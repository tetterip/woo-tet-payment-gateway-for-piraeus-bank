<?php
/*
  Plugin Name: Piraeus Bank WooCommerce Payment Gateway
  Description: Piraeus Bank Payment Gateway allows you to accept payment through various channels such as Maestro, Mastercard, AMex cards, Diners  and Visa cards On your Woocommerce Powered Site.
  Version: 3.2.3
  Author: Michalis Tetteris
  Author URI: https://ttrp.gr
  License: GPL-3.0+
  License URI: http://www.gnu.org/licenses/gpl-3.0.txt
  WC tested up to: 10.6.0
  Text Domain: woo-payment-gateway-for-piraeus-bank
  Domain Path: /languages
*/
/*
Based on original plugin "Piraeus Bank Greece Payment Gateway for WooCommerce" by emspace.gr [https://wordpress.org/plugins/woo-payment-gateway-piraeus-bank-greece/]
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Declare WooCommerce feature compatibility.
 *
 * Both declarations MUST be at the top level of the plugin file, outside any
 * callback. The before_woocommerce_init hook fires before plugins_loaded, so
 * any add_action() registered inside plugins_loaded will never catch it.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        // HPOS — High-Performance Order Storage
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
        // Block-based Cart & Checkout
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'cart_checkout_blocks',
            __FILE__,
            true
        );
    }
} );

add_action( 'plugins_loaded', function () {
    if ( ! class_exists( '\WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo __( 'Piraeus Bank Payment Gateway requires WooCommerce to be installed and active.', 'woo-payment-gateway-for-piraeus-bank' );
            echo '</p></div>';
        } );
        return;
    }

    spl_autoload_register( function ( $class ) {
        $prefix   = 'Papaki\\PiraeusBank\\WooCommerce\\';
        $base_dir = plugin_dir_path( __FILE__ ) . 'classes/';

        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, $len );
        $file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    } );

    // Pass the absolute path (__FILE__), not plugin_basename().
    // FeaturesUtil::declare_compatibility() requires the absolute path to match
    // what WooCommerce uses internally when scanning active plugins.
    new \Papaki\PiraeusBank\WooCommerce\Application( __FILE__ );
}, 0 );