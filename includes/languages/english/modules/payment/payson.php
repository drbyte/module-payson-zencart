<?php

/**
 * @package languageDefines
 */
//include_once((IS_ADMIN_FLAG === true ? DIR_FS_CATALOG_MODULES : DIR_WS_MODULES) . 'payment/payson/def.payson.php');
include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');

$local_lang = "EN";

define('MODULE_PAYMENT_PAYSON_TEXT_ADMIN_TITLE', $paysonAdmin[$local_lang]['text_admin_title']);
define('MODULE_PAYMENT_PAYSON_TEXT_CATALOG_TITLE', $paysonAdmin[$local_lang]['text_catalog_title']);

if (IS_ADMIN_FLAG === true) {
    $temp = $paysonAdmin[$local_lang]['config_instruction1'];
    if (defined('MODULE_PAYMENT_PAYSONSTD_STATUS')) {
        $temp.= '';
    } else {
        $temp.= $paysonAdmin[$local_lang]['config_instruction2'];
    }
    $temp.= $paysonAdmin[$local_lang]['config_instruction3'];

    define('MODULE_PAYMENT_PAYSON_TEXT_DESCRIPTION', $temp);
    define('MODULE_PAYMENT_PAYSON_ACCEPT_TEXT', $paysonAdmin[$local_lang]['accept_payson']);
    define('MODULE_PAYMENT_PAYSON_ENABLE_TEXT', $paysonAdmin[$local_lang]['enable_payson']);

    define('MODULE_PAYMENT_PAYSON_INVOICE_ENABLED_HEAD', $paysonAdmin[$local_lang]['enable_payson_invoice_head']);
    define('MODULE_PAYMENT_PAYSON_INVOICE_ENABLED_TEXT', $paysonAdmin[$local_lang]['enable_payson_invoice_text']);

    define('MODULE_PAYMENT_PAYSON_AGENTID_HEAD', $paysonAdmin[$local_lang]['agentid_head']);
    define('MODULE_PAYMENT_PAYSON_AGENTID_TEXT', $paysonAdmin[$local_lang]['agentid_text']);

    define('MODULE_PAYMENT_PAYSON_SELLEREMAIL_HEAD', $paysonAdmin[$local_lang]['selleremail_head']);
    define('MODULE_PAYMENT_PAYSON_SELLEREMAIL_TEXT', $paysonAdmin[$local_lang]['selleremail_text']);

    define('MODULE_PAYMENT_PAYSON_MD5KEY_HEAD', $paysonAdmin[$local_lang]['md5key_head']);
    define('MODULE_PAYMENT_PAYSON_MD5KEY_TEXT', $paysonAdmin[$local_lang]['md5key_text']);

    define('MODULE_PAYMENT_PAYSON_PAYMETHOD_HEAD', $paysonAdmin[$local_lang]['paymentmethods_head']);
    define('MODULE_PAYMENT_PAYSON_PAYMETHOD_TEXT', $paysonAdmin[$local_lang]['paymentmethods_text']);

    define('MODULE_PAYMENT_PAYSON_PAYMETHOD_ITEMS_HEAD', $paysonAdmin[$local_lang]['paymethoditems_head']);
    define('MODULE_PAYMENT_PAYSON_PAYMETHOD_ITEMS_TEXT', $paysonAdmin[$local_lang]['paymethoditems_text']);

    define('MODULE_PAYMENT_PAYSON_GUARANTEE_OFFERED_HEAD', $paysonAdmin[$local_lang]['paysonguarantee_head']);
    define('MODULE_PAYMENT_PAYSON_GUARANTEE_OFFERED_TEXT', $paysonAdmin[$local_lang]['paysonguarantee_text']);

    define('MODULE_PAYMENT_PAYSON_CUSTOM_HEAD', $paysonAdmin[$local_lang]['custommess_head']);
    define('MODULE_PAYMENT_PAYSON_CUSTOM_TEXT', $paysonAdmin[$local_lang]['custommess_text']);

    define('MODULE_PAYMENT_PAYSON_INVOICE_MANAGE_BACKEND_HEAD', $paysonAdmin[$local_lang]['manage_invoice_from_store_head']);
    define('MODULE_PAYMENT_PAYSON_INVOICE_MANAGE_BACKEND_TEXT', $paysonAdmin[$local_lang]['manage_invoice_from_store_text']);
} else {
    define('MODULE_PAYMENT_PAYSON_TEXT_DESCRIPTION', '<strong>' . $paysonAdmin[$local_lang]['text_catalog_title'] . '</strong>');
}

define('MODULE_PAYMENT_PAYSON_MARK_BUTTON_IMG', $paysonShop[$local_lang]['mark_button_img']);
define('MODULE_PAYMENT_PAYSON_MARK_BUTTON_ALT', $paysonShop[$local_lang]['check_out_w_payson']);

define('MODULE_PAYMENT_PAYSON_TEXT_CATALOG_LOGO', '<img src="' . MODULE_PAYMENT_PAYSON_MARK_BUTTON_IMG . '" alt="' . MODULE_PAYMENT_PAYSON_MARK_BUTTON_ALT . '" title="' . MODULE_PAYMENT_PAYSON_MARK_BUTTON_ALT . '" />');

define('MODULE_PAYMENT_PAYSON_PURCHASE_DESCRIPTION_TITLE', $paysonShop[$local_lang]['order_id_from_text'] . STORE_NAME);

define('MODULE_PAYMENT_PAYSON_TEXT_PAYSONREF', $paysonShop[$local_lang]['mailtext_paysonreference']);

define('NOTIFY_PAYMENT_PAYSON_UNINSTALLED', $paysonAdmin[$local_lang]['module_installed']);
define('NOTIFY_PAYMENT_PAYSON_INSTALLED', $paysonAdmin[$local_lang]['module_uninstalled']);

define('MODULE_PAYMENT_PAYSON_INV_MARK_BUTTON_IMG', $paysonShop[$local_lang]['inv_mark_button_img']);
define('MODULE_PAYMENT_PAYSON_INV_MARK_BUTTON_ALT', $paysonShop[$local_lang]['inv_check_out_w_payson']);
define('MODULE_PAYMENT_PAYSON_INV_ACCEPTANCE_MARK_TEXT', $paysonShop[$local_lang]['inv_read_more_link']);

define('MODULE_PAYMENT_PAYSON_INV_TEXT_CATALOG_LOGO', '<img src="' . MODULE_PAYMENT_PAYSON_INV_MARK_BUTTON_IMG . '" alt="' . MODULE_PAYMENT_PAYSON_INV_MARK_BUTTON_ALT . '" title="' . MODULE_PAYMENT_PAYSON_INV_MARK_BUTTON_ALT . '" /> &nbsp;' .
        '<span class="smallText">' . MODULE_PAYMENT_PAYSON_INV_ACCEPTANCE_MARK_TEXT . '</span>');

define('MODULE_PAYMENT_PAYSON_INV_NOSUCHORDER', $paysonAdmin[$local_lang]['nosuchorder']);
define('MODULE_PAYMENT_PAYSON_INV_INVSTATUS_CANT_UPDATE', $paysonAdmin[$local_lang]['cant_update']);
define('MODULE_PAYMENT_PAYSON_INV_INVSTATUS_UPDATED_FAIL', $paysonAdmin[$local_lang]['update_fail']);
define('MODULE_PAYMENT_PAYSON_INV_INVSTATUS_UPDATED_OK', $paysonAdmin[$local_lang]['update_ok']);
?>