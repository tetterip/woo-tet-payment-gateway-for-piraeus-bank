<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display Piraeus Bank payment message on the order-received page.
 * Called via add_action( 'wp', ... ) in Application.php.
 *
 * NOTE: The functions below (woocommerce_add_piraeusbank_gateway,
 * piraeusbank_plugin_action_links, piraeus_woocommerce_states, pb_getCardholderName,
 * pb_getCountryNumericCode, pb_validatePhoneNumberAllCountries, etc.) that were
 * previously in this file are now handled as methods of the Application and
 * WC_Piraeusbank_Gateway classes. They have been removed to avoid dead code.
 */
function piraeusbank_message() {
    $order_id = absint( get_query_var( 'order-received' ) );

    if ( ! $order_id ) {
        return;
    }

    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        return;
    }

    if ( ! is_order_received_page() || 'piraeusbank_gateway' !== $order->get_payment_method() ) {
        return;
    }

    $piraeusbank_message = $order->get_meta( '_piraeusbank_message', true );

    if ( ! empty( $piraeusbank_message ) ) {
        $message      = $piraeusbank_message['message'];
        $message_type = $piraeusbank_message['message_type'];

        $order->delete_meta_data( '_piraeusbank_message' );
        $order->save();

        wc_add_notice( $message, $message_type );
    }
}