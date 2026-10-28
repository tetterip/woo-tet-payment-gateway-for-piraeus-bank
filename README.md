# Piraeus Bank Payment Gateway for WooCommerce

WooCommerce payment gateway for Piraeus Bank (Greek market). Supports redirect-based card payments, free instalments, IRIS payments, and Block-based Checkout.

Based on [Piraeus Bank WooCommerce Payment Gateway](https://wordpress.org/plugins/woo-payment-gateway-for-piraeus-bank/) by Papaki (Enartia S.A.) (see [Credits](#credits)).

## Features

- Redirect-based payment via Piraeus Bank paycenter
- 3D Secure v2 / SCA compliant
- Free instalments (configurable max per order total)
- IRIS payment support
- Cardholder name field (optional)
- Block-based Checkout compatible
- HPOS compatible
- HTTP proxy support for servers without a static IP
- Greek phone numbers sent in the matching 3-D Secure field (mobile / fixed line)
- Google Pay payments recorded on the order (FPAN / DPAN)
- Declined payments return to the order's pay page with a "try again" option
- Admin warning when the bank rejects the credentials, and a daily check for payments that never got a bank response
- Settings-page diagnostics: server outbound IP, Cloudflare detection, and a "Test callback URL" check for host firewalls
- Bank password stored only as the hash the bank needs

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

## Updates and privacy

This plugin gets its updates from the ttrp.gr update server (`https://plugins.ttrp.gr/`), not from WordPress.org.
When WordPress checks for updates, and when you open **ttrp.gr Plugins → All Plugins**, your site sends that server:

- the plugin's slug and installed version,
- the WordPress version and the site's address (in the request's `User-Agent` header),
- the admin language (All Plugins page only).

The server logs these requests, with the IP address, for 180 days and keeps a list of which sites run which plugin
version. No order, customer or other personal data is sent. Questions: info@ttrp.gr.

## Security

Please report vulnerabilities privately to **info@ttrp.gr**, not in a public issue. See [SECURITY.md](SECURITY.md).

## License

GPL-3.0-or-later — see [LICENSE](LICENSE).
