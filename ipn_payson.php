<?php
/**
 * ipn_payson.php callback handler for Payson IPN notifications
 *
 * @package paymentMethod
 * @copyright Copyright 2010 Payson
 */
 $filelogging = false;
 function stringDump($string){
  $filename= "ipn_payson.txt";
  $handle = fopen($filename, "a");
  fwrite($handle, "** ".date("Y-m-d H:i:s")." **\n");
  fwrite($handle, "Data: $string; \n");
  fclose($handle);
}
$req['input'] = file_get_contents('php://input');
if ($filelogging){
  stringDump($req['input']);
  stringDump("token= ".$_POST['token']);
} 

 require('includes/application_top.php');
 switch ($_GET['mode']){ 
	case 'payson':
	   require( DIR_FS_CATALOG . 'includes/modules/payment/payson.php');
	break;

	case 'payson_inv':
	   require( DIR_FS_CATALOG . 'includes/modules/payment/payson_inv.php');
	break;

	default :
	 exit;
}

 include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

 
 global $db; 
 
 
 
 switch ($_GET['mode']){ 
	case 'payson':
	   $payment_class = new payson;
       $userid = MODULE_PAYMENT_PAYSON_BUSINESS_ID;
       $md5key = MODULE_PAYMENT_PAYSON_MD5KEY;
	break;

	case 'payson_inv':
	   $payment_class = new payson_inv;
       $userid = MODULE_PAYMENT_PAYSON_INV_BUSINESS_ID;
       $md5key = MODULE_PAYMENT_PAYSON_INV_MD5KEY;
	break;

	default :
	 exit;
}
 $moduleversion = $payment_class->getApplicationVersion(); 
 
  
 //insert ipn_call in events
 $now = date("Y-m-d H:i:s");
 $paysonEvents = DB_PREFIX.$paysonDbTableEvents;
 
 $message = serialize($req['input']);
 
 $token           = $_POST['token'];
 $trackingId      = $_POST['trackingId'];
 if (!isset($trackingId)){
 	exit;
 	//to avoid spoofing from browsers
 }
 
 $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'IPN_CALL',
                             token          = '".$token."',  
		                     trackingId     = ".$trackingId.",
                             logged_message = '".$message."',
							 created        = '".$now."' ");
 
 //insert ipn_validate_response in events
 $now = date("Y-m-d H:i:s");
 $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'IPN_VALIDATE_REQUEST',
                             token          = '".$token."',
							 trackingId     = ".$trackingId.",  
                             logged_message = '".$message."',
							 created        = '".$now."' ");
 
 
 $response=paysonValidateIpnMessage($userid, $md5key, $moduleversion, $paysonIpnMessageValidationURL, $req['input']);
 
 //insert ipn_validate_response in events
 $now = date("Y-m-d H:i:s");
 $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'IPN_VALIDATE_RESPONSE',
                             token          = '".$token."',
							 trackingId     = ".$trackingId.",  
                             logged_message = '".$response."',
							 created        = '".$now."' ");
  
 
?>