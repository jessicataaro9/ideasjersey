<?php
/*
 * Plugin Name: Easypay_direct_creditcard Payment
 * Plugin URI: #
 * Description: Easypay_direct_creditcard Payment
 * Version: v1.0.0
 * Author: PariTECH
 * Author URI: #
 * Requires at least: 3.3
 * Tested up to: 3.5.1
 * Text Domain: easypay-direct-creditcard-woocommerce
 * Domain Path: /lang/
 */
//if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) die('You are not allowed to call this page directly.');

add_action( 'plugins_loaded', 'onlinepay_authorizepay_gateway_init');

function onlinepay_authorizepay_gateway_init()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    load_plugin_textdomain('Onlinepay_authorizepay', false, dirname(plugin_basename(__FILE__)) . '/lang/');
    require_once(plugin_basename('class-wc-easypay-direct-creditcard-woocommerce.php'));
    add_filter('woocommerce_payment_gateways', 'woocommerce_onlinepay_authorizepay_add_gateway');
}

/**
 * Add the gateway to WooCommerce
 *
 * @access public
 * @param array $methods
 * @package WooCommerce/Classes/Payment
 * @return array
 */
function woocommerce_onlinepay_authorizepay_add_gateway($methods)
{
    $methods[] = 'WC_Easypay_direct_creditcard';
    return $methods;
}

?>