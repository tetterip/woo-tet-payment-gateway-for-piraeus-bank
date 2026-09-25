<?php
/**
 * Plugin Name:       Piraeus Bank Payment Gateway for WooCommerce
 * Plugin URI:        https://ttrp.gr
 * Description:       Piraeus Bank Payment Gateway allows you to accept payment through various channels such as Maestro, Mastercard, AMex cards, Diners and Visa cards on your WooCommerce powered site.
 * Version:           3.4.6
 * Author:            Michalis Tetteris
 * Author URI:        https://ttrp.gr
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       woo-payment-gateway-for-piraeus-bank
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * Requires at least: 4.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * WC requires at least: 5.0
 * WC tested up to:   10.7.0
 * Based on:          "Piraeus Bank Greece Payment Gateway for WooCommerce" by emspace.gr
 */

defined( 'ABSPATH' ) || exit;

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

// ── ttrp.gr Plugins menu ─────────────────────────────────────────────────────
function ttrp_pb_maybe_register_brand_menu(): void {
    global $menu;
    if ( is_array( $menu ) ) {
        foreach ( $menu as $item ) {
            if ( isset( $item[2] ) && 'ttrp-plugins' === $item[2] ) return;
        }
    }
    if ( ! function_exists( 'ttrp_brand_dashboard' ) ) {
        function ttrp_brand_dashboard(): void {
            global $submenu;
            $target = null;
            if ( ! empty( $submenu['ttrp-plugins'] ) ) {
                foreach ( $submenu['ttrp-plugins'] as $item ) {
                    if ( $item[2] !== 'ttrp-plugins' ) { $target = $item[2]; break; }
                }
            }
            if ( $target ) {
                $url = esc_url( admin_url( 'admin.php?page=' . $target ) );
                echo '<meta http-equiv="refresh" content="0;url=' . $url . '">';
                echo '<script>window.location.replace("' . $url . '");</script>';
            }
        }
    }
    $svg_path = plugin_dir_path( __FILE__ ) . 'assets/ttrp-logo.svg';
    $icon = file_exists( $svg_path )
        ? 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $svg_path ) )
        : 'dashicons-admin-plugins';
    add_menu_page( 'ttrp.gr Plugins', 'ttrp.gr Plugins', 'manage_options', 'ttrp-plugins', 'ttrp_brand_dashboard', $icon, 56 );
    add_action( 'admin_menu', function () { remove_submenu_page( 'ttrp-plugins', 'ttrp-plugins' ); }, 999 );
}
function ttrp_pb_add_submenu(): void {
    add_submenu_page( 'ttrp-plugins', 'Piraeus Bank', 'Piraeus Bank', 'manage_options', 'ttrp-piraeus-bank', '__return_null' );
}
function ttrp_pb_redirect(): void {
    if ( isset( $_GET['page'] ) && 'ttrp-piraeus-bank' === $_GET['page'] ) {
        wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=piraeusbank_gateway' ) );
        exit;
    }
}
add_action( 'admin_menu', 'ttrp_pb_maybe_register_brand_menu', 5 );
add_action( 'admin_menu', 'ttrp_pb_add_submenu' );
add_action( 'admin_init',  'ttrp_pb_redirect' );

require_once plugin_dir_path( __FILE__ ) . 'update-checker.php';
new TTRP_Update_Checker( __FILE__, 'woo-tet-payment-gateway-for-piraeus-bank' );