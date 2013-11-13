<?php

$req['input'] = file_get_contents('php://input');

require_once('includes/application_top.php');
require_once( DIR_FS_CATALOG . 'includes/modules/payment/payson.php');
require_once( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');


global $db, $zco_notifier;

if ($_GET['mode'] == "payson") {
    $payment_class = new payson;
    $userid = MODULE_PAYMENT_PAYSON_BUSINESS_ID;
    $md5key = MODULE_PAYMENT_PAYSON_MD5KEY;
} else {
    die("Wrong mode was sent");
}

$moduleversion = $payment_class->getApplicationVersion();

$token = $_POST['token'];

$trackingId = zen_db_prepare_input($_POST['trackingId']);

if(!isset($trackingId))
    die("Tracking id has to be set");

$validationResult = paysonValidateIpnMessage($userid, $md5key, $moduleversion, $paysonIpnMessageValidationURL, $req['input']);

if ($validationResult != "VERIFIED")
    die("Invalid response from Payson");

$res = $db->Execute("SELECT orders_id, session_data FROM " . $payment_class->tableForPaysonData . " WHERE trackingId = " . $trackingId);

$orderId = $res->fields['orders_id'];

if ($orderId)
{
    die("This order has already been completed");
}

// Restore session data used during checkout
session_decode($res->fields['session_data']);

$_SESSION["paysonToken"] = $token;

$checkoutProcessFile = DIR_WS_MODULES . FILENAME_CHECKOUT_PROCESS . ".php";

include($checkoutProcessFile);
?>