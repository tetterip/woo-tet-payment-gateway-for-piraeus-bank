<p align="center">
  <img src=".github/icon.svg" width="112" height="112" alt="">
</p>

<h1 align="center">Piraeus Bank Payment Gateway for WooCommerce</h1>

<p align="center"><strong>Card, instalment and IRIS payments through the Piraeus Bank paycenter, with 3-D Secure v2 and Block Checkout.</strong></p>

<p align="center">
  <a href="https://github.com/tetterip/woo-tet-payment-gateway-for-piraeus-bank/releases/latest"><img alt="Latest release" src="https://img.shields.io/github/v/release/tetterip/woo-tet-payment-gateway-for-piraeus-bank?label=release&color=7c3aed"></a>
  <a href="LICENSE"><img alt="License" src="https://img.shields.io/github/license/tetterip/woo-tet-payment-gateway-for-piraeus-bank?color=2563eb"></a>
  <img alt="WordPress 4.0+" src="https://img.shields.io/badge/WordPress-4.0%2B-21759B?logo=wordpress&logoColor=white">
  <img alt="WooCommerce 5.0+" src="https://img.shields.io/badge/WooCommerce-5.0%2B-96588A?logo=woocommerce&logoColor=white">
  <img alt="PHP 7.4+" src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white">
</p>

<p align="center">
  <a href="https://github.com/tetterip/woo-tet-payment-gateway-for-piraeus-bank/releases/latest"><strong>Download</strong></a> ·
  <a href="https://plugins.ttrp.gr/#woo-tet-payment-gateway-for-piraeus-bank">plugins.ttrp.gr</a> ·
  <a href="CHANGELOG.md">Changelog</a> ·
  <a href="https://github.com/tetterip/woo-tet-payment-gateway-for-piraeus-bank/issues/new/choose">Report a bug</a> ·
  <a href=".github/CONTRIBUTING.md">Contributing</a>
</p>

<!--
  Screenshots: save PNGs (about 1600 px wide) in .github/screenshots/ and list them here, e.g.
  <p align="center"><img src=".github/screenshots/checkout.png" width="720" alt="…"></p>
-->

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
version. No order, customer or other personal data is sent. Questions: info@ttrp.gr. Details: [privacy policy](https://plugins.ttrp.gr/?page=privacy&lang=en) and [terms of use](https://plugins.ttrp.gr/?page=terms&lang=en); administrators see a one-time notice about this in wp-admin.

## Contributing

Bug reports, ideas and pull requests are welcome. See [CONTRIBUTING.md](.github/CONTRIBUTING.md) for how to set up, test and translate the plugin.

## Security

Please report vulnerabilities privately to **info@ttrp.gr**, not in a public issue. See [SECURITY.md](SECURITY.md).

## License

GPL-3.0-or-later — see [LICENSE](LICENSE).
