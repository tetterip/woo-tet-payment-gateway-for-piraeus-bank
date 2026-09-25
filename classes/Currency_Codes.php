<?php

namespace Papaki\PiraeusBank\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ISO 4217 alphabetic → numeric currency codes for the ticket request's CurrencyCode.
 *
 * Which currencies a merchant can actually charge in depends on their Piraeus Bank agreement
 * (almost always EUR only). Sending the order's real currency means an unsupported one is
 * rejected by the bank instead of the amount being charged as euros.
 */
class Currency_Codes {

    private const NUMERIC = [
        'EUR' => '978',
        'USD' => '840',
        'GBP' => '826',
        'CHF' => '756',
        'BGN' => '975',
        'RON' => '946',
        'CZK' => '203',
        'PLN' => '985',
        'HUF' => '348',
        'SEK' => '752',
        'DKK' => '208',
        'NOK' => '578',
        'CAD' => '124',
        'AUD' => '036',
        'JPY' => '392',
        'TRY' => '949',
        'RSD' => '941',
        'ALL' => '008',
        'MKD' => '807',
    ];

    /**
     * @param string $currency ISO 4217 alphabetic code, e.g. "EUR".
     *
     * @return string Numeric code, or '' when unknown.
     */
    public static function numeric( string $currency ): string {
        return self::NUMERIC[ strtoupper( trim( $currency ) ) ] ?? '';
    }
}
