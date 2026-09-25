<?php

namespace Papaki\PiraeusBank\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pure helpers for the MerchantReference and the bank response fields.
 *
 * The MerchantReference sent to the bank is "{order_id}-{12 random chars}". The random
 * part is a per-attempt secret: it is stored next to the transaction ticket and a callback
 * must echo it back before it can change an order. Callbacks cannot rely on the WC session
 * (the bank POSTs cross-site, so SameSite=Lax strips the cookie) and the declined-payment
 * response carries no HashKey, so this secret is what authenticates it.
 */
class Payment_Reference {

    /** Length of the random suffix. Upper-case alphanumerics, ~62 bits. */
    public const SUFFIX_LENGTH = 12;

    /** The bank accepts up to 50 characters. */
    public const MAX_LENGTH = 50;

    /**
     * Build a fresh MerchantReference for a payment attempt.
     *
     * @param int         $order_id
     * @param string|null $suffix Fixed suffix (tests only); random when null.
     *
     * @return string
     */
    public static function build( int $order_id, ?string $suffix = null ): string {
        if ( $suffix === null ) {
            $suffix = '';
            $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            for ( $i = 0; $i < self::SUFFIX_LENGTH; $i++ ) {
                $suffix .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
            }
        }

        return substr( $order_id . '-' . $suffix, 0, self::MAX_LENGTH );
    }

    /**
     * Extract the order ID from a MerchantReference.
     *
     * Accepts the current "{id}-{suffix}" format and the plain "{id}" used before 3.5.0,
     * so payments already in flight during an update still resolve.
     *
     * @param string $reference
     *
     * @return int 0 when the reference is not recognisable.
     */
    public static function order_id( string $reference ): int {
        if ( preg_match( '/^(\d+)(?:-[A-Za-z0-9]+)?$/', trim( $reference ), $m ) ) {
            return (int) $m[1];
        }

        return 0;
    }

    /**
     * Whether the bank response is an IRIS payment (CardType 15 or PaymentMethod IRIS).
     *
     * @param string $card_type
     * @param string $payment_method
     *
     * @return bool
     */
    public static function is_iris( string $card_type, string $payment_method ): bool {
        return trim( $card_type ) === '15' || strtoupper( trim( $payment_method ) ) === 'IRIS';
    }

    /**
     * IRIS ResponseCode 09: the transfer was initiated but not yet confirmed and may still
     * settle through DIAS. It is not a decline, so the order must not be marked failed.
     * A card ResponseCode 09 does not carry this meaning.
     *
     * @param string $response_code
     * @param string $card_type
     * @param string $payment_method
     *
     * @return bool
     */
    public static function is_iris_pending( string $response_code, string $card_type, string $payment_method ): bool {
        return self::is_iris( $card_type, $payment_method ) && ltrim( trim( $response_code ), '0' ) === '9';
    }
}
