<?php
/**
 * payson_inv.php payment module class for new Payson API
 *
 * @package paymentMethod
 * 
 */


/**
 *  ensure dependencies are loaded
 */

include_once( DIR_FS_CATALOG . 'includes/modules/payment/payson/functions.payson.php');

/**
 * payson.php payment module class for new Payson API
 *
 */ 
class payson_inv extends base {
    /**
     * string representing the payment method
     *
     * @var string
     */
    var $code;
    /**
     * $title is the displayed name for this payment method
     *
     * @var string
     */
    var $title;
    /**
     * $description is a soft name for this payment method
     *
     * @var string
     */
    var $description;
    /**
     * $enabled determines whether this module shows or not... in catalog.
     *
     * @var boolean
     */
    var $enabled;

    var $paysonModuleVersion;


    /**
     * constructor
     *
     * @return payson
     */
    function payson_inv() {

    include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');
    
    global $order, $messageStack;
    $this->code 		= 'payson_inv';
    $this->codeVersion 	= '1.3.9';
    $this->paysonModuleVersion = 'PAYSONINV-ZENCART-1.9';

    if (IS_ADMIN_FLAG === true) 
    {
        $this->title = MODULE_PAYMENT_PAYSON_INV_TEXT_ADMIN_TITLE; // Payment Module title in Admin
    } 
    else 
    {
        $this->title = MODULE_PAYMENT_PAYSON_INV_TEXT_CATALOG_TITLE; // Payment Module title in Catalog
    }

    $this->description 	= MODULE_PAYMENT_PAYSON_INV_TEXT_DESCRIPTION;
    $this->sort_order 	= MODULE_PAYMENT_PAYSON_INV_SORT_ORDER;
    $this->enabled 		= ((MODULE_PAYMENT_PAYSON_INV_STATUS == 'True') ? true : false);
    $this->email_logg  = MODULE_PAYMENT_PAYSON_PAYMENTS_INVOICE_EMAIL_LOGG;
	
    if ((int)MODULE_PAYMENT_PAYSON_INV_ORDER_STATUS_ID > 0) 
    {
        $this->order_status = MODULE_PAYMENT_PAYSON_INV_ORDER_STATUS_ID;
    }
    if (is_object($order)) {
	$this->update_status();
    }
    
    $this->form_action_url = $paysonBrowserPostURL;
    
    if (PROJECT_VERSION_MAJOR != '1' && substr(PROJECT_VERSION_MINOR, 0, 3) != '3.9') 
    {
	$this->enabled = false;
    }
    
    //check currency and do not present payment option if currency is not supported
    
    if (in_array(strtoupper($_SESSION['currency']), $paysonInvoiceCurrenciesSupported) || $_SESSION['currency'] == '')
    {
        //ok, currency is supported
    } 
    else 
    {
        //currency is not supported, hide option	
        $this->enabled = false;
    }	
  }
  
    function getApplicationVersion()
    {
        return $this->paysonModuleVersion;
    }
  
    /**
     * calculate zone matches and flag settings to determine whether this module should display to customers or not
     *
     */
    function update_status() 
    {
        global $order, $db;

        if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_PAYSON_INV_ZONE > 0) ) 
        {
            $check_flag = false;
            $check_query = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYSON_INV_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
            while (!$check_query->EOF)  
            {
                if ($check_query->fields['zone_id'] < 1) 
                {
                    $check_flag = true;
                    break;
                } 
                elseif ($check_query->fields['zone_id'] == $order->billing['zone_id']) 
                {
                    $check_flag = true;
                    break;
                }
                $check_query->MoveNext();
            }

	    if ($check_flag == false)
            {
                $this->enabled = false;
            }

        }
    }

    /**
     * JS validation which does error-checking of data-entry if this module is selected for use
     * (Number, Owner, and CVV Lengths)
     *
     * @return string
     */
    function javascript_validation() 
    {
        return false;
    }
    
    /**
     * Displays payment method name along with Credit Card Information Submission Fields (if any) on the Checkout Payment Page
     *
     * @return array
     */
    function selection() {
        global $order;
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        //check for min order value
        if ( $order->info['total']*$order->info['currency_value'] < $paysonInvoiceMinimalOrderValue ) 
        {
            //below min amount
            $this->enabled = false;
            return;
        }

        return array('id' => $this->code,
                     'module' => MODULE_PAYMENT_PAYSON_INV_TEXT_CATALOG_LOGO,
                     'icon' => MODULE_PAYMENT_PAYSON_INV_TEXT_CATALOG_LOGO
                    );
    }

    /**
     * Normally evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
     * Since payson module is not collecting info, it simply skips this step.
     *
     * @return boolean
     */

    function pre_confirmation_check() 
    {
        //before generating html for step3
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');
        global $db, $order, $currencies, $currency; 

        //0 determine currency, language, amount and get an temporary trackingid
        $localeCode = $this->_getLanguageCode();
        $currencyCode = strtoupper($_SESSION['currency']);
        $trackingId = time();

        $now = date("Y-m-d H:i:s");
        $paysonTable = DB_PREFIX.$paysonDbTablePaytrans;
        $paysonEvents = DB_PREFIX.$paysonDbTableEvents;

        //db insert below when amount has possible invoice fee

        //1 prepare and call paysonTokenRequest()

        $postdata['returnUrl'] = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true, false);
        $postdata['cancelUrl'] = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true, false);
        $postdata['ipnNotificationUrl'] = zen_href_link('ipn_payson.php?mode=payson_inv', '', 'SSL', true, false, true, true);
        $postdata['localeCode'] = $localeCode;
        $postdata['currencyCode'] = $currencyCode;
        //write trackingId into end customers payment info
        $postdata['memo'] = sprintf(MODULE_PAYMENT_PAYSON_INV_PURCHASE_DESCRIPTION_TITLE, $trackingId);

        $postdata['trackingId'] = $trackingId;
        $postdata['senderEmail'] = $order->customer['email_address'];
        $postdata['senderFirstName'] = $order->customer['firstname'];
        $postdata['senderLastName'] = $order->customer['lastname'];
        $postdata['receiverEmail'] = MODULE_PAYMENT_PAYSON_INV_SELLER_EMAIL;

        //make order items list
        $orderitemslist = array();
        $n = sizeof($order->products);

        // First all order items
        for ($i = 0 ; $i < $n ; $i++) 
        {
            $price_without_tax = $order->products[$i]['final_price'] * $order->info['currency_value'];
            $taxPercentage = ($order->products[$i]['tax'] / 100);

            $orderitemslist[] = array(
                             'description'   => $order->products[$i]['name'],
                             'sku'           => zen_get_prid($order->products[$i]['id']),
                             'quantity'      => $order->products[$i]['qty'],
                             'unitPrice'     => number_format($price_without_tax, 2, '.', ''),
                             'taxPercentage' => $taxPercentage,
            );

            $total_amount += number_format( number_format($price_without_tax, 2, '.', '') 
                                            * (1 +  $taxPercentage)
                                            * $order->products[$i]['qty'] , 2, '.', '');
        }

        foreach($GLOBALS['order_totals'] as $k_global => $v_global)
        {
            $name = strip_tags($v_global['title']);
            die($name);
        }

        //check for coupons----------------------------------
        $coupon = 0;

        if ( isset($order->info['coupon_code']) )
        {
            $couponQuery = $db->execute("select * from ".TABLE_COUPONS." where coupon_code='".$order->info['coupon_code']."' AND coupon_active='Y' ");

            if($couponQuery->RecordCount() == 1 )
            {
                $coupon    = $couponQuery->fields['coupon_amount']*$order->info['currency_value'];
                $coupon_id = $couponQuery->fields['coupon_id'];
            }
        }
    
        if ($coupon > 0)
        {
            $couponDescQuery = $db->execute("select * from ".TABLE_COUPONS_DESCRIPTION." where coupon_id=".$coupon_id." AND language_id=".$_SESSION['languages_id']);
            $couponDescription = $couponDescQuery->fields['coupon_description'];
            $taxPercentage = zen_get_tax_rate(MODULE_PAYMENT_PAYSON_INV_TAX_CLASS, $order->billing['country']['id'], $order->billing['zone_id']) / 100;

            // if (DISPLAY_PRICE_WITH_TAX == 'true')
            // {
            $price_without_tax = ($coupon/(1+$taxPercentage));
            // }

            $orderitemslist[] = array(
                             'description'   => $couponDescription,
                             'sku'           => $coupon_id,
                             'quantity'      => 1,
                             'unitPrice'     => number_format(-1*$price_without_tax, 2, '.', ''),
                             'taxPercentage' => number_format($taxPercentage, 2, '.', ''),
            );

            // Negative amount!
            $total_amount += number_format(-1 * $coupon, 2, '.', ',');
        }

    	if (zen_not_null($order->info['shipping_method'])){
		    $shipping_module = substr($_SESSION['shipping']['id'], 0, strpos($_SESSION['shipping']['id'], '_'));
		    $shipping_tax_percentage = zen_get_tax_rate($GLOBALS[$shipping_module]->tax_class, $order->billing['country']['id'], $order->billing['zone_id']) / 100;
		    $shipping_price_without_tax = $order->info['shipping_cost'] * $order->info['currency_value'];
		
		    if (DISPLAY_PRICE_WITH_TAX == 'true')
		    {
		        $shipping_price_without_tax = ($shipping_price_without_tax/(1+$shipping_tax_percentage));
		    }
		
		    $orderitemslist[] = array(
		         'description'   => $order->info['shipping_method'],
		         'sku'           => 9998,
		         'quantity'      => 1,
		         'unitPrice'     => number_format($shipping_price_without_tax, 2, '.', ''),
		         'taxPercentage' => $shipping_tax_percentage,
		     );
		
		     $total_amount += number_format($shipping_price_without_tax
		                                * (1+$shipping_tax_percentage), 2, '.', '');
		}

        //check for invoice fee
        $fee = 0;
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYSON_INV_FEE_STATUS'");
        if($check_query->RecordCount() ==1)
        {
            $usefee = $check_query->fields['configuration_value'];
            if ($usefee)
            {
                $fee_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYSON_INV_FEE_FEE'");
                $fee = $fee_query->fields['configuration_value']*$order->info['currency_value'];

                $fee_tax_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYSON_INV_FEE_TAX_CLASS'");
                $fee_tax_class = $fee_tax_query->fields['configuration_value'];			

                $taxPercentage = zen_get_tax_rate($fee_tax_class, $order->billing['country']['id'], $order->billing['zone_id']) / 100;

                $fee = $fee * (1+$taxPercentage);
            } 
        }

        if ($fee > 0)
        {
            $postdata['invoiceFee'] = number_format($fee, 2, '.', ',');
            $total_amount += number_format($fee, 2, '.', ',');
        }

        $postdata['amount'] = $total_amount;

        $db->Execute(" INSERT INTO ".$paysonTable." SET 
                             trackingId     = ".$trackingId.",
                             customers_id   = ".(int)$_SESSION['customer_id'].",
                             amount         = ".$total_amount.",
                             currency       = '".$currencyCode."',
                             lang           = '".$localeCode."',
                             created        = '".$now."' ");

         //get default setting for fees             
         $defaults['feesPayer'] = $paysonFeesPayerSupported[1];
         //only invoice
         $postdata['fundingList'] = 'INVOICE';

         $now = date("Y-m-d H:i:s");
         $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'TOKEN_REQUEST',
                             trackingId     = ".$trackingId.",  
                             logged_message = '".serialize($postdata)."',
                             created        = '".$now."' ");

	 $paysonTokenResponse = paysonTokenRequest(MODULE_PAYMENT_PAYSON_INV_BUSINESS_ID, 
		 				   MODULE_PAYMENT_PAYSON_INV_MD5KEY, 
						   $this->paysonModuleVersion, 
						   $paysonTokenRequestURL, 
						   $postdata, 
						   $orderitemslist, 
						   $defaults, 
						   true);

          //2 validate response
          $paysonTokenResponseValid = paysonTokenResponseValidate($paysonTokenResponse);
          if ($paysonTokenResponseValid == true)
          {
               $paysonToken = paysonGetToken($paysonTokenResponse);

               $now = date("Y-m-d H:i:s");
               $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                                   event_tag      = 'TOKEN_RESPONSE',
                                   token          = '".$paysonToken['TOKEN']."',
                                   trackingId     = ".$trackingId.",  
                                   logged_message = '".serialize($paysonTokenResponse)."',
                                   created        = '".$now."' ");


               //uppdate table payson_paytrans
               $db->Execute(" UPDATE ".$paysonTable." SET 
                                   token              = '".$paysonToken['TOKEN']."',
                                   curl_ack           = '".$paysonToken['ack']."',
                                   curl_timestamp     = '".$paysonToken['timestamp']."',
                                   curl_correlationId = '".$paysonToken['correlationId']."'
                                   WHERE trackingId   = ".$trackingId);

               //$_SESSION['PAYSON_TOKEN'] = $paysonToken['TOKEN'];
               $this->form_action_url = $paysonBrowserPostURL."?token=".$paysonToken['TOKEN'];
          } 
          else 
          {
              //bad response, redirect to payment selection page

              zen_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, 'Log from Payson Invoice Module::paysonTokenRequest', 
                       "Data: \r\n" . str_replace("&", "\r\n", http_build_query($postdata)) .
                       "\r\n\r\nOrder Items: \r\n" . str_replace("&", "\r\n", http_build_query($orderitemslist)) . 		
                       "\r\n\r\n\r\n Response: \r\n" . str_replace("&", "\r\n", $paysonTokenResponse), 
                       STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, "", 'debug');	

              $badResponse = paysonGetBadResponse($paysonTokenResponse);

              $now = date("Y-m-d H:i:s");
              $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                                event_tag      = 'TOKEN_RESPONSE',
                                token          = 'Bad token response',
                                trackingId     = ".$trackingId.",  
                                logged_message = '".serialize($paysonTokenResponse)."',
                                created        = '".$now."' ");

              //uppdate table payson_paytrans
              $db->Execute(" UPDATE ".$paysonTable." SET curl_ack = '".$badResponse['ack']."',
                                curl_timestamp     = '".$badResponse['timestamp']."',
                                curl_correlationId = '".$badResponse['correlationId']."',
                                curl_errorId       = '".$badResponse['errorId']."',
                                curl_message       = '".$badResponse['message']."'
                                WHERE trackingId = ".$trackingId);

              $messageStack = new messageStack;
              $messageStack->add_session('checkout_payment', $badResponse['message'], 'error');
              zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));    	
          }
          return false;
    }

    /**
     * Display Credit Card Information on the Checkout Confirmation Page
     * Since none is collected for payson before forwarding to payson site, this is skipped
     *
     * @return boolean
     */
    function confirmation() 
    {
       return false;
    }

    /**
     * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
     * This sends the data to the payment gateway for processing.
     * (These are hidden fields on the checkout confirmation page)
     *
     * @return string
     */
    function process_button() 
    {
        $process_button_string = '';
        return $process_button_string;

    }

    /**
     * Determine the language to use inc fallback
     */
    function _getLanguageCode() 
    {
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        if (in_array(strtoupper($_SESSION['languages_code']), $paysonLanguagesSupported))
        {
            //some of the supported languages
            $lang_code = strtoupper($_SESSION['languages_code']);
        }
        else if (strtoupper($_SESSION['languages_code']) == 'SE')
        {
            //possible wrong notation for swedish
            $lang_code = 'SV';
        } 
        else 
        {
            //could be anything, use english at Payson
            $lang_code = 'EN';
        }
        return $lang_code;
    }
   
    /**
     * Store transaction info to the order and process any results that come back from the payment gateway
     */
    function before_process() 
    {
        global $db, $order, $_GET;
        $token = $_GET['TOKEN'];  

        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        $paysonTable = DB_PREFIX.$paysonDbTablePaytrans;
        $paysonEvents = DB_PREFIX.$paysonDbTableEvents;

        if (strlen($token)< 20) 
        {
            //too short or no token
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        //get db id out of token
        $res = $db->Execute(" SELECT * FROM ".$paysonTable." WHERE token='".$token."'");
        if( $res->RecordCount()==0 )
        {
            //token not found
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        $trackingId =$res->fields['trackingId'];
        $now = date("Y-m-d H:i:s");
        $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'GET_PAYMENT_DETAILS_REQUEST',
                             token          = '".$token."',
                             trackingId     = ".$trackingId.",  
                             logged_message = '".$token."',
                             created        = '".$now."' ");

        $res = paysonGetPaymentDetails(MODULE_PAYMENT_PAYSON_INV_BUSINESS_ID, MODULE_PAYMENT_PAYSON_INV_MD5KEY, $this->paysonModuleVersion, $paysonPaymentDetailsURL, $token);

        $res_arr = explode("&",$res);
        $i=0;
        $ipn_status = '';
	while($i < sizeof($res_arr) ){
            list($tag, $val) = explode("=", $res_arr[$i]);
                if ($val == 'PENDING' ){
                    $ipn_status = $val;
                    break;
                    
                }
            $i++;    
        }
        
        $now = date("Y-m-d H:i:s");
        $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'GET_PAYMENT_DETAILS_RESPONSE',
                             token          = '".$token."',
                             trackingId     = ".$trackingId.",  
                             logged_message = '".serialize($res)."',
                             created        = '".$now."' ");


        $ipnres = $db->execute("SELECT * FROM ".$paysonEvents." WHERE trackingId=".$trackingId." AND event_tag='IPN_VALIDATE_RESPONSE'");

        //uppdatera tabellen payson paytrans med orderid
        $local_data['amount'] = number_format($order->info['total']*$order->info['currency_value'],2,'.',',' );
        $local_data['trackingId'] = $trackingId;
        $local_data['currencyCode'] = strtoupper($_SESSION['currency']);
        $local_data['ipn_verify_status'] = $ipnres->fields['logged_message'];

        $paymentResults = paysonGetPaysonResults($local_data,$res);


        if($ipn_status == 'PENDING'){
         
                $db->Execute(" UPDATE ".$paysonTable." SET 
                                  payson_status='".$paymentResults['status']."',
                                  payson_type='".$paymentResults['type']."',
                                  payson_reference=".$paymentResults['purchaseId'].",
                                  invoice_status='".$paymentResults['invoice_status']."'
                                  WHERE trackingId=".$trackingId);
               
        }
         else{
                // CANCEL or non approved
                $db->Execute(" UPDATE ".$paysonTable." SET payson_status='".$ipn_status."' WHERE token='".$token."'");
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
         }


         $add_comments  = MODULE_PAYMENT_PAYSON_INV_TEXT_PAYSONREF;
         $add_comments .= ": ";
         $add_comments .= $paymentResults['purchaseId'];
         $new_comments  = $order->info['comments'];
         $new_comments .= $add_comments;
         $order->info['comments'] = $new_comments;

         //since this in an invoice, we need to force update shippingadress
         $shippingAddress = paysonGetShippingAddress($res);
         $order->delivery['firstname']        = $shippingAddress['name'];
         $order->delivery['lastname']         = '';
         $order->delivery['street_address']   = $shippingAddress['streetAddress'];
         $order->delivery['city']             = $shippingAddress['city'];
         $order->delivery['postcode']         = $shippingAddress['postalCode'];
         $order->delivery['country']['title'] = $shippingAddress['country'];    

         return true;
    }


    function after_order_create($zf_order_id)
    {
        global $db, $_GET;
        $token = $_GET['TOKEN'];
        //uppdatera tabellen payson paytrans med orderid
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        $paysonTable = DB_PREFIX.$paysonDbTablePaytrans;
        $db->Execute(" UPDATE ".$paysonTable." SET orders_id=".$zf_order_id." WHERE token='".$token."'");
    } //after_order_create

    /**
     * Checks referrer
     *
     * @param string $zf_domain
     * @return boolean
     */
    function check_referrer($zf_domain) 
    {
        return true;
    }

    /**
     * Post-processing activities
     * When the order returns from the processor, if PDT was successful, this stores the results in order-status-history and logs data for subsequent reference
     *
     * @return boolean
     */
    function after_process() 
    {
        return true;
    }

    /**
     * Used to display error message details
     *
     * @return boolean
     */
    function output_error() 
    {
        return false;
    }

    /**
     * Check to see whether module is installed
     *
     * @return boolean
     */
    function check() 
    {
        global $db;

        if (!isset($this->_check)) 
        {
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYSON_INV_STATUS'");
            $this->_check = $check_query->RecordCount();
        }

        return $this->_check;
    }

    /**
     * Install the payment module and its configuration settings
     *
     */
    function install() 
    {
        global $db, $messageStack;
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        if (!isset($_SESSION['language']))
        {
            include( DIR_FS_CATALOG . 'includes/languages/english/modules/payment/payson_inv.php');
        } 
        else 
        {
            include( DIR_FS_CATALOG . 'includes/languages/'.$_SESSION['language'].'/modules/payment/payson_inv.php');
        }

        $paysonTable = DB_PREFIX.$paysonDbTablePaytrans;
        $paysonEvents = DB_PREFIX.$paysonDbTableEvents;

        $db->Execute(paysonCreatePaytransTableQuery($paysonTable));
        $db->Execute(paysonCreateTransEventsTableQuery($paysonEvents));


        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".MODULE_PAYMENT_PAYSON_INV_ENABLE_TEXT."', 'MODULE_PAYMENT_PAYSON_INV_STATUS', 'True', '".MODULE_PAYMENT_PAYSON_INV_ACCEPT_TEXT."', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".MODULE_PAYMENT_PAYSON_INV_SELLEREMAIL_HEAD."', 'MODULE_PAYMENT_PAYSON_INV_SELLER_EMAIL','".STORE_OWNER_EMAIL_ADDRESS."', '".MODULE_PAYMENT_PAYSON_INV_SELLEREMAIL_TEXT."', '6', '1', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".MODULE_PAYMENT_PAYSON_INV_AGENTID_HEAD."', 'MODULE_PAYMENT_PAYSON_INV_BUSINESS_ID','', '".MODULE_PAYMENT_PAYSON_INV_AGENTID_TEXT."', '6', '2', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".MODULE_PAYMENT_PAYSON_INV_MD5KEY_HEAD."', 'MODULE_PAYMENT_PAYSON_INV_MD5KEY','', '".MODULE_PAYMENT_PAYSON_INV_MD5KEY_TEXT."', '6', '3', now())");

       // $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Terms and conditions', 'MODULE_PAYMENT_PAYSON_INV_TERMS_URL', 'http://', 'URL to Invoice terms and conditions on your site.', '6', '4', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYSON_INV_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '5', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set order status for new invoices', 'MODULE_PAYMENT_PAYSON_INV_ORDER_STATUS_ID', '2', 'New orders should have this status upon creation: <br />(\'Processing\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Send Payson invoice when status is changed to', 'MODULE_PAYMENT_PAYSON_INV_DELIVERED_STATUS_ID', '3', 'Send PDF invoice to user when I change order status to: <br />(\'Shipped\' recommended)', '6', '7', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Cancel Payson invoice when status is changed to', 'MODULE_PAYMENT_PAYSON_INV_CANCELED_STATUS_ID', '1', 'Cancel the Payson invoice when I change order status to: <br />(\'New status Canceled\' recommended)', '6', '8', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Credit Payson invoice when status is changed to', 'MODULE_PAYMENT_PAYSON_INV_CREDIT_STATUS_ID', '1', 'Credit the Payson invoice when I change order status to: <br />(\'New status Credited\' recommended)', '6', '9', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAYSON_INV_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '8', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_PAYMENT_PAYSON_INV_TAX_CLASS', '0', 'Use the following tax class on the payment charge.', '6', '9', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");

        $this->notify('NOTIFY_PAYMENT_PAYSON_INV_INSTALLED');
    }

    /**
     * Remove the module and all its settings
     *
     */
    function remove() {
        global $db;
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        $paysonTable = DB_PREFIX.$paysonDbTablePaytrans;
        $paysonEvents = DB_PREFIX.$paysonDbTableEvents;

        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE\_PAYMENT\_PAYSON\_INV\_%'");
        //delete extra tables if the other module is removed
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYSON_STATUS'");
        if ($check_query->RecordCount() == 0)
        {
            $db->Execute("drop table if exists ".$paysonTable);
            $db->Execute("drop table if exists ".$paysonEvents);
        }
        $this->notify('NOTIFY_PAYMENT_PAYSON_INV_UNINSTALLED');
    }

    /**
     * Internal list of configuration keys used for configuration of the module
     *
     * @return array
     */
    function keys() 
    {
        $keys_list = array(
                       'MODULE_PAYMENT_PAYSON_INV_STATUS',
                       'MODULE_PAYMENT_PAYSON_INV_BUSINESS_ID',
                       'MODULE_PAYMENT_PAYSON_INV_SELLER_EMAIL',
                       'MODULE_PAYMENT_PAYSON_INV_MD5KEY',
                       'MODULE_PAYMENT_PAYSON_INV_ZONE',
                      // 'MODULE_PAYMENT_PAYSON_INV_TERMS_URL',
                       'MODULE_PAYMENT_PAYSON_INV_DELIVERED_STATUS_ID',
                       'MODULE_PAYMENT_PAYSON_INV_ORDER_STATUS_ID',
                       'MODULE_PAYMENT_PAYSON_INV_CANCELED_STATUS_ID',
                       'MODULE_PAYMENT_PAYSON_INV_CREDIT_STATUS_ID',
                       'MODULE_PAYMENT_PAYSON_INV_SORT_ORDER',
                       'MODULE_PAYMENT_PAYSON_INV_TAX_CLASS',
                        );

        return $keys_list;
    }

    function _doStatusUpdate($oID, $newstatus, $comments, $customer_notified, $check_status_fields_orders_status){
        global $db, $messageStack;

        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        $paysonTable = DB_PREFIX.$paysonDbTablePaytrans;
        $paysonEvents = DB_PREFIX.$paysonDbTableEvents;

        //get the trackingid, ,paymenttype, invoicestatus and token for this orders_id
        $res = $db->Execute(" SELECT * FROM ".$paysonTable." WHERE payson_type='INVOICE' AND orders_id=".$oID);
        if( $res->RecordCount()==0 )
        {
            $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_NOSUCHORDER, 'error');
            return;
        }
        $token =$res->fields['token'];
        $trackingId =$res->fields['trackingId'];
        $now = date("Y-m-d H:i:s");  	
        //-----------get values ------------------------------------------
        $userid = MODULE_PAYMENT_PAYSON_INV_BUSINESS_ID;
        $md5key = MODULE_PAYMENT_PAYSON_INV_MD5KEY;
        $moduleversion = $this->paysonModuleVersion;
        $url = $paysonPaymentUpdateURL;
        //----------------------------------------------------------------
        if ($res->fields['invoice_status'] == 'ORDERCREATED')
        {
            //possible to CANCELORDER or SHIPORDER
            switch ($newstatus){     
                case MODULE_PAYMENT_PAYSON_INV_DELIVERED_STATUS_ID:
                    $new_invoice_status = "SHIPPED";
                    //skriv not till events om beg�ran, oavsett
                    $message = "FROM:".$res->fields['invoice_status'].":TO:".$new_invoice_status;
                    $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'SHOPORG_INV_STATUS_CHANGE_REQ',
                             token          = '".$token."',
                             trackingId     = ".$trackingId.",  
                             logged_message = '".$message."',
                             created        = '".$now."' ");

                    $presult = paysonPaymentUpdate($userid, $md5key, $moduleversion, $url, $token, 'SHIPORDER');   
             	     break;

                case MODULE_PAYMENT_PAYSON_INV_CANCELED_STATUS_ID:
                    $new_invoice_status = "ORDERCANCELED";
                    //skriv not till events om beg�ran, oavsett
                    $message = "FROM:".$res->fields['invoice_status'].":TO:".$new_invoice_status;
                    $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'SHOPORG_INV_STATUS_CHANGE_REQ',
                             token          = '".$token."',
                             trackingId     = ".$trackingId.",  
                             logged_message = '".$message."',
                              created        = '".$now."' ");

                    $presult = paysonPaymentUpdate($userid, $md5key, $moduleversion, $url, $token, 'CANCELORDER');
                    break;

                default :
                    $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_CANT_UPDATE, 'notice');
                    return;
            }
        } 
        else if( $res->fields['invoice_status'] == 'SHIPPED') 
        {
            //possible to CREDITORDER
            switch ($newstatus){     
                case MODULE_PAYMENT_PAYSON_INV_CREDIT_STATUS_ID:
                    $new_invoice_status = "CREDITED";
                    //skriv not till events om beg�ran, oavsett
                    $message = "FROM:".$res->fields['invoice_status'].":TO:".$new_invoice_status;
                    $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'SHOPORG_INV_STATUS_CHANGE_REQ',
                             token          = '".$token."',
                             trackingId     = ".$trackingId.",  
                             logged_message = '".$message."',
                             created        = '".$now."' ");

                    $presult = paysonPaymentUpdate($userid, $md5key, $moduleversion, $url, $token, 'CREDITORDER');
                    break;

                default :
                    $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_CANT_UPDATE, 'notice');
                    return;
            }
        } 
        else 
        {
            $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_CANT_UPDATE, 'notice');
            return;
        }
        //gick det bra?
        if (!$presult)
        {
            $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_UPDATED_FAIL.$new_invoice_status, 'error');
            //skriv not till events om misslyckat res
            $message = "FAILED";
            $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'SHOPORG_INV_STATUS_CHANGE_RES',
                             token          = '".$token."',
                             trackingId     = ".$trackingId.",  
                             logged_message = '".$message."',
                             created        = '".$now."' ");

            return;
        }

        //update paytrans with new invoice_status
        $db->Execute(" UPDATE ".$paysonTable." SET invoice_status='".$new_invoice_status."' WHERE orders_id=".$oID);
        //skriv not till events
        $trackingId =$res->fields['trackingId'];
        $now = date("Y-m-d H:i:s");
        //skriv not till events om lyckat res
        $message = "SUCCESS";
        $db->Execute(" INSERT INTO ".$paysonEvents." SET 
                             event_tag      = 'SHOPORG_INV_STATUS_CHANGE_RES',
                             token          = '".$token."',
                             trackingId     = ".$trackingId.",  
                             logged_message = '".$message."',
                             created        = '".$now."' ");

        //meddelande
        $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_UPDATED_OK.$new_invoice_status, 'success');
        return true;
    } 

}