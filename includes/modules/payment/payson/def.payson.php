<?php

/**
 * @copyright 2010 Payson
 */
//Minimal order values
$paysonInvoiceMinimalOrderValue = 30;
 
//Default values in array position 0
$paysonCurrenciesSupported = array('SEK', 'EUR');
$paysonInvoiceCurrenciesSupported = array('SEK');
$paysonLanguagesSupported = array('SV', 'EN');
$paysonFeesPayerSupported = array('EACHRECEIVER', 'PRIMARYRECEIVER', 'SENDER', 'SECONDARYONLY');

//URL for POST, GET or CURL
$paysonTokenRequestURL = "https://test-api.payson.se/1.0/Pay/";
$paysonBrowserRedirectURL = "https://test-www.payson.se/paySecure/?token=";
$paysonBrowserPostURL = "https://test-www.payson.se/paySecure/";
$paysonPaymentDetailsURL = "https://test-api.payson.se/1.0/PaymentDetails/"; 
$paysonIpnMessageValidationURL = "https://test-api.payson.se/1.0/Validate/";
$paysonPaymentUpdateURL = "https://test-api.payson.se/1.0/PaymentUpdate/";
//LINKs
$paysonSignInLink = "https://www.payson.se/SignIn/";
$paysonSignUpLink = "https://www.payson.se/account/signup/";
$paysonApiDocLink = "https://api.payson.se/";
$paysonInfoLink   = "https://www.payson.se";

//shop texts, language dependent
$paysonShop['EN']['mark_button_img']= "https://www.payson.se/sites/all/files/images/external/payson150x55.png";
$paysonShop['SV']['mark_button_img']= "https://www.payson.se/sites/all/files/images/external/payson150x55.png";

$paysonShop['EN']['inv_mark_button_img']= "https://www.payson.se/sites/all/files/images/external/payson_faktura_swap.png";
$paysonShop['SV']['inv_mark_button_img']= "https://www.payson.se/sites/all/files/images/external/payson_faktura_swap.png";

$paysonShop['EN']['check_out_w_payson'] = 'Checkout with Payson';
$paysonShop['SV']['check_out_w_payson'] = 'Betala med Payson';

$paysonShop['EN']['inv_check_out_w_payson'] = 'Checkout using Invoice - Payson';
$paysonShop['SV']['inv_check_out_w_payson'] = 'Betala med Faktura - Payson';

$paysonShop['EN']['order_id_from_text'] = 'Order: %s from ';
$paysonShop['SV']['order_id_from_text'] = 'Order: %s från ';

$paysonShop['EN']['order_id_from_text_short'] = 'Order: %s';
$paysonShop['SV']['order_id_from_text_short'] = 'Order: %s';


$paysonShop['EN']['mailtext_paysonreference'] = 'Payment Approved by Payson with Referece Number';
$paysonShop['SV']['mailtext_paysonreference'] = 'Betalning har genomf&oumlrts via Payson med Referensnummer';

$paysonShop['EN']['inv_mailtext_paysonreference'] ='PDF Invoice is sent from Payson upon delivery. Reference Number';
$paysonShop['SV']['inv_mailtext_paysonreference'] ='PDF-Faktura fr&aring;n Payson skickas n&auml;r varorna skickats. Referensnummer';


//do not increase text length on this, Prestashop use a max length of 32 chars and the Payson payment ref must also be included.
$paysonShop['EN']['paysonreference_ps'] ='Payson RefNr: ';
$paysonShop['SV']['paysonreference_ps'] ='Payson Refnr: ';

$paysonShop['EN']['inv_paysonreference_ps'] ='Payson Invoice RefNr: ';
$paysonShop['SV']['inv_paysonreference_ps'] ='Payson Faktura Refnr: ';



//admin texts,
$paysonAdmin['EN']['text_admin_title'] = "Payson New API";
$paysonAdmin['SV']['text_admin_title'] = "Payson Nytt API";

$paysonAdmin['EN']['inv_text_admin_title'] = "Payson Invoice New API";
$paysonAdmin['SV']['inv_text_admin_title'] = "Payson Faktura Nytt API";


$paysonAdmin['EN']['text_catalog_title'] = "Payson";
$paysonAdmin['SV']['text_catalog_title'] = "Payson";


$paysonAdmin['EN']['inv_text_catalog_title'] = "Invoice - Payson";
$paysonAdmin['SV']['inv_text_catalog_title'] = "Faktura - Payson";


$paysonAdmin['EN']['config_instruction1'] = '<strong>Payson</strong><br /><a href="'.$paysonSignInLink.'" target="_blank">Manage your Payson account.</a><br /><br /><font color="green">Configuration Instructions:</font><br />
  1. <a href="'.$paysonSignUpLink.'" target="_blank">Sign up for your Payson account - click here.</a><br />';
$paysonAdmin['SV']['config_instruction1'] = '<strong>Payson</strong><br /><a href="'.$paysonSignInLink.'" target="_blank">Hantera ditt Paysonkonto.</a><br /><br /><font color="green">Konfigureringsinstruktioner:</font><br />
  1. <a href="'.$paysonSignUpLink.'" target="_blank">Skapa ditt Paysonkonto - klicka h&auml;r.</a><br />';
$paysonAdmin['SV']['config_instruction1'] = '<strong>Payson</strong><br /><a href="'.$paysonSignInLink.'" target="_blank">Hantera ditt Paysonkonto.</a><br /><br /><font color="green">Konfigureringsinstruktioner:</font><br />
  1. <a href="'.$paysonSignUpLink.'" target="_blank">Skapa ditt Paysonkonto - klicka h&auml;r.</a><br />';

$paysonAdmin['EN']['config_instruction2'] = '2. ...and click "install" above to enable Payson support... and "edit" your Payson settings.';
$paysonAdmin['SV']['config_instruction2'] = '2. ...och klicka "install" ovanf&oumlr att aktivera Payson support... och "edit" dina Paysoninst&auml;llningar.';


$paysonAdmin['EN']['config_instruction2_vm'] = '2. ...and fill in form below to enable Payson support... and "save" your Payson settings.';
$paysonAdmin['SV']['config_instruction2_vm'] = '2. ...och fyll i formul&auml;r nedan f&oumlr att aktivera Payson support... och "spara" dina Paysoninst&auml;llningar.';




$paysonAdmin['EN']['config_instruction3'] = '</ul><font color="green"><hr /><strong>Requirements:</strong></font><br /><hr />*<strong>Payson Account</strong> (<a href="'.$paysonSignUpLink.'" target="_blank">click to signup</a>)<br />*<strong>*<strong>Port 80</strong> is used for bidirectional communication with the gateway, so must be open on your host\'s router/firewall<br />*<strong>Settings</strong> must be configured as described above.';
$paysonAdmin['SV']['config_instruction3'] = '</ul><font color="green"><hr /><strong>Krav:</strong></font><br /><hr />*<strong>Paysonkonto</strong> (<a href="'.$paysonSignUpLink.'" target="_blank">klicka h&auml;r f&oumlr att skapa</a>)<br />*<strong>*<strong>Port 80</strong> anv�nds f�r dubbelriktad kommunikation med Paysons server, s� den m�ste vara �ppen i din host\'s router/firewall<br />*<strong>Inst�llningar</strong> m�ste konfigureras enligt ovan beskrivet.';

$paysonAdmin['EN']['vm_invoiceFee_text'] = 'To use invoiceFee(max 30 SEK), apply a negative discount on this payment method.';
$paysonAdmin['SV']['vm_invoiceFee_text'] = 'F&oumlr att anv&auml;nda fakturaavgift(max 30 SEK), anv&auml;nd en negativ rabatt f&oumlr denna betalmetod';

$paysonAdmin['EN']['accept_payson'] = 'Do you want to accept Payson payments?';
$paysonAdmin['SV']['accept_payson'] = 'Vill du ta emot betalningar med Payson?';


$paysonAdmin['EN']['inv_accept_payson'] = 'Do you want to accept Payson Invoice payments?';
$paysonAdmin['SV']['inv_accept_payson'] = 'Vill du ta emot betalningar med Payson Faktura?';



$paysonAdmin['EN']['enable_payson'] = 'Enable Payson Module';
$paysonAdmin['SV']['enable_payson'] = 'Aktivera Paysonmodul';

$paysonAdmin['EN']['inv_enable_payson'] = 'Enable Payson Invoice Module';
$paysonAdmin['SV']['inv_enable_payson'] = 'Aktivera Payson fakturamodul';


$paysonAdmin['EN']['inv_fee'] = 'Invoice Fee';
$paysonAdmin['SV']['inv_fee'] = 'Faktureringsavgift';




$paysonAdmin['EN']['agentid_head'] = 'Agent Id';
$paysonAdmin['SV']['agentid_head'] = 'Agentid';

$paysonAdmin['EN']['agentid_text'] = 'Agent Id for your Payson account.';
$paysonAdmin['SV']['agentid_text'] = 'AgentId f&oumlr ditt Paysonkonto.';

$paysonAdmin['EN']['selleremail_head'] = 'Seller Email'; 
$paysonAdmin['EN']['selleremail_text'] = 'Email address for your Payson account.<br />NOTE: This must match <strong>EXACTLY </strong>the primary email address on your Payson account settings.';
$paysonAdmin['SV']['selleremail_head'] = 'S&auml;ljarens Email'; 
$paysonAdmin['SV']['selleremail_text'] = 'Emailadress f&oumlr ditt Paysonkonto.<br />OBS: Denna m&aring;ste vara <strong>identisk </strong>med den emailadress som f&oumlr ditt Paysonkonto.';

$paysonAdmin['EN']['md5key_head'] = 'MD5 Key';
$paysonAdmin['EN']['md5key_text'] = 'MD5 Key for your Payson account.';
$paysonAdmin['SV']['md5key_head'] = 'MD5nyckel';
$paysonAdmin['SV']['md5key_text'] = 'MD5nyckel f&oumlr ditt Paysonkonto';

$paysonAdmin['EN']['paymentmethods_head'] = 'Payment methods';
$paysonAdmin['EN']['paymentmethods_text'] = 'Whether all or some Payment Methods should be available at Payson';
$paysonAdmin['SV']['paymentmethods_head'] = 'Betalningsm&oumljligheter';
$paysonAdmin['SV']['paymentmethods_text'] = 'Om alla eller endast ett urval av betalningsm&oumljligheter skall erbjudas hos Payson';

$paysonAdmin['EN']['paymentmethods_all'] = 'All';
$paysonAdmin['SV']['paymentmethods_all'] = 'Alla';

$paysonAdmin['EN']['paymentmethods_some'] = 'Some, as below';
$paysonAdmin['SV']['paymentmethods_some'] = 'N&aring;gra enligt nedan';


$paysonAdmin['EN']['vm_extrainfo_text'] = 'If the Payment Extra Info field is blank you must click this button below!';
$paysonAdmin['SV']['vm_extrainfo_text'] = 'Om f&auml;ltet Payment Extra Info nedan &auml;r tomt m&aring;ste du klicka p&aring; knappen nedan!';

$paysonAdmin['EN']['vm_extrainfo_button_text'] = 'Populate field below automatic';
$paysonAdmin['SV']['vm_extrainfo_button_text'] = 'Fyll i f&auml;ltet nedan automatiskt';


$paysonAdmin['EN']['paymethoditems_head'] = 'Select Payment methods';
$paysonAdmin['EN']['paymethoditems_text'] = 'Check the Payment Methods that should be available at Payson';
$paysonAdmin['SV']['paymethoditems_head'] = 'V&auml;lj betalm&oumljligheter';
$paysonAdmin['SV']['paymethoditems_text'] = 'Markera de betalningsm&oumljligheter som skall erbjudas hos Payson';

$paysonAdmin['EN']['paysonguarantee_head'] = 'Payson Guarantee';
$paysonAdmin['EN']['paysonguarantee_text'] = 'Whether Payson Guarantee is offered or not.';
$paysonAdmin['SV']['paysonguarantee_head'] = 'Paysongaranti';
$paysonAdmin['SV']['paysonguarantee_text'] = 'Om Paysongaranti skall anv&auml;ndas eller ej';

$paysonAdmin['EN']['custommess_head'] = 'Custom message';
$paysonAdmin['EN']['custommess_text'] = 'Custom message, common for all orders.';
$paysonAdmin['SV']['custommess_head'] = 'Meddelande';
$paysonAdmin['SV']['custommess_text'] = 'Meddelande, likadant f&oumlr alla ordrar.';

$paysonAdmin['EN']['inv_module_uninstalled'] = 'Payson Invoice Uninstalled';
$paysonAdmin['SV']['inv_module_uninstalled'] = 'Payson Faktura avinstallerad';

$paysonAdmin['EN']['inv_module_installed'] = 'Payson Invoice Installed';
$paysonAdmin['SV']['inv_module_installed'] = 'Payson Faktura installerad';


$paysonAdmin['EN']['module_uninstalled'] = 'Payson Uninstalled';
$paysonAdmin['SV']['module_uninstalled'] = 'Payson avinstallerad';

$paysonAdmin['EN']['module_installed'] = 'Payson Installed';
$paysonAdmin['SV']['module_installed'] = 'Payson installerad';

$paysonAdmin['EN']['inv_fee_title'] = 'Invoice Fee';
$paysonAdmin['SV']['inv_fee_title'] = 'Fakturaavgift';


$paysonAdmin['EN']['inv_fee_desc'] = 'A invoice fee of 0-40 SEK incl. VAT can be added to the order when customers choose to pay with Payson Faktura.';
$paysonAdmin['SV']['inv_fee_desc'] = 'En avgift p&aring; upp till 40 kr inkl. moms kan l&auml;ggas p&aring; vid betalningar via Payson Faktura.';


$paysonAdmin['EN']['inv_fee_enable_head'] = 'Do you want to add invoice fee?';
$paysonAdmin['SV']['inv_fee_enable_head'] = 'Vill du l&auml;gga p&aring; faktureringavgift?';

$paysonAdmin['EN']['inv_fee_enable_text'] = 'An invoice fee between 0-40 SEK could be added';
$paysonAdmin['SV']['inv_fee_enable_text'] = 'En faktureringsavgift mellan 0-40 SEK kan l&auml;ggas till';

$paysonAdmin['EN']['inv_fee_amount_head'] = 'Invoice Fee';
$paysonAdmin['SV']['inv_fee_amount_head'] = 'Faktureringsavgift';


$paysonAdmin['EN']['inv_fee_amount_text'] = 'Invoice Fee without VAT';
$paysonAdmin['SV']['inv_fee_amount_text'] = 'Faktureringsavgift';


//inv updating in admin
$paysonAdmin['EN']['nosuchorder'] = 'Can not find an Payson Invoice buy on that order';
$paysonAdmin['SV']['nosuchorder'] = 'Hittar inget Payson fakturak&oumlp f&oumlr denna order';

$paysonAdmin['EN']['cant_update'] = 'Can not update invoice to desired invoice status';
$paysonAdmin['SV']['cant_update'] = 'Kan inte uppdatera fakturastatus till &oumlnskad status';


$paysonAdmin['EN']['update_fail'] = 'Failed update status to ';
$paysonAdmin['SV']['update_fail'] = 'Misslyckades att uppdatera status till ';

$paysonAdmin['EN']['update_ok'] = 'Updated the invoice status of the Payson Invoice to ';
$paysonAdmin['SV']['update_ok'] = 'Uppdaterade Paysonfakturans status till ';


$paysonAdmin['EN']['inv_statuschange_hint_head'] = 'Payson Invoice Status Change Hints';
$paysonAdmin['SV']['inv_statuschange_hint_head'] = 'Payson Faktura, hj&auml;lp f&oumlr status&auml;ndring';

$paysonAdmin['EN']['inv_statuschange_hint1'] = 'Update order status to %s will change the Payson Invoice to %s';
$paysonAdmin['SV']['inv_statuschange_hint1'] = 'Uppdatering av orderstatus till %s kommer att &auml;ndra Payson Fakturan till %s';

$paysonAdmin['EN']['inv_current_status_head'] = 'Current Payson Invoice status: ';
$paysonAdmin['SV']['inv_current_status_head'] = 'Nuvarande Payson Faktura status: ';
$paysonAdmin['FI']['inv_current_status_head'] = 'Nuvarande Payson Faktura status: ';

$paysonAdmin['EN']['inv_status_history_head'] = 'History';
$paysonAdmin['SV']['inv_status_history_head'] = 'Historik';

$paysonAdmin['EN']['inv_status_history_head2'] = '<td>Date</td> <td>Status</td> <td>Update</td>';
$paysonAdmin['SV']['inv_status_history_head2'] = '<td>Datum</td> <td>Status</td> <td>Uppdatering</td>';

$paysonAdmin['EN']['enable_payson_invoice_head'] = 'Enable Payson Invoice';
$paysonAdmin['SV']['enable_payson_invoice_head'] = 'Aktivera Payson Faktura';

$paysonAdmin['EN']['enable_payson_invoice_text'] = 'Please note that this requires a separate contract with Payson';
$paysonAdmin['SV']['enable_payson_invoice_text'] = 'Vänligen notera att detta kräver ett separat avtal med Payson';

$paysonAdmin['EN']['manage_invoice_from_store_head'] = 'Manage invoice from store';
$paysonAdmin['SV']['manage_invoice_from_store_head'] = 'Hantera faktura från butiken';

$paysonAdmin['EN']['manage_invoice_from_store_text'] = 'Please not that it is important that you use set order statuses that make sense for you when using this function';
$paysonAdmin['SV']['manage_invoice_from_store_text'] = 'Vänligen notera att det är mycket viktigt att du använder en orderstatus som inte gör att du blandar ihop ordrar';

//db table names
$paysonDbTablePaytrans = "payson_paytrans";
$paysonDbTableEvents   = "payson_events";
?>