<?php
/*
  $Id: ot_payson_inv_fee.php

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Released under the GNU General Public License
*/

class ot_payson_inv_fee {
    var $title, $output;

    function ot_payson_inv_fee() 
	{
		$this->code = 'ot_payson_inv_fee';
		$this->title = MODULE_PAYSON_INV_FEE_TITLE;
		$this->description = MODULE_PAYSON_INV_FEE_DESCRIPTION;
		$this->enabled = MODULE_PAYSON_INV_FEE_STATUS;
		$this->sort_order = MODULE_PAYSON_INV_FEE_SORT_ORDER;
		$this->tax_class = MODULE_PAYSON_INV_FEE_TAX_CLASS;
		$this->output = array();
    }

    function process() 
	{
		global $order, $ot_subtotal, $currencies;

		if ($_SESSION['payment'] != "payson_inv")
			return;
		 
		$od_amount = MODULE_PAYSON_INV_FEE_FEE;
		//--
		if (MODULE_PAYSON_INV_FEE_TAX_CLASS > 0) {
			$tod_rate =zen_get_tax_rate(MODULE_PAYSON_INV_FEE_TAX_CLASS);
			$tod_amount =  zen_calculate_tax($od_amount, $tod_rate);
			$order->info['tax'] += $tod_amount;
			$tax_desc = zen_get_tax_description(
			MODULE_PAYSON_INV_FEE_TAX_CLASS,
			$order->billing['country']['id'], $order->delivery['zone_id']);
			$order->info['tax_groups']["$tax_desc"] += zen_calculate_tax($od_amount, $tod_rate);
		}

		if (DISPLAY_PRICE_WITH_TAX=="true") { 
			$od_amount = $od_amount + $tod_amount;
		} else {       
			$order->info['total'] += $tod_amount;
		}
		//--
		if ($od_amount != 0) {
			$this->output[] = array('title' => $this->title . ':',
						'text' => $currencies->format($od_amount),
						'value' => $od_amount);
			$order->info['total'] = $order->info['total'] + $od_amount;  
			if ($this->sort_order < $ot_subtotal->sort_order) {
			$order->info['subtotal'] = $order->info['subtotal'] - $od_amount;
			}
		}
    }
    

    
    function check() 
	{
		global $db;
		if (!isset($this->check)) {
			$check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYSON_INV_FEE_STATUS'");
			$this->check = $check_query->RecordCount();
		}

		return $this->check;
    }

    function keys() {
	return array('MODULE_PAYSON_INV_FEE_STATUS',
		     'MODULE_PAYSON_INV_FEE_FEE',
		     'MODULE_PAYSON_INV_FEE_TAX_CLASS',
		     'MODULE_PAYSON_INV_FEE_SORT_ORDER'
		     );
    }

    function install() {
	global $db;
	
	include( DIR_FS_CATALOG . 'includes/modules/payment/payson/def.payson.php');
    
    if (!isset($_SESSION['language'])){
    	include( DIR_FS_CATALOG . 'includes/languages/english/modules/order_total/ot_payson_inv_fee.php');
    } else {
        include( DIR_FS_CATALOG . 'includes/languages/'.$_SESSION['language'].'/modules/order_total/ot_payson_inv_fee.php');
    }
	
	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('".MODULE_PAYSON_INV_FEE_ENABLE_HEAD."', 'MODULE_PAYSON_INV_FEE_STATUS', 'true', '".MODULE_PAYSON_INV_FEE_ENABLE_TEXT."', '6', '1','zen_cfg_select_option(array(\'true\', \'false\'), ', now())");
	
	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('".MODULE_PAYSON_INV_FEE_AMOUNT_HEAD."', 'MODULE_PAYSON_INV_FEE_FEE', '20', '".MODULE_PAYSON_INV_FEE_AMOUNT_TEXT."', '6', '7', now())");

	
	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort Order', 'MODULE_PAYSON_INV_FEE_SORT_ORDER', '201', 'Sort order of display.', '6', '2', now())");

	$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Tax Class', 'MODULE_PAYSON_INV_FEE_TAX_CLASS', '0', 'Use the following tax class on the payment charge.', '6', '6', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");


    }

    function remove() {
	global $db;
	$keys = '';
	$keys_array = $this->keys();
	for ($i=0; $i<sizeof($keys_array); $i++) {
	    $keys .= "'" . $keys_array[$i] . "',";
	}
	$keys = substr($keys, 0, -1);

	$db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in (" . $keys . ")");
    }
}
?>