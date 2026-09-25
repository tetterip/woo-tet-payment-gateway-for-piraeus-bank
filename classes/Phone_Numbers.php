<?php

namespace Papaki\PiraeusBank\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Routes a Greek phone number to the right 3-D Secure field.
 *
 * WooCommerce collects one billing phone with no type, but the ticket request has separate
 * MobilePhone / HomePhone / WorkPhone fields and the issuer's risk engine reads them as
 * separate facts. The Greek numbering plan makes the type unambiguous: 69x is mobile, 2x is
 * fixed line. Numbers from other countries have no such guarantee and are left to the
 * existing all-fields behaviour.
 */
class Phone_Numbers {

    /**
     * @param string $phone   Billing phone as entered.
     * @param string $country Billing country (ISO 3166 alpha-2).
     *
     * @return array{type: string, number: string}|null type is 'mobile' or 'home'; number is
     *         formatted "30-XXXXXXXXXX". Null when the number is not a recognisable Greek one.
     */
    public static function greek( string $phone, string $country ): ?array {
        $trimmed = trim( $phone );
        $digits  = preg_replace( '/\D/', '', $trimmed );

        if ( $digits === '' ) {
            return null;
        }

        $has_prefix = false;
        if ( strpos( $trimmed, '+' ) === 0 && strpos( $digits, '30' ) === 0 ) {
            $digits     = substr( $digits, 2 );
            $has_prefix = true;
        } elseif ( strpos( $digits, '0030' ) === 0 ) {
            $digits     = substr( $digits, 4 );
            $has_prefix = true;
        } elseif ( strpos( $digits, '30' ) === 0 && strlen( $digits ) === 12 ) {
            $digits     = substr( $digits, 2 );
            $has_prefix = true;
        }

        // Without an explicit +30 the number is only treated as Greek for a Greek address.
        if ( ! $has_prefix && strtoupper( trim( $country ) ) !== 'GR' ) {
            return null;
        }

        if ( strlen( $digits ) !== 10 ) {
            return null;
        }

        if ( strpos( $digits, '69' ) === 0 ) {
            return [ 'type' => 'mobile', 'number' => '30-' . $digits ];
        }

        if ( $digits[0] === '2' ) {
            return [ 'type' => 'home', 'number' => '30-' . $digits ];
        }

        return null;
    }
}
