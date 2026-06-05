# Piraeus Bank Payment Gateway for WooCommerce

WooCommerce payment gateway for Piraeus Bank (Greek market). Supports redirect-based card payments, pre-authorisation, free instalments, IRIS payments, and Block-based Checkout.

Based on the original plugin by [emspace.gr](https://wordpress.org/plugins/woo-payment-gateway-piraeus-bank-greece/).

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

## License

GPL-3.0-or-later — see [http://www.gnu.org/licenses/gpl-3.0.txt](http://www.gnu.org/licenses/gpl-3.0.txt)
