<?php

/**
 * @copyright 2010 Payson
 */

function _paysonGetHeaderData($userid, $md5key, $moduleversion){
	$module_vesion = explode("-",$moduleversion);
	
	$headerdata = array('PAYSON-SECURITY-USERID: '.$userid,
	                    'PAYSON-SECURITY-PASSWORD: '.$md5key,
						'PAYSON-APPLICATION-ID: '.$moduleversion,
						'PAYSON-MODULE-INFO: payson_zencart|'.$module_vesion[2].'|'.getVersionPS());
	return $headerdata;
}

function paysonGetShopLanguage($lang){
     switch ( strtoupper($lang) ){ 
	   case 'SV':
	   case 'SE':
	   case 'SVENSKA':
	   case 'SWEDISH':
	   $localeCode = 'SV';
	  break;

	  case 'FI':
	  case 'FINNISH':
	  case 'FINSKA':
	  case 'SUOMI':
	   $localeCode = 'FI';
	  break;

	  default :
	   $localeCode = 'EN';
    }
    return $localeCode;
}

function  paysonGetPaysonResults($local_data,$res){
	/*
	echo "localdata ";
	print_r($local_data);
	echo "\r\n ";
	*/
	$res_arr = array();
	$res_arr = explode("&",$res);
	
	//message status
	list($message_status_tag, $message_status_val) = explode("=", $res_arr[0]);
	
	//find interesting keys
	$i=0;
	while($i < sizeof($res_arr) ){
		list($tag, $val) = explode("=", $res_arr[$i]);
		switch ($tag){ 
	     case 'status':
	       $payment_status_val = $val;
	     break;
	     
	     case 'invoiceStatus':
	       $invoice_status_val = $val;
	     break;

         case 'receiverList.receiver(0).amount':
           $amount_val = $val;
         break;
         
	     case 'trackingId':
	       $trackingId_val = $val;
	     break;

	     case 'currencyCode':
	       $currencyCode_val = $val;
	     break;

         case 'purchaseId':
           $purchaseId_val = $val;
         break;
         
         case 'type':
           $type_val = $val;
         break;
         
	     default :
	     //nothing
        }
		$i++;
	}
	
	//check payment status
	if($message_status_val == "SUCCESS" ){
		$paymentResults['status'] = $payment_status_val;
	}
	
	//---- compare amount -------------------------
	if (str2num($local_data['amount']) != $amount_val){
		$paymentResults['status'] .= ", but amount is not correct". $local_data['amount'];
	}
	//------- compare trackingId-------------------
	if ($local_data['trackingId'] != $trackingId_val){
		$paymentResults['status'] .= ", but trackingId is not correct";
	}
	//--------compare currencyCode-----------------
	if ($local_data['currencyCode'] != $currencyCode_val){
		$paymentResults['status'] .= ", but currencyCode is not correct";
	}
	//check IPN validation result
	if ($local_data['ipn_verify_status'] != 'VERIFIED'){
		$paymentResults['status'] .= ", but IPN validation is not correct";
	}
	//---------- get paysonreference------------------
	$paymentResults['purchaseId'] = $purchaseId_val;
	//----------- get payment type ----------------------
	$paymentResults['type'] = $type_val;
	//------------ get possible invoice status---------------
	if (isset($invoice_status_val)){
	  $paymentResults['invoice_status'] = $invoice_status_val;
	}
	return $paymentResults;
}

function paysonGetShippingAddress($res){
    $res_arr = array();
	$res_arr = explode("&",$res);
	
	//find interesting keys
	$i=0;
	while($i < sizeof($res_arr) ){
		list($tag, $val) = explode("=", $res_arr[$i]);
		switch ($tag){ 
	     case 'shippingAddress.name':
	       $shippingAddress['name'] = urldecode($val);
	     break;

         case 'shippingAddress.streetAddress':
           $shippingAddress['streetAddress'] = urldecode($val);
         break;
         
	     case 'shippingAddress.postalCode':
	       $shippingAddress['postalCode'] = urldecode($val);
	     break;

	     case 'shippingAddress.city':
	       $shippingAddress['city'] = urldecode($val);
	     break;

         case 'shippingAddress.country':
           $shippingAddress['country'] = urldecode($val);
         break;
         
	     default :
	     //nothing
        }
		$i++;
	}  
	return $shippingAddress;
}

function paysonValidateIpnMessage($userid, $md5key, $moduleversion, $url, $message){
	
	$headerdata = _paysonGetHeaderData($userid, $md5key, $moduleversion); 
    //------------- do a curl call to payson				
	$ch = curl_init();					
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headerdata ); 
    curl_setopt($ch, CURLOPT_URL, $url);
    //curl_setopt($ch, CURLOPT_POST, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $message ); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    
    $res = curl_exec($ch);
    if($res === false){
      //serious problem, most likely we did not connect to payson	
      die( 'Curl error: ' . curl_error($ch) );
    }
    curl_close($ch);
    return $res;
}

function paysonGetPaymentDetails($userid, $md5key, $moduleversion, $url, $token){
	
	$headerdata = _paysonGetHeaderData($userid, $md5key, $moduleversion);
	
	$postdata  = "token=".$token;
	
	//------------- do a curl call to payson				
	$ch = curl_init();					
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headerdata ); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $res = curl_exec($ch);
    if($res === false){
      //serious problem, most likely we did not connect to payson	
      die( 'Curl error: ' . curl_error($ch) );
    }
    curl_close($ch);
    return $res;					
						
}

function paysonTokenRequest($userid, $md5key, $moduleversion, $url, $postdata, $orderitemslist, $defaults,$processOrderList = false){
    $headerdata = _paysonGetHeaderData($userid, $md5key, $moduleversion);

    //urls
    $returnUrl = urlencode($postdata['returnUrl']);
    $cancelUrl = urlencode($postdata['cancelUrl']);
    $ipnNotificationUrl = urlencode($postdata['ipnNotificationUrl']);

    //language and currency
    $localeCode = $postdata['localeCode'];
    $currencyCode = $postdata['currencyCode'];

    //limitation in payment methods
    $fundingList = $postdata['fundingList'];

    //fees
    $feesPayer = $defaults['feesPayer'];

    //misc
    $memo = urlencode($postdata['memo']);
    $custom = urlencode($postdata['custom']);
    $trackingId = $postdata['trackingId'];
    //$guaranteeOffered = $postdata['guaranteeOffered'];
    $guaranteeOffered = 'NO';

    //sender(end customer)
    $senderEmail = urlencode($postdata['senderEmail']);
    $senderFirstName = urlencode($postdata['senderFirstName']);
    $senderLastName = urlencode($postdata['senderLastName']);

    //receiver (Shop ownwer, aka seller)
    $rc_email = urlencode($postdata['receiverEmail']);

    //amount
    $rc_amount = urlencode($postdata['amount']);

    //note that we do not preprocess $orderitemslist here

    //-------------built postdata(as get to adapt to receiving server)------------------------------------- 
    //urls
    $postdata2  = "returnUrl=".$returnUrl."&cancelUrl=".$cancelUrl."&ipnNotificationUrl=".$ipnNotificationUrl;
    //language and currency
    $postdata2 .= "&localeCode=".$localeCode."&currencyCode=".$currencyCode;
    //limitation in payment methods
    if ($postdata['fundingList'] == 'ALL')
    {
        //do not populate because default is all methods
    }
    else if ($postdata['fundingList'] == 'INVOICE')
    {
        $postdata2 .= "&fundingList.fundingConstraint(0).constraint=INVOICE";
    }
    else
    {
        $i=0;
        foreach($postdata['fundingList'] AS $constraintItem)
        {
            $item = str_replace(" ", "", $constraintItem);
            $postdata2 .= "&fundingList.fundingConstraint(".$i.").constraint=".$item;
            $i++;
        }
    }

    //fees
    $postdata2 .= "&feesPayer=".$feesPayer;
    //invoiceFee?
    if ($postdata['invoiceFee'] > 0)
    {
        $postdata2 .= "&invoiceFee=".urlencode($postdata['invoiceFee']);
    }

    //misc
    $postdata2 .= "&memo=" . htmlspecialchars(utf8_encode($memo));
    $postdata2 .= "&custom=".$custom."&trackingId=".$trackingId."&guaranteeOffered=".$guaranteeOffered;
    //sender(end customer)
    $postdata2 .= "&senderEmail=".$senderEmail."&senderFirstName=".$senderFirstName."&senderLastName=".$senderLastName;
    //receiver (Shop ownwer, aka seller)
    $postdata2 .= "&receiverList.receiver(0).email=".$rc_email;
    //amount
    $postdata2 .= "&receiverList.receiver(0).amount=".$rc_amount;

 //   $asdf =  print_r($orderitemslist, true);
   // echo $asdf;
   // die();
    //$orderitemslist
    if ($processOrderList == true)
    {
        if (sizeof($orderitemslist) > 0 )
        {
            $i=0;
            foreach($orderitemslist AS $orderitem)
            {
                $postdata2 .= "&orderItemList.orderItem(".$i.").description=".htmlspecialchars(utf8_encode($orderitem['description']));
                $postdata2 .= "&orderItemList.orderItem(".$i.").sku=".$orderitem['sku'];
                $postdata2 .= "&orderItemList.orderItem(".$i.").quantity=".$orderitem['quantity'];
                $postdata2 .= "&orderItemList.orderItem(".$i.").unitPrice=".$orderitem['unitPrice'];
                $postdata2 .= "&orderItemList.orderItem(".$i.").taxPercentage=".$orderitem['taxPercentage'];
 
                $i++;
            }
        }
    }
    //what we send
    //print_r($postdata2);

    //------------- do a curl call to payson
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerdata ); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);	

    $res = curl_exec($ch);
    if($res === false)
    {
        //serious problem, most likely we did not connect to payson	
        die( 'Curl error: ' . curl_error($ch) );
    }
    curl_close($ch);
    
    return $res;
}


function paysonPaymentUpdate($userid, $md5key, $moduleversion, $url, $token, $update_cmd){
	$headerdata = _paysonGetHeaderData($userid, $md5key, $moduleversion);
	if (!isset($token) ){
		return false;
	}
	if (!isset($update_cmd) ){
		return false;
	}
	//----------------------------------------------------------------
	$postdata2  = "token=".urlencode($token)."&action=".$update_cmd;
	//------------- do a curl call to payson				
	$ch = curl_init();					
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headerdata );
	curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata2); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    $res = curl_exec($ch);
    if($res === false){
      //serious problem, most likely we did not connect to payson	
      die( 'Curl error: ' . curl_error($ch) );
    }
    curl_close($ch);
    
    return paysonTokenResponseValidate($res);
}

function paysonTokenResponseValidate($paysonTokenResponse){
	//$ack, $timestamp, $version, $token, $correlationId
    list($pair0, $pair1, $pair2,$pair3, $pair4) = explode("&",$paysonTokenResponse);
    list($ack_name, $ack_value) = explode("=",$pair0);
    if ($ack_value == "SUCCESS"){
    	return true;
    } else {
    	return false;
    }	
	
}

function paysonGetToken($paysonTokenResponse){
	//$ack, $timestamp, $version, $token, $correlationId
    list($pair0, $pair1, $pair2,$pair3, $pair4) = explode("&",$paysonTokenResponse);
    
    list($ack_name, $ack_value) = explode("=",$pair0);
    list($timestamp_name, $timestamp_value) = explode("=",$pair1);
    list($version_name, $version_value) = explode("=",$pair2);
    list($token_name, $token_value) = explode("=",$pair3);
    list($correlationId_name, $correlationId_value) = explode("=",$pair4);
    
    $result['ack']           = $ack_value;
    $result['timestamp']     = urldecode($timestamp_value);
    $result['version']       = $version_value;
    $result['TOKEN']         = $token_value;
	$result['correlationId'] = $correlationId_value;
    
    return $result;
}

function paysonGetBadResponse($paysonTokenResponse){
	//$ack, $timestamp, $version, $errorList
	$errorList = array();
	$list = array();
	$list = explode("&",$paysonTokenResponse);
	
	list($ack_name, $ack_value) = explode("=",$list[0]);
    list($timestamp_name, $timestamp_value) = explode("=",$list[1]);
    list($version_name, $version_value) = explode("=",$list[2]);
    
    $result['ack']           = $ack_value;
    $result['timestamp']     = urldecode($timestamp_value);
    $result['version']       = $version_value;
    
    $result['errorId']       = '';
    $result['message']       = '';
	$i=0;
    foreach($list AS $item) {
	  if ($i > 2){
	  	 list($key, $value) = explode("=",$item);
	  	 if(strpos($key, "errorId")>5){
	  	 	$result['errorId'] .= $value.", ";
	  	 } else if(strpos($key, "message")>5){
	  	 	$result['message'] .= $value.", ";
	  	 } else if(strpos($key, "parameter")>5){
	  	 	$result['message'] .= "parameter: ".$value.", ";
	  	 }	
	  }
	  $i++;
    }
    $result['errorId'] = rtrim($result['errorId'],' ');
    $result['errorId'] = rtrim($result['errorId'],',');
    $result['message'] = rtrim($result['message'],' ');
    $result['message'] = urldecode(rtrim($result['message'],',') );
    
    return $result;
}

function paysonBrowserRedirect($rurl, $token_value){
  $url = $rurl.$token_value;
  header("Location: ".$url);
}

function str2num($str){ 
  if(strpos($str, '.') < strpos($str,',')){ 
            $str = str_replace('.','',$str); 
            $str = strtr($str,',','.');            
        } 
        else{ 
            $str = str_replace(',','',$str);            
        } 
        return (float)$str; 
} 

function getVersionPS(){
	global $db;
	
	$zencartVersion = $db->execute("SELECT project_version_minor FROM ". TABLE_PROJECT_VERSION . "  WHERE project_version_key = 'Zen-Cart Main'");
	if ($zencartVersion->RecordCount() > 0) {
  		return $zencartVersion->fields['project_version_minor'];
	} else {
 		 return 'NONE';
	}
}

function paysonCreatePaytransTableQuery($table_name){
	return " CREATE TABLE IF NOT EXISTS ".$table_name." (
          `payson_paytrans_id` int(11) unsigned NOT NULL auto_increment,
          `created` varchar(30),
          `trackingId` varchar(40) NOT NULL default '',
          `customers_id` int(11) NOT NULL default '0',
          `amount` decimal(15,4) default NULL,
          `currency` char(3) default NULL,
          `lang` char(2) default NULL,
		  `curl_ack` varchar(40) NOT NULL default '',
          `curl_timestamp` varchar(40) NOT NULL default '',
          `curl_correlationId` varchar(40) NOT NULL default '',
          `token` varchar(40) NOT NULL default '',
          `curl_errorId` tinytext,
          `curl_message` mediumtext,
          `payson_status` varchar(255),
          `invoice_status` varchar(40),
          `payson_type` varchar(40),
		  `payson_reference` varchar(40),
          `orders_id` int(11) default NULL,
          `struct` mediumtext,
           PRIMARY KEY  (`payson_paytrans_id`)
        ) ENGINE=MyISAM";
}

function paysonCreateTransEventsTableQuery($table_name){
	return " CREATE TABLE IF NOT EXISTS ".$table_name." (
          `payson_events_id` int(11) unsigned NOT NULL auto_increment,
          `created` varchar(30),
          `event_tag` varchar(30),
          `token` varchar(40) NOT NULL default '',
          `trackingId` varchar(40) NOT NULL default '',
          `logged_message` mediumtext,
           PRIMARY KEY  (`payson_events_id`)
        ) ENGINE=MyISAM";
}

?>
