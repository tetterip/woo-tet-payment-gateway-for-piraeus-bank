# Piraeus Bank Payment Gateway for WooCommerce

WooCommerce payment gateway for Piraeus Bank (Greek market). Supports redirect-based card payments, pre-authorisation, free instalments, IRIS payments, and Block-based Checkout.

Based on [Piraeus Bank WooCommerce Payment Gateway](https://wordpress.org/plugins/woo-payment-gateway-for-piraeus-bank/) by Papaki (Enartia S.A.) (see [Credits](#credits)).

## Features

- Redirect-based payment via Piraeus Bank paycenter
- 3D Secure v2 / SCA compliant
- Pre-authorised transactions
- Free instalments (configurable max per order total)
- IRIS payment support
- Cardholder name field (optional)
- Block-based Checkout compatible
- HPOS compatible
- HTTP proxy support for servers without a static IP

## Requirements

| Requirement | Value |
|---|---|
| PHP | 7.4+ |
| WordPress | 4.0+ |
| WooCommerce | any recent version |
| Server | SOAP extension required |

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through **Plugins > Installed Plugins**
3. Go to **WooCommerce > Settings > Payments > Piraeus Bank** and configure

> **Shortcut:** After activation the gateway settings are also accessible via **ttrp.gr Plugins → Piraeus Bank** in the WordPress admin sidebar.

### Bank setup

Provide the following URLs to Piraeus Bank at OnlineServices@epayworldwide.gr:

**With permalinks:**
- Success: `https://yourdomain.gr/wc-api/WC_Piraeusbank_Gateway?peiraeus=success`
- Failure: `https://yourdomain.gr/wc-api/WC_Piraeusbank_Gateway?peiraeus=fail`
- Cancel: `https://yourdomain.gr/wc-api/WC_Piraeusbank_Gateway?peiraeus=cancel`

**Without permalinks:**
- Success: `https://yourdomain.gr/?wc-api=WC_Piraeusbank_Gateway&peiraeus=success`
- Failure: `https://yourdomain.gr/?wc-api=WC_Piraeusbank_Gateway&peiraeus=fail`
- Cancel: `https://yourdomain.gr/?wc-api=WC_Piraeusbank_Gateway&peiraeus=cancel`

## Credits

This plugin is a modified version of [Piraeus Bank WooCommerce Payment Gateway](https://wordpress.org/plugins/woo-payment-gateway-for-piraeus-bank/)
by **Papaki (Enartia S.A.)**, released under GPL-3.0-or-later. Original contributors: enartia, ggeorgopoulos, akatopodis.
That plugin is in turn based on [Piraeus Bank Greece Payment Gateway for WooCommerce](https://wordpress.org/plugins/woo-payment-gateway-piraeus-bank-greece/)
by **emspace.gr**. The `Papaki\PiraeusBank\WooCommerce` namespace comes from the Papaki version.

Modified and maintained by [ttrp.gr](https://ttrp.gr) since 2026-04: HPOS compatibility, Block Checkout support,
PHP 7.4/8.x fixes and ongoing maintenance. See [CHANGELOG.md](CHANGELOG.md) for all changes.

This is not an official plugin of Piraeus Bank and is not endorsed by it. "Piraeus Bank" is a trademark of its
owner and is used only to describe compatibility.

## License

GPL-3.0-or-later — see [LICENSE](LICENSE).
