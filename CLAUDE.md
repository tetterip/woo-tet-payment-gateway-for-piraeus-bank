# Piraeus Bank Payment Gateway for WooCommerce — Claude Code Context

## Project overview

WooCommerce payment gateway for Piraeus Bank (Greek market). Uses a redirect flow to the bank's paycenter. Supports pre-auth, instalments, IRIS, 3DS v2, and Block-based Checkout.

Forked from the abandoned upstream plugin by emspace.gr; our primary contributions are HPOS compatibility, Block Checkout support, PHP 7.4/8.x fixes, and ongoing maintenance.

## Architecture

```
wooshop-piraeus.php                          – Plugin entry point; autoloader, feature declarations, bootstraps Application, ttrp.gr Plugins menu
classes/
  Application.php                            – Registers hooks, gateway filter, language loading, state additions (CY, DE)
  WC_Piraeusbank_Gateway.php                 – Core gateway: settings, form generation, SOAP calls, response handling
  Checkout_Block.php                         – Block Checkout integration; registers payment method type, cardholder field
  WC_Piraeusbank_Gateway_Checkout_Block.php  – Block-side payment method type (JS + PHP glue)
assets/js/blocks/
  checkout.js                                – Block Checkout frontend script
languages/
  woo-payment-gateway-for-piraeus-bank-el.*  – Greek translation (.po / .mo)
```

## Key patterns

**Namespace** — all classes use `Papaki\PiraeusBank\WooCommerce\`. The autoloader in `wooshop-piraeus.php` maps this to `classes/`.

**HPOS compatibility** — declared via `FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true)` on `before_woocommerce_init` at the top level of the entry point (not inside a callback), so it fires before `plugins_loaded`.

**ttrp.gr Plugins menu** — `ttrp_pb_maybe_register_brand_menu()` / `ttrp_pb_add_submenu()` / `ttrp_pb_redirect()` at the bottom of `wooshop-piraeus.php`. The submenu redirects via `admin_init` to `wc-settings&tab=checkout&section=piraeusbank_gateway`. Top-level menu shared across all ttrp.gr plugins; guarded by `global $menu` check and `function_exists('ttrp_brand_dashboard')`.

**Feature declarations must be top-level** — `before_woocommerce_init` fires before `plugins_loaded`; any declaration registered inside `plugins_loaded` will never run.

**Payment flow** — redirect to Piraeus paycenter → bank POSTs back to `/?wc-api=WC_Piraeusbank_Gateway` → `check_piraeusbank_response()` handles success / fail / cancel.

**SOAP** — all bank communication uses PHP's SOAP extension. Server must have it installed.

**Text domain** — `woo-payment-gateway-for-piraeus-bank` (matches `Application::PLUGIN_NAMESPACE`).

## Compatibility targets

| Requirement | Value |
|---|---|
| PHP | 7.4+ (no deprecations on 8.2/8.3) |
| WordPress | 4.0+ |
| WooCommerce | any recent version |
| WC tested up to | keep in sync with latest stable WC |

## Versioning

Follows [Semantic Versioning](https://semver.org/). Always update `Version:` in `wooshop-piraeus.php` and add a changelog entry in `CHANGELOG.md` when releasing.

Current version: **3.4.2**

## Commit style

Short imperative subject line, no period. Body optional. Example:

```
Fix cardholder name field not cleared after failed payment
```

## Known issues / technical debt

These have been identified but not yet fixed — pick them up before adding new features:

- **`functions.php` is dead code** — `piraeusbank_message()` there is a duplicate of `Application::piraeusbank_message()` and the file is never `require`d. Safe to delete.
- **`Application::declare_transactions()`** — calls `FeaturesUtil::declare_compatibility()` with `$wpdb->prefix . 'piraeusbank_transactions'` as the feature ID, which is not a valid WooCommerce feature. Investigate and remove or correct.
- **`validate_fields()` skips Block Checkout** — returns `false` immediately on REST requests without checking if fields are actually present, meaning Block Checkout never validates required billing fields.
- **`CurrencyCode` hardcoded as `'978'` (EUR)** — `generate_piraeusbank_form()` always sends EUR regardless of WooCommerce currency setting. Should map `get_woocommerce_currency()` to ISO 4217 numeric code.
- **`admin_options()` uses raw cURL** — fetches server IP via `curl_init()` instead of `wp_remote_get()`, bypassing WordPress HTTP API and proxy settings.
- **No `uninstall.php`** — `wp_piraeusbank_transactions` table and all plugin options persist in the database after uninstallation.
- **Installments logic duplicated** — the `pb_installments_variation` parsing loop appears in `payment_fields()`, `Checkout_Block::calculate_max_installments()`. Should be extracted to a single method on the gateway.

## Security notes

- Bank password is stored plaintext in WP options (unavoidable — SOAP API requires MD5 of the raw password at call time).
- Response authenticity is verified via SHA-256 and HMAC-SHA256 hash of the transaction ticket before any order status change.
