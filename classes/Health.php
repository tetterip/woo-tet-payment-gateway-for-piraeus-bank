<?php

namespace Papaki\PiraeusBank\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin-facing health checks:
 *  - a persistent warning while the bank rejects the store's credentials (ResultCode 100),
 *    which blocks every card payment;
 *  - a daily reconciliation that flags orders sent to the bank whose response never came
 *    back, and prunes the transaction-ticket table.
 */
class Health {

    public const CREDENTIALS_OPTION = 'piraeusbank_credentials_error';
    public const REPORT_OPTION      = 'piraeusbank_unconfirmed_orders';
    public const CRON_HOOK          = 'piraeusbank_reconcile';
    public const FLAG_META          = '_piraeusbank_unconfirmed_flagged';
    public const CUSTOMER_CANCEL    = '_piraeusbank_customer_cancelled';

    /** Time a customer may spend on the bank page before the attempt counts as unconfirmed. */
    private const GRACE = 2 * HOUR_IN_SECONDS;

    /** Only attempts this recent are reconciled; older ones were handled by earlier runs. */
    private const WINDOW = 7 * DAY_IN_SECONDS;

    /** Ticket rows older than this are deleted. */
    private const RETENTION = 30 * DAY_IN_SECONDS;

    private const BATCH = 200;

    private const REPORT_MAX = 50;

    public static function init() {
        add_action( 'init', [ self::class, 'schedule' ] );
        add_action( self::CRON_HOOK, [ self::class, 'reconcile' ] );
        add_action( 'admin_init', [ self::class, 'maybe_dismiss_report' ] );
        add_action( 'admin_notices', [ self::class, 'credentials_notice' ] );
        add_action( 'admin_notices', [ self::class, 'report_notice' ] );
    }

    public static function schedule() {
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
        }
    }

    /**
     * Called when IssueNewTicket returns ResultCode 100 (Authentication Error).
     *
     * @param string $description The bank's ResultDescription.
     */
    public static function flag_credentials_error( string $description ) {
        $current = get_option( self::CREDENTIALS_OPTION );
        $since   = is_array( $current ) && ! empty( $current['since'] ) ? (int) $current['since'] : time();

        update_option( self::CREDENTIALS_OPTION, [ 'since' => $since, 'description' => $description ], false );
    }

    /** Called when a ticket is issued successfully. */
    public static function clear_credentials_error() {
        if ( get_option( self::CREDENTIALS_OPTION ) !== false ) {
            delete_option( self::CREDENTIALS_OPTION );
        }
    }

    /**
     * Not dismissible: it stands for a complete outage of card payments, and it removes
     * itself as soon as a payment is started successfully.
     */
    public static function credentials_notice() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $error = get_option( self::CREDENTIALS_OPTION );
        if ( ! is_array( $error ) ) {
            return;
        }

        $since = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $error['since'] );

        echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Piraeus Bank: card payments are failing.', Application::PLUGIN_NAMESPACE ) . '</strong> ';
        echo esc_html(
            sprintf(
                /* translators: %s: date and time */
                __( 'Since %s the bank has rejected the Username or Password of the Ticketing Web Service (ResultCode 100), so no customer can pay by card.', Application::PLUGIN_NAMESPACE ),
                $since
            )
        );
        if ( ! empty( $error['description'] ) ) {
            echo ' ' . esc_html(
                sprintf(
                    /* translators: %s: error message from the bank */
                    __( 'Bank response: "%s".', Application::PLUGIN_NAMESPACE ),
                    $error['description']
                )
            );
        }
        echo '</p><p><a class="button button-primary" href="' . esc_url( self::settings_url() ) . '">' . esc_html__( 'Check the credentials', Application::PLUGIN_NAMESPACE ) . '</a> ';
        echo esc_html__( 'This warning disappears automatically after the next successful payment attempt.', Application::PLUGIN_NAMESPACE ) . '</p></div>';
    }

    /**
     * Daily: flag orders whose customer was sent to the bank but no response arrived, and
     * prune old ticket rows.
     *
     * Order status is never changed. Without the bank's follow-up web service the plugin
     * cannot tell an abandoned payment page from an approved payment whose response was lost
     * (firewall, wrong callback URL), so it reports them for checking in the AdminTool.
     */
    public static function reconcile() {
        global $wpdb;

        $table = $wpdb->prefix . 'piraeusbank_transactions';
        $now   = time();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name
        $refs = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT merch_ref FROM {$table} WHERE timestamp BETWEEN %s AND %s LIMIT %d",
            gmdate( 'Y-m-d H:i:s', $now - self::WINDOW ),
            gmdate( 'Y-m-d H:i:s', $now - self::GRACE ),
            self::BATCH
        ) );

        $by_order = [];
        foreach ( (array) $refs as $ref ) {
            $order_id = Payment_Reference::order_id( (string) $ref );
            if ( $order_id ) {
                $by_order[ $order_id ][] = (string) $ref;
            }
        }

        $flagged = [];
        foreach ( $by_order as $order_id => $order_refs ) {
            $order = wc_get_order( $order_id );

            if ( ! $order instanceof \WC_Order || $order->get_payment_method() !== 'piraeusbank_gateway' || $order->is_paid() ) {
                continue;
            }

            // Pending, or cancelled by WooCommerce's unpaid-order timeout (which usually runs
            // before the grace period ends). A cancel on the bank page is excluded.
            if ( ! $order->has_status( [ 'pending', 'cancelled' ] ) || $order->get_meta( self::CUSTOMER_CANCEL ) || $order->get_meta( self::FLAG_META ) ) {
                continue;
            }

            $order->add_order_note(
                sprintf(
                    /* translators: %s: comma-separated MerchantReference values */
                    __( 'Piraeus Bank: the customer was sent to the bank but no response came back (MerchantReference: %s). This is usually an abandoned payment, but it can also be an approved payment whose response was lost. Check these references in the Piraeus Bank AdminTool; if one was approved, complete the order manually.', Application::PLUGIN_NAMESPACE ),
                    implode( ', ', $order_refs )
                )
            );
            $order->update_meta_data( self::FLAG_META, gmdate( 'Y-m-d H:i:s', $now ) );
            $order->save();

            $flagged[] = $order_id;
        }

        if ( $flagged ) {
            $report = array_merge( (array) get_option( self::REPORT_OPTION, [] ), $flagged );
            $report = array_slice( array_values( array_unique( array_map( 'absint', $report ) ) ), -self::REPORT_MAX );
            update_option( self::REPORT_OPTION, $report, false );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE timestamp < %s", gmdate( 'Y-m-d H:i:s', $now - self::RETENTION ) ) );
    }

    public static function report_notice() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $order_ids = array_filter( (array) get_option( self::REPORT_OPTION, [] ), static function ( $id ) {
            $order = wc_get_order( $id );
            return $order instanceof \WC_Order && ! $order->is_paid();
        } );

        if ( ! $order_ids ) {
            return;
        }

        $links = array_map( static function ( $id ) {
            $order = wc_get_order( $id );
            return '<a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . esc_html( $order->get_order_number() ) . '</a>';
        }, $order_ids );

        $dismiss = wp_nonce_url( add_query_arg( 'piraeusbank_dismiss_report', '1' ), 'piraeusbank_dismiss_report' );

        echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Piraeus Bank: payments without a bank response', Application::PLUGIN_NAMESPACE ) . '</strong></p><p>';
        echo esc_html__( 'These orders were sent to the bank but no response came back. Most are abandoned payments, but check them in the Piraeus Bank AdminTool: if one was approved, complete it manually. The order note lists the references to look up.', Application::PLUGIN_NAMESPACE );
        echo '</p><p>' . wp_kses( implode( ', ', $links ), [ 'a' => [ 'href' => [] ] ] ) . '</p>';
        echo '<p><a href="' . esc_url( $dismiss ) . '">' . esc_html__( 'Dismiss', Application::PLUGIN_NAMESPACE ) . '</a></p></div>';
    }

    public static function maybe_dismiss_report() {
        if ( ! isset( $_GET['piraeusbank_dismiss_report'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        check_admin_referer( 'piraeusbank_dismiss_report' );
        delete_option( self::REPORT_OPTION );

        wp_safe_redirect( remove_query_arg( [ 'piraeusbank_dismiss_report', '_wpnonce' ] ) );
        exit;
    }

    /**
     * @return string
     */
    public static function settings_url(): string {
        return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=piraeusbank_gateway' );
    }
}
