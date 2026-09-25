<?php
/**
 * Removes the plugin's own data when it is deleted from Plugins → Installed Plugins.
 *
 * Order meta (transaction IDs, support reference IDs) is kept: it belongs to the orders.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'piraeusbank_transactions' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange

delete_option( 'woocommerce_piraeusbank_gateway_settings' );
delete_option( 'piraeusbank_credentials_error' );
delete_option( 'piraeusbank_unconfirmed_orders' );

delete_transient( 'piraeusbank_server_ip' );
delete_transient( 'piraeusbank_outbound_ip' );
delete_transient( 'piraeusbank_cloudflare_ranges' );

wp_clear_scheduled_hook( 'piraeusbank_reconcile' );
