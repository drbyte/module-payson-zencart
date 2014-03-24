<?php

//error_reporting(E_ALL);
// ini_set("display_errors", 1); 
/**
 * payson.php payment module class for new Payson API
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
class payson extends base {

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
    var $invoiceEnabled;
    var $directEnabled;
    var $isInvoicePayment;
    var $tableForPaysonData;
    var $showReceiptPage;

    // var $testmode;

    /**
     * constructor
     *
     * @return payson
     */
    function payson() {

        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        global $order;
        $this->code = 'payson';
        $this->codeVersion = PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR;
        $this->paysonModuleVersion = 'PAYSON-ZENCART-2.1';

        $this->tableForPaysonData = DB_PREFIX . $paysonDbTablePaytrans;

        $this->invoiceEnabled = false;
        $this->directEnabled = false;



   

        if (IS_ADMIN_FLAG === true) {
            $this->title = MODULE_PAYMENT_PAYSON_TEXT_ADMIN_TITLE; // Payment Module title in Admin
        } else {
            $this->title = MODULE_PAYMENT_PAYSON_TEXT_CATALOG_TITLE; // Payment Module title in Catalog
        }

        $this->description = MODULE_PAYMENT_PAYSON_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_PAYSON_SORT_ORDER;
        $this->enabled = ((MODULE_PAYMENT_PAYSON_STATUS == 'True') ? true : false);

        if ((int) MODULE_PAYMENT_PAYSON_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PAYSON_ORDER_STATUS_ID;
        }
        if (MODULE_PAYMENT_PAYSON_RECEIPT_PAGE == 'True') {
            $this->showReceiptPage = 'true';
        } elseif (MODULE_PAYMENT_PAYSON_RECEIPT_PAGE == 'False') {
            $this->showReceiptPage = 'false';
        }
        if (MODULE_PAYMENT_PAYSON_INVOICE_ENABLED == "True") {
            $this->invoiceEnabled = true;

            if (isset($_REQUEST['payson-method']) && $_REQUEST['payson-method'] == "payson-invoice") {
                $this->isInvoicePayment = true;
                $_SESSION['paysonIsInvoice'] = true;
            } else {
                $_SESSION['paysonIsInvoice'] = false;
            }
        }



        $paysonDirectSetting = MODULE_PAYMENT_PAYSON_PAYMETHOD;
        $paysonDirectMethodsSelected = MODULE_PAYMENT_PAYSON_PAYMETHOD_ITEMS;

        if ($paysonDirectSetting != "Disabled") {

            $this->directEnabled = true;

            if ($paysonDirectSetting == "Check boxes below" && $paysonDirectMethodsSelected == "--none--")
                $this->directEnabled = false;
        }

        if (is_object($order)) {
            $this->update_status();
        }

        if (!in_array(strtoupper($_SESSION['currency']), $paysonCurrenciesSupported) || $_SESSION['currency'] == '') {
            $this->enabled = false;
            $this->invoiceEnabled = false;
        } else if (strtoupper($_SESSION["currency"] != "SEK")) {
            $this->invoiceEnabled = false;
        }

        if (!($this->directEnabled || $this->invoiceEnabled ))
            $this->enabled = false;

        $this->form_action_url = $paysonBrowserPostURL;
    }

    function getApplicationVersion() {
        return $this->paysonModuleVersion;
    }

    /**
     * Displays payment method name along with Credit Card Information Submission Fields (if any) on the Checkout Payment Page
     *
     * @return array
     */
    function selection() {

        global $order;
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        $fieldsArray = array();

        $currentPaymentMethod = $_SESSION['payment'];

        $isInvoiceSelected = $currentPaymentMethod == "payson" && $this->isInvoicePayment;

        if ($this->directEnabled)
            $fieldsArray[] = array("title" => MODULE_PAYMENT_PAYSON_TEXT_CATALOG_LOGO, "field" => zen_draw_radio_field("payson-method", 'payson-card-bank', !$isInvoiceSelected, 'id="payson-card-bank" onclick="document.getElementById(\'pmt-payson\').checked = checked;"'), 'tag' => "payson-card-bank");

        $invoiceAmountLimit = $paysonInvoiceMinimalOrderValue;

        $useInvoice = false;

        if ($this->invoiceEnabled && $order->info['total'] * $order->info['currency_value'] > $invoiceAmountLimit) {
            $fieldsArray[] = array("title" => MODULE_PAYMENT_PAYSON_INV_TEXT_CATALOG_LOGO, "field" => zen_draw_radio_field("payson-method", 'payson-invoice', $isInvoiceSelected, 'id="payson-invoice" onclick="document.getElementById(\'pmt-payson\').checked = checked;"'), 'tag' => "payson-invoice");
            $useInvoice = true;
        }
        if (sizeof($fieldsArray) == 1) {
            $moduleData = array();

            $moduleData = array('id' => $this->code, 'module' => $fieldsArray[0]['title']);

            if ($useInvoice) {

                $invoiceModuleFieldData = array();
                $invoiceModuleFieldData[] = array('field' => zen_draw_hidden_field('payson-method', 'payson-invoice'));

                $moduleData['fields'] = $invoiceModuleFieldData;
            }

            return $moduleData;
        }

        $methods = array('id' => $this->code,
            'module' => "Payson",
            'fields' => $fieldsArray
        );


        return $methods;
    }

    /**
     * JS validation which does error-checking of data-entry if this module is selected for use
     * (Number, Owner, and CVV Lengths)
     *
     * @return string
     */
    function javascript_validation() {
        return false;
    }

    /**
     * calculate zone matches and flag settings to determine whether this module should display to customers or not
     *
     */
    function update_status() {
        global $order, $db;

        if (($this->enabled == true) && ((int) MODULE_PAYMENT_PAYSON_ZONE > 0)) {
            $check_flag = false;
            $check_query = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYSON_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");

            while (!$check_query->EOF) {
                if ($check_query->fields['zone_id'] < 1) {
                    $check_flag = true;
                    break;
                } elseif ($check_query->fields['zone_id'] == $order->billing['zone_id']) {
                    $check_flag = true;
                    break;
                }
                $check_query->MoveNext();
            }

            if ($check_flag == false) {
                $this->enabled = false;
            }
        }
    }

    /**
     * Normally evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
     * Since payson module is not collecting info, it simply skips this step.
     *
     * @return boolean
     */

    function pre_confirmation_check() {

        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        global $db, $order, $currencies, $currency, $messageStack, $Receipt;


        $now = date("Y-m-d H:i:s");
        $localeCode = $this->getLanguageCodeForPayson();
        $currencyCode = strtoupper($_SESSION['currency']);
        $trackingId = time();

        $postData = $this->getStaticPostData();

        $postData['localeCode'] = $localeCode;
        $postData['currencyCode'] = $currencyCode;
        $postData['memo'] = sprintf(MODULE_PAYMENT_PAYSON_PURCHASE_DESCRIPTION_TITLE, $trackingId);
        $postData['trackingId'] = $trackingId;
        $postData['senderEmail'] = $order->customer['email_address'];
        $postData['senderFirstName'] = $order->customer['firstname'];
        $postData['senderLastName'] = $order->customer['lastname'];


        $totalAmount = 0;
        $orderItemList = array();

        $this->setProductOrderList($order, $orderItemList, $totalAmount);
        $this->setCouponOrderList($order, $orderItemList, $totalAmount);

        $this->setShippingOrderList($order, $orderItemList, $totalAmount);
        if ($this->isInvoicePayment) {
            $this->addInvoiceFeeToPaysonData($order, $totalAmount, $postData);
        }



        $postData['amount'] = $totalAmount;

        $db->Execute(" INSERT INTO " . $this->tableForPaysonData . " SET 
                             trackingId     = " . $trackingId . ",
                             customers_id   = " . (int) $_SESSION['customer_id'] . ",
                             amount         = " . $totalAmount . ",
                             currency       = '" . $currencyCode . "',
                             lang           = '" . $localeCode . "',
                             created        = '" . $now . "' ");
        $defaults['feesPayer'] = $paysonFeesPayerSupported[1];
        $Receipts['showReceiptPage'] = $this->showReceiptPage;


        if ($this->isInvoicePayment) {
            $postData['fundingList'] = 'INVOICE';
        } else if (MODULE_PAYMENT_PAYSON_PAYMETHOD == 'ALL') {
            $postData['fundingList'] = 'ALL';
        } else {
            $postData['fundingList'] = explode(",", MODULE_PAYMENT_PAYSON_PAYMETHOD_ITEMS);
        }

        $paysonTokenResponse = paysonTokenRequest(MODULE_PAYMENT_PAYSON_BUSINESS_ID, MODULE_PAYMENT_PAYSON_MD5KEY, $this->paysonModuleVersion, $paysonTokenRequestURL, $postData, $orderItemList, $defaults, $Receipts, true);
        //2 validate response
        $paysonTokenResponseValid = paysonTokenResponseValidate($paysonTokenResponse);

        if ($paysonTokenResponseValid == true) {
            $paysonToken = paysonGetToken($paysonTokenResponse);

            //uppdate table payson_paytrans
            $db->Execute(" UPDATE " . $this->tableForPaysonData . " SET 
		                token              = '" . $paysonToken['TOKEN'] . "',
                                curl_ack           = '" . $paysonToken['ack'] . "',
                                curl_timestamp     = '" . $paysonToken['timestamp'] . "',
                                curl_correlationId = '" . $paysonToken['correlationId'] . "',
                                session_data = '" . session_encode() . "'
				WHERE trackingId   = " . $trackingId);

            //$_SESSION['PAYSON_TOKEN'] = $paysonToken['TOKEN'];
            $this->form_action_url = $paysonBrowserPostURL . "?token=" . $paysonToken['TOKEN'];
        } else {
            //bad response, redirect to payment selection page
            zen_mail(STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, 'Log from Payson Module::paysonTokenRequest', "Data: \r\n" . str_replace("&", "\r\n", http_build_query($postData)) .
                    "\r\n\r\nOrder Items: \r\n" . str_replace("&", "\r\n", http_build_query($orderItemList)) .
                    "\r\n\r\n\r\n Response: \r\n" . str_replace("&", "\r\n", $paysonTokenResponse), STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, "", 'debug');

            $badResponse = paysonGetBadResponse($paysonTokenResponse);

            //update table payson_paytrans
            $db->Execute(" UPDATE " . $this->tableForPaysonData . " SET curl_ack = '" . $badResponse['ack'] . "',
                                     curl_timestamp     = '" . $badResponse['timestamp'] . "',
                                     curl_correlationId = '" . $badResponse['correlationId'] . "',
                                     curl_errorId       = '" . $badResponse['errorId'] . "',
                                     curl_message       = '" . $badResponse['message'] . "'
                                     WHERE trackingId = " . $trackingId);

            $messageStack->add_session("checkout_payment", MODULE_PAYMENT_PAYSON_GENERIC_ERROR);

            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, 'payson-method=' . $_POST['payson-method'], 'SSL', true, false));
        }
        return false;
    }

    /**
     * Display Credit Card Information on the Checkout Confirmation Page
     * Since none is collected for payson before forwarding to payson site, this is skipped
     *
     * @return boolean
     */
    function confirmation() {
        return false;
    }

    /**
     * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
     * This sends the data to the payment gateway for processing.
     * (These are hidden fields on the checkout confirmation page)
     *
     * @return string
     */
    function process_button() {
        $process_button_string = '';
        return $process_button_string;
    }

    /**
     * Store transaction info to the order and process any results that come back from the payment gateway
     */
    function before_process() {

        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        global $db, $order;

        $token = $this->getDBSafeToken();

        if (strlen($token) < 20) {
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        $orderData = $this->getOrderDataFromToken($token);

        $paymentDetailsResponse = paysonGetPaymentDetails(MODULE_PAYMENT_PAYSON_BUSINESS_ID, MODULE_PAYMENT_PAYSON_MD5KEY, $this->paysonModuleVersion, $paysonPaymentDetailsURL, $token);

        $paymentDetailsData = parsePaymentDetailsResponse($paymentDetailsResponse);

        switch ($paymentDetailsData['status']) {
            case 'COMPLETED':
            case 'PENDING':
                $db->Execute(" UPDATE " . $this->tableForPaysonData . " SET 
                                  payson_status='" . $paymentDetailsData['status'] . "',
                                  payson_type='" . $paymentDetailsData['type'] . "',
                                  payson_reference=" . $paymentDetailsData['purchaseId'] . ",
                                  invoice_status='" . $paymentDetailsData['invoiceStatus'] . "'
                                  WHERE trackingId=" . $db->prepare_input($orderData['trackingId']));
                break;

            default :
                $db->Execute(" UPDATE " . $this->tableForPaysonData . " SET payson_status='" . $paymentDetailsData['status'] . "' WHERE token='" . $token . "'");
                zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        //since this in an invoice, we need to force update shippingadress
        if ($this->isInvoicePayment) {
            $this->updateShippingAdressForInvoicePayment($paymentDetailsData, $order);
        }

        $this->appendCommentsToOrder($order, $paymentDetailsData['purchaseId']);


        return true;
    }

    function after_order_create($zf_order_id) {
        global $db;

        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        $db->Execute("UPDATE " . $this->tableForPaysonData . " SET orders_id=" . $zf_order_id . " WHERE token='" . $this->getDBSafeToken() . "'");
    }

    /**
     * Post-processing activities
     * When the order returns from the processor, if PDT was successful, this stores the results in order-status-history and logs data for subsequent reference
     *
     * @return boolean
     */
    function after_process() {
        return true;
    }

    /**
     * Checks referrer
     *
     * @param string $zf_domain
     * @return boolean
     */
    function check_referrer($zf_domain) {
        return true;
    }

    /**
     * Used to display error message details
     *
     * @return boolean
     */
    function output_error() {
        return false;
    }

    /**
     * Check to see whether module is talled
     *
     * @return boolean
     */
    function check() {
        global $db;

        if (!isset($this->_check)) {
            $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PAYSON_STATUS'");
            $this->_check = $check_query->RecordCount();
        }

        return $this->_check;
    }

    /**
     * Install the payment module and its configuration settings
     *
     */
    function install() {
        global $db, $messageStack;
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        if (!isset($_SESSION['language'])) {
            include( DIR_FS_CATALOG . 'includes/languages/english/modules/payment/payson.php');
        } else {
            include( DIR_FS_CATALOG . 'includes/languages/' . $_SESSION['language'] . '/modules/payment/payson.php');
        }


        $db->Execute(paysonCreatePaytransTableQuery($this->tableForPaysonData));

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_PAYSON_ENABLE_TEXT . "', 'MODULE_PAYMENT_PAYSON_STATUS', 'True', '" . MODULE_PAYMENT_PAYSON_ACCEPT_TEXT . "', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_TEST_PAGE_TEXT . "', 'MODULE_PAYMENT_PAYSON_TEST_PAGES', 'True', '" . MODULE_PAYMENT_TEST_PAGE_HEAD . "', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        
        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_PAYSON_INVOICE_ENABLED_HEAD . "', 'MODULE_PAYMENT_PAYSON_INVOICE_ENABLED', 'False', '" . MODULE_PAYMENT_PAYSON_INVOICE_ENABLED_TEXT . "', '6', '2', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_PAYSON_AGENTID_HEAD . "', 'MODULE_PAYMENT_PAYSON_BUSINESS_ID','', '" . MODULE_PAYMENT_PAYSON_AGENTID_TEXT . "', '6', '3', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_PAYSON_SELLEREMAIL_HEAD . "', 'MODULE_PAYMENT_PAYSON_SELLER_EMAIL','" . STORE_OWNER_EMAIL_ADDRESS . "', '" . MODULE_PAYMENT_PAYSON_SELLEREMAIL_TEXT . "', '6', '3', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_PAYSON_MD5KEY_HEAD . "', 'MODULE_PAYMENT_PAYSON_MD5KEY','', '" . MODULE_PAYMENT_PAYSON_MD5KEY_TEXT . "', '6', '3', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_PAYSON_PAYMETHOD_HEAD . "', 'MODULE_PAYMENT_PAYSON_PAYMETHOD', 'ALL', '" . MODULE_PAYMENT_PAYSON_PAYMETHOD_TEXT . "', '6', '20', 'zen_cfg_select_option(array(\'Disabled\',\'ALL\', \'Check boxes below\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_PAYSON_PAYMETHOD_ITEMS_HEAD . "', 'MODULE_PAYMENT_PAYSON_PAYMETHOD_ITEMS', 'CREDITCARD, BANK', '" . MODULE_PAYMENT_PAYSON_PAYMETHOD_ITEMS_TEXT . "', '6', '20', 'zen_cfg_select_multioption(array(\'CREDITCARD\',\'BANK\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_PAYSON_GUARANTEE_OFFERED_HEAD . "', 'MODULE_PAYMENT_PAYSON_GUARANTEE_OFFERED', 'NO', '" . MODULE_PAYMENT_PAYSON_GUARANTEE_OFFERED_TEXT . "', '6', '20', 'zen_cfg_select_option(array(\'OPTIONAL\',\'REQUIRED\',\'NO\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('" . MODULE_PAYMENT_PAYSON_CUSTOM_HEAD . "', 'MODULE_PAYMENT_PAYSON_CUSTOM','', '" . MODULE_PAYMENT_PAYSON_CUSTOM_TEXT . "', '6', '3', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYSON_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '4', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Pending Notification Status', 'MODULE_PAYMENT_PAYSON_PROCESSING_STATUS_ID', '" . DEFAULT_ORDERS_STATUS_ID . "', 'Set the status of orders made with this payment module that are not yet completed to this value<br />(\'Pending\' recommended)', '6', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set Order Status', 'MODULE_PAYMENT_PAYSON_ORDER_STATUS_ID', '2', 'Set the status of orders made with this payment module that have completed payment to this value<br />(\'Processing\' recommended)', '6', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Send Payson invoice when status is changed to', 'MODULE_PAYMENT_PAYSON_INV_DELIVERED_STATUS_ID', '3', 'Send PDF invoice to user when I change order status to: <br />(\'Shipped\' recommended)', '6', '7', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_PAYSON_INVOICE_MANAGE_BACKEND_HEAD . "', 'MODULE_PAYMENT_PAYSON_INVOICE_MANAGE_BACKEND', 'False', '" . MODULE_PAYMENT_PAYSON_INVOICE_MANAGE_BACKEND_TEXT . "', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Cancel Payson invoice when status is changed to', 'MODULE_PAYMENT_PAYSON_INV_CANCELED_STATUS_ID', '1', 'Cancel the Payson invoice when I change order status to: <br />(\'New status Canceled\' recommended)', '6', '8', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Credit Payson invoice when status is changed to ', 'MODULE_PAYMENT_PAYSON_INV_CREDIT_STATUS_ID', '1', 'Credit the Payson invoice when I change order status to: <br />(\'New status Credited\' recommended)', '6', '9', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAYSON_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '8', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('" . MODULE_PAYMENT_RECEIPT_PAGE_TEXT . "', 'MODULE_PAYMENT_PAYSON_RECEIPT_PAGE', 'True', '" . MODULE_PAYMENT_RECEIPT_PAGE_HEAD . "', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        

        $this->notify('NOTIFY_PAYMENT_PAYSON_INSTALLED');
    }

    /**
     * Remove the module and all its settings
     *
     */
    function remove() {
        global $db;
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        $paysonEvents = DB_PREFIX . $paysonDbTableEvents;

        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key LIKE 'MODULE\_PAYMENT\_PAYSON\_%'");

        $db->Execute("drop table if exists " . $this->tableForPaysonData);
        $db->Execute("drop table if exists " . $paysonEvents);

        $this->notify('NOTIFY_PAYMENT_PAYSON_UNINSTALLED');
    }

    /**
     * Internal list of configuration keys used for configuration of the module
     *
     * @return array
     */
    function keys() {
        $keys_list = array(
            'MODULE_PAYMENT_PAYSON_SORT_ORDER',
            'MODULE_PAYMENT_PAYSON_TEST_PAGES',
            'MODULE_PAYMENT_PAYSON_STATUS',
            'MODULE_PAYMENT_PAYSON_BUSINESS_ID',
            'MODULE_PAYMENT_PAYSON_SELLER_EMAIL',
            'MODULE_PAYMENT_PAYSON_MD5KEY',
            'MODULE_PAYMENT_PAYSON_PAYMETHOD',
            'MODULE_PAYMENT_PAYSON_PAYMETHOD_ITEMS',
            'MODULE_PAYMENT_PAYSON_INVOICE_ENABLED',
            'MODULE_PAYMENT_PAYSON_CUSTOM',
            'MODULE_PAYMENT_PAYSON_ZONE',
            'MODULE_PAYMENT_PAYSON_PROCESSING_STATUS_ID',
            'MODULE_PAYMENT_PAYSON_ORDER_STATUS_ID',             
            'MODULE_PAYMENT_PAYSON_INVOICE_MANAGE_BACKEND',
            'MODULE_PAYMENT_PAYSON_INV_DELIVERED_STATUS_ID',
            'MODULE_PAYMENT_PAYSON_INV_CANCELED_STATUS_ID',
            'MODULE_PAYMENT_PAYSON_INV_CREDIT_STATUS_ID',
            'MODULE_PAYMENT_PAYSON_RECEIPT_PAGE',
            
        );

        return $keys_list;
    }

    private function updateShippingAdressForInvoicePayment($paymentDetailsData, $order) {
        $order->delivery['firstname'] = $paymentDetailsData['name'];
        $order->delivery['lastname'] = '';
        $order->delivery['street_address'] = $paymentDetailsData['streetAddress'];
        $order->delivery['city'] = $paymentDetailsData['city'];
        $order->delivery['postcode'] = $paymentDetailsData['postalCode'];
        $order->delivery['country']['title'] = $paymentDetailsData['country'];
    }

    private function appendCommentsToOrder($order, $purchaseId) {
        $add_comments = MODULE_PAYMENT_PAYSON_TEXT_PAYSONREF;
        $add_comments .= ": ";
        $add_comments .= $purchaseId;
        $new_comments = $order->info['comments'];
        $new_comments .= $add_comments;
        $order->info['comments'] = $new_comments;
    }

    private function getOrderDataFromToken($token) {

        global $db;

        $res = $db->Execute(" SELECT * FROM " . $this->tableForPaysonData . " WHERE token='" . $token . "'");

        if ($res->RecordCount() == 0) {
            //token not found
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }

        // If we have a order id then this order has already been created by a IPN call
        if ($res->fields['orders_id']) {
            $_SESSION['cart']->reset(true);
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, (isset($_GET['action']) && $_GET['action'] == 'confirm' ? 'action=confirm' : ''), 'SSL'));
        }
        return $res->fields;
    }

    private function getDBSafeToken() {
        if (isset($_GET['TOKEN']))
            $token = $_GET['TOKEN'];
        else
            $token = $_SESSION['paysonToken'];

        $token = zen_db_prepare_input($token);

        return $token;
    }

    private function setProductOrderList($order, &$orderItemList, &$totalAmount) {

        $n = sizeof($order->products);

        // First all order items
        for ($i = 0; $i < $n; $i++) {
            $price_without_tax = $order->products[$i]['final_price'] * $order->info['currency_value'];
            $taxPercentage = ($order->products[$i]['tax'] / 100);

            $orderItemList[] = array(
                'description' => urlencode($order->products[$i]['name']),
                'sku' => zen_get_prid($order->products[$i]['id']),
                'quantity' => $order->products[$i]['qty'],
                'unitPrice' => number_format($price_without_tax, 2, '.', ''),
                'taxPercentage' => $taxPercentage,
            );

            $totalAmount += number_format(number_format($price_without_tax, 2, '.', '') * (1 + $taxPercentage) * $order->products[$i]['qty'], 2, '.', '');
        }
    }

    private function setCouponOrderList($order, &$orderItemList, &$totalAmount) {
       global $db;
        $couponAmount = 0;

        $coupon_id = $_SESSION['cc_id'];
        if (isset($_SESSION['cc_id'])) {
            $coupon = $db->Execute('SELECT * FROM ' . TABLE_COUPONS . ' WHERE coupon_id=' . $coupon_id . " AND coupon_active='Y' ");

            $couponAmount = $coupon->fields['coupon_amount'];


            switch ($coupon->fields['coupon_type']) {
                //Percental discount coupon
                case 'P':
                    $couponAmount = $totalAmount * ($coupon->fields['coupon_amount'] / 100);
                    break;
                // Fixed value discount coupon
                case 'F':
                    $couponAmount = $coupon->fields['coupon_amount'] * $order->info['currency_value'];
                    break;
                //Free shipping discount coupon
                case 'S':
                    $coupon_Amount = $order->info['shipping_cost'] * (-1);
                    break;
            }
        }

        if ($couponAmount > 0) {
            $couponDescQuery = $db->Execute("SELECT * FROM " . TABLE_COUPONS_DESCRIPTION . " WHERE coupon_id= " . $coupon_id . " AND language_id=" . $_SESSION['languages_id']);

            $couponDescription = $couponDescQuery->fields['coupon_description'] ? : $couponDescQuery->fields['coupon_name'];
            $taxPercentage = 0;



            $price_without_tax = ($couponAmount / (1 + $taxPercentage));

            $orderItemList[] = array(
                'description' => urlencode($couponDescription),
                'sku' => $coupon_id,
                'quantity' => 1,
                'unitPrice' => number_format(-1 * $price_without_tax, 2, '.', ''),
                'taxPercentage' => number_format($taxPercentage, 2, '.', ''),
            );
            $totalAmount -= $couponAmount;
        }
    }

    private function setShippingOrderList($order, &$orderItemList, &$totalAmount) {



        if (zen_not_null($order->info['shipping_method'])) {
            $shipping_module = substr($_SESSION['shipping']['id'], 0, strpos($_SESSION['shipping']['id'], '_'));
            $shipping_tax_percentage = zen_get_tax_rate($GLOBALS[$shipping_module]->tax_class, $order->billing['country']['id'], $order->billing['zone_id']) / 100;
            $shipping_price_without_tax = $order->info['shipping_cost'] * $order->info['currency_value'];

            if (DISPLAY_PRICE_WITH_TAX == 'true') {
                $shipping_price_without_tax = ($shipping_price_without_tax / (1 + $shipping_tax_percentage));
            }

            $orderItemList[] = array(
                'description' => urlencode($order->info['shipping_method']),
                'sku' => 9998,
                'quantity' => 1,
                'unitPrice' => number_format($shipping_price_without_tax, 2, '.', ''),
                'taxPercentage' => $shipping_tax_percentage,
            );

            $totalAmount += number_format($shipping_price_without_tax * (1 + $shipping_tax_percentage), 2, '.', '');
        }
    }

    private function addInvoiceFeeToPaysonData($order, &$totalAmount, &$dataToSendToPayson) {

        global $db;
        $fee = 0;
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYSON_INV_FEE_STATUS'");
        if ($check_query->RecordCount() == 1) {
            $usefee = $check_query->fields['configuration_value'];
            if ($usefee) {
                $fee_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYSON_INV_FEE_FEE'");
                $fee = $fee_query->fields['configuration_value'] * $order->info['currency_value'];

                $fee_tax_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYSON_INV_FEE_TAX_CLASS'");
                $fee_tax_class = $fee_tax_query->fields['configuration_value'];

                $taxPercentage = zen_get_tax_rate($fee_tax_class, $order->billing['country']['id'], $order->billing['zone_id']) / 100;

                $fee = $fee * (1 + $taxPercentage);
            }
        }

        $formattedFee = number_format($fee, 2, '.', ',');

        if ($formattedFee > 0) {
            $dataToSendToPayson['invoiceFee'] = $formattedFee;
            $totalAmount += $formattedFee;
        }
    }

    private function getStaticPostData() {
        $postData = array();

        $postData['returnUrl'] = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true, false) . '&payson-method=' . ($this->isInvoicePayment ? 'payson-invoice' : 'payson-card-bank');
        $postData['cancelUrl'] = zen_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true, false) . '&payson-method=' . ($this->isInvoicePayment ? 'payson-invoice' : 'payson-card-bank');
        $postData['ipnNotificationUrl'] = zen_href_link('ipn_payson.php?mode=payson', '', 'SSL', true, false, true, true) . '&payson-method=' . ($this->isInvoicePayment ? 'payson-invoice' : 'payson-card-bank');
        $postData['custom'] = MODULE_PAYMENT_PAYSON_CUSTOM;
        $postData['guaranteeOffered'] = MODULE_PAYMENT_PAYSON_GUARANTEE_OFFERED;
        $postData['receiverEmail'] = MODULE_PAYMENT_PAYSON_SELLER_EMAIL;

        return $postData;
    }

    /**
     * Determine the language to use inc fallback
     */
    private function getLanguageCodeForPayson() {
        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');
        if (in_array(strtoupper($_SESSION['languages_code']), $paysonLanguagesSupported)) {
            //some of the supported languages
            $lang_code = strtoupper($_SESSION['languages_code']);
        } else if (strtoupper($_SESSION['languages_code']) == 'SE') {
            //possible wrong notation for swedish
            $lang_code = 'SV';
        } else {
            //could be anything, use english at Payson
            $lang_code = 'EN';
        }
        return $lang_code;
    }

    function _doStatusUpdate($oID, $newstatus, $comments, $customer_notified, $check_status_fields_orders_status) {
        global $db, $messageStack;

        $invoiceHandlingEnabled = MODULE_PAYMENT_PAYSON_INVOICE_MANAGE_BACKEND;
        if ($invoiceHandlingEnabled === "False")
            return;

        include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

        //get the trackingid, ,paymenttype, invoicestatus and token for this orders_id
        $res = $db->Execute(" SELECT * FROM " . $this->tableForPaysonData . " WHERE payson_type='INVOICE' AND orders_id=" . $oID);
        if ($res->RecordCount() == 0) {
            $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_NOSUCHORDER, 'error');
            return;
        }
        $token = $res->fields['token'];
        $trackingId = $res->fields['trackingId'];
        $now = date("Y-m-d H:i:s");

        $userid = MODULE_PAYMENT_PAYSON_BUSINESS_ID;
        $md5key = MODULE_PAYMENT_PAYSON_MD5KEY;
        $moduleversion = $this->paysonModuleVersion;
        $url = $paysonPaymentUpdateURL;

        if ($res->fields['invoice_status'] == 'ORDERCREATED') {
            switch ($newstatus) {
                case MODULE_PAYMENT_PAYSON_INV_DELIVERED_STATUS_ID:
                    $new_invoice_status = "SHIPPED";
                    $presult = paysonPaymentUpdate($userid, $md5key, $moduleversion, $url, $token, 'SHIPORDER');
                    break;

                case MODULE_PAYMENT_PAYSON_INV_CANCELED_STATUS_ID:
                    $new_invoice_status = "ORDERCANCELED";
                    $presult = paysonPaymentUpdate($userid, $md5key, $moduleversion, $url, $token, 'CANCELORDER');
                    break;

                default :
                    $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_CANT_UPDATE, 'notice');
                    return;
            }
        } else if ($res->fields['invoice_status'] == 'SHIPPED') {

            switch ($newstatus) {
                case MODULE_PAYMENT_PAYSON_INV_CREDIT_STATUS_ID:
                    $new_invoice_status = "CREDITED";
                    $presult = paysonPaymentUpdate($userid, $md5key, $moduleversion, $url, $token, 'CREDITORDER');
                    break;

                default :
                    $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_CANT_UPDATE, 'notice');
                    return;
            }
        } else {
            $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_CANT_UPDATE, 'notice');
            return;
        }
        //gick det bra?
        if (!$presult) {
            $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_UPDATED_FAIL . $new_invoice_status, 'error');
            return;
        }
        //update paytrans with new invoice_status
        $db->Execute(" UPDATE " . $this->tableForPaysonData . " SET invoice_status='" . $new_invoice_status . "' WHERE orders_id=" . $oID);

        $messageStack->add_session(MODULE_PAYMENT_PAYSON_INV_INVSTATUS_UPDATED_OK . $new_invoice_status, 'success');
        return true;
    }

}
