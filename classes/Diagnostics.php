<?php

namespace Papaki\PiraeusBank\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Setup diagnostics on the gateway settings page: callback URLs, outbound IP, Cloudflare
 * detection and a "Test callback URL" check for host firewalls (WAF).
 */
class Diagnostics {

    public const AJAX_ACTION = 'piraeusbank_test_callback';

    /** MerchantReference prefix of the synthetic test callback; never maps to an order. */
    public const TEST_REFERENCE_PREFIX = 'WAFTEST-';

    /** Response header the callback handler sends for a test request that reached PHP. */
    public const TEST_HEADER = 'X-Piraeusbank-Callback';

    private const OUTBOUND_IP_TRANSIENT = 'piraeusbank_outbound_ip';
    private const CLOUDFLARE_TRANSIENT  = 'piraeusbank_cloudflare_ranges';

    public static function init() {
        add_action( 'wp_ajax_' . self::AJAX_ACTION, [ self::class, 'ajax_test_callback' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue' ] );
    }

    /**
     * The callback URL registered with the bank, in the same form the settings page has
     * always shown it.
     *
     * @param string $action success | fail | cancel
     *
     * @return string
     */
    public static function callback_url( string $action ): string {
        $host = get_bloginfo( 'url' ) . '/';

        return $host . ( get_option( 'permalink_structure' ) ? 'wc-api/WC_Piraeusbank_Gateway?peiraeus=' . $action : '?wc-api=WC_Piraeusbank_Gateway&peiraeus=' . $action );
    }

    /**
     * The server's outbound IPv4 address, which is what the bank must register.
     *
     * Asks api.ipify.org (cached 12h; a failure is cached 15 minutes). A host that knows its
     * outbound IP can supply it with the `piraeusbank_outbound_ip` filter.
     *
     * @return string '' when it cannot be determined.
     */
    public static function outbound_ip(): string {
        $filtered = apply_filters( 'piraeusbank_outbound_ip', '' );
        if ( is_string( $filtered ) && filter_var( $filtered, FILTER_VALIDATE_IP ) ) {
            return $filtered;
        }

        $cached = get_transient( self::OUTBOUND_IP_TRANSIENT );
        if ( is_string( $cached ) ) {
            return $cached === 'none' ? '' : $cached;
        }

        $response = wp_remote_get( 'https://api.ipify.org', [ 'timeout' => 5 ] );
        $ip       = is_wp_error( $response ) ? '' : trim( (string) wp_remote_retrieve_body( $response ) );

        if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            set_transient( self::OUTBOUND_IP_TRANSIENT, $ip, 12 * HOUR_IN_SECONDS );
            return $ip;
        }

        set_transient( self::OUTBOUND_IP_TRANSIENT, 'none', 15 * MINUTE_IN_SECONDS );
        return '';
    }

    /**
     * The address the site's hostname resolves to (where visitors connect). On many hosts
     * this is not the address outgoing requests leave from.
     *
     * @return string
     */
    public static function site_ip(): string {
        $host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
        $ip   = $host !== '' ? gethostbyname( $host ) : '';

        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }

    /**
     * Whether the current admin request arrived through Cloudflare.
     *
     * @return bool
     */
    public static function is_cloudflare(): bool {
        return ! empty( $_SERVER['HTTP_CF_RAY'] ) || ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] );
    }

    /**
     * Cloudflare's published IPv4 ranges (cached 12h; a failure is cached 15 minutes).
     *
     * @return string[]
     */
    public static function cloudflare_ranges(): array {
        $cached = get_transient( self::CLOUDFLARE_TRANSIENT );
        if ( is_array( $cached ) ) {
            return $cached;
        }

        $response = wp_safe_remote_get( 'https://www.cloudflare.com/ips-v4/', [ 'timeout' => 5 ] );
        $ranges   = [];

        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            foreach ( preg_split( '/\s+/', (string) wp_remote_retrieve_body( $response ) ) as $line ) {
                if ( preg_match( '#^\d{1,3}(\.\d{1,3}){3}/\d{1,2}$#', $line ) ) {
                    $ranges[] = $line;
                }
            }
        }

        set_transient( self::CLOUDFLARE_TRANSIENT, $ranges, $ranges ? 12 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );

        return $ranges;
    }

    /**
     * AJAX: POST a realistic declined-payment response to the site's own callback URLs and
     * report whether it reached the plugin. Host firewalls sometimes block the bank's
     * decline payload (Greek text, empty HashKey, dash-only date) before PHP runs, which
     * leaves orders stuck in "Pending payment".
     */
    public static function ajax_test_callback() {
        check_ajax_referer( self::AJAX_ACTION, 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( [ 'message' => __( 'You are not allowed to do this.', Application::PLUGIN_NAMESPACE ) ], 403 );
        }

        $results = [];
        foreach ( [ 'fail', 'success' ] as $action ) {
            $results[] = self::run_test( $action );
        }

        wp_send_json_success( [ 'results' => $results ] );
    }

    /**
     * @param string $action
     *
     * @return array{url: string, status: string, message: string}
     */
    private static function run_test( string $action ): array {
        $url = self::callback_url( $action );

        $response = wp_remote_post( $url, [
            'timeout'     => 15,
            'redirection' => 0,
            'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
            'headers'     => [ 'Referer' => 'https://paycenter.piraeusbank.gr/redirection/pay.aspx' ],
            'body'        => [
                'SupportReferenceID'  => '0',
                'ResultCode'          => '981',
                'ResultDescription'   => 'Invalid Card number/Exp Month/Exp Year.',
                'StatusFlag'          => 'Failure',
                'ResponseCode'        => '',
                'ResponseDescription' => 'Η συναλλαγή απορρίφθηκε από την εκδότρια τράπεζα.',
                'LanguageCode'        => 'el-GR',
                'MerchantReference'   => self::TEST_REFERENCE_PREFIX . wp_generate_password( 12, false, false ),
                'TransactionDateTime' => '--',
                'TransactionId'       => '',
                'CardType'            => '',
                'PackageNo'           => '',
                'ApprovalCode'        => '',
                'RetrievalRef'        => '',
                'AuthStatus'          => '',
                'Parameters'          => '',
                'HashKey'             => '',
                'PaymentMethod'       => '',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            return [
                'url'     => $url,
                'status'  => 'unreachable',
                'message' => sprintf(
                    /* translators: %s: error message */
                    __( 'Your server could not connect to its own URL (%s). Many hosts block such loopback requests, so this test cannot tell whether a firewall blocks the bank. Ask your host, or make a test payment.', Application::PLUGIN_NAMESPACE ),
                    $response->get_error_message()
                ),
            ];
        }

        if ( wp_remote_retrieve_header( $response, self::TEST_HEADER ) === 'reached' ) {
            return [
                'url'     => $url,
                'status'  => 'pass',
                'message' => __( 'OK: the test response reached the plugin.', Application::PLUGIN_NAMESPACE ),
            ];
        }

        return [
            'url'     => $url,
            'status'  => 'blocked',
            'message' => sprintf(
                /* translators: %d: HTTP status code */
                __( 'Blocked: the request did not reach the plugin (HTTP %d). A firewall (ModSecurity, Imunify360, BitNinja, LiteSpeed, a security plugin or a CDN) is probably intercepting it. Ask your host to whitelist this URL for the bank\'s responses; do not disable the firewall for the whole site.', Application::PLUGIN_NAMESPACE ),
                (int) wp_remote_retrieve_response_code( $response )
            ),
        ];
    }

    /**
     * @param string $hook_suffix
     */
    public static function enqueue( $hook_suffix ) {
        if ( $hook_suffix !== 'woocommerce_page_wc-settings' || ! isset( $_GET['section'] ) || $_GET['section'] !== 'piraeusbank_gateway' || ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $file = dirname( __DIR__ ) . '/assets/js/admin-settings.js';

        wp_enqueue_script( 'piraeusbank-admin-settings', plugins_url( 'assets/js/admin-settings.js', dirname( __DIR__ ) . '/wooshop-piraeus.php' ), [], (string) filemtime( $file ), true );
        wp_localize_script( 'piraeusbank-admin-settings', 'piraeusbankAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'action'  => self::AJAX_ACTION,
            'nonce'   => wp_create_nonce( self::AJAX_ACTION ),
            'i18n'    => [
                'testing' => __( 'Testing…', Application::PLUGIN_NAMESPACE ),
                'copied'  => __( 'Copied', Application::PLUGIN_NAMESPACE ),
                'error'   => __( 'The test could not be run. Reload the page and try again.', Application::PLUGIN_NAMESPACE ),
            ],
        ] );
    }
}
