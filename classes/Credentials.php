<?php

namespace Papaki\PiraeusBank\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Storage format of the Ticketing Web Service password.
 *
 * The bank only ever receives MD5(password), so that digest is all the plugin needs to keep.
 * It is stored as "md5:<digest>"; a value without the prefix is a plain password saved by an
 * older version and is converted on the next page load.
 */
class Credentials {

    public const PREFIX = 'md5:';

    /**
     * The value to store when the settings form is saved.
     *
     * @param string $submitted What the admin typed (the field is always rendered empty).
     * @param string $stored    The currently stored value.
     *
     * @return string
     */
    public static function to_store( string $submitted, string $stored ): string {
        if ( $submitted === '' ) {
            return self::is_digest( $stored ) || $stored === '' ? $stored : self::PREFIX . md5( $stored );
        }

        return self::PREFIX . md5( $submitted );
    }

    /**
     * The MD5 digest to send to the bank.
     *
     * @param string $stored
     *
     * @return string '' when no password is set.
     */
    public static function digest( string $stored ): string {
        if ( $stored === '' ) {
            return '';
        }

        return self::is_digest( $stored ) ? substr( $stored, strlen( self::PREFIX ) ) : md5( $stored );
    }

    /**
     * @param string $stored
     *
     * @return bool
     */
    public static function is_digest( string $stored ): bool {
        return strpos( $stored, self::PREFIX ) === 0 && preg_match( '/^[a-f0-9]{32}$/', substr( $stored, strlen( self::PREFIX ) ) ) === 1;
    }
}
