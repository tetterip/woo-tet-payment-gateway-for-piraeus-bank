# Piraeus Bank Payment Gateway for WooCommerce — Claude Code Context

## Project overview

WooCommerce payment gateway for Piraeus Bank (Greek market). Uses a redirect flow to the bank's paycenter. Supports instalments, IRIS, 3DS v2, and Block-based Checkout.

Modified fork of *Piraeus Bank WooCommerce Payment Gateway* by Papaki (Enartia S.A.), GPL-3.0-or-later ([wordpress.org](https://wordpress.org/plugins/woo-payment-gateway-for-piraeus-bank/)), itself based on the emspace.gr original. Upstream is **still maintained** (v3.2.0, Jan 2026), so check it for bank-side fixes worth porting. Our primary contributions are HPOS compatibility, Block Checkout support, PHP 7.4/8.x fixes, and ongoing maintenance.

**GPL attribution is mandatory:** keep the `LICENSE` file, the `Based on` header line and the README *Credits* section (original authors + "not an official Piraeus Bank plugin"). Never remove upstream author references.

## Architecture

```
wooshop-piraeus.php                          – Plugin entry point; autoloader, feature declarations, bootstraps Application, ttrp.gr Plugins menu
classes/
  Application.php                            – Registers hooks, gateway filter, language loading, state additions (CY, DE)
  WC_Piraeusbank_Gateway.php                 – Core gateway: settings, form generation, SOAP calls, response handling; bank setup data + diagnostics render below the Save button via `woocommerce_after_settings_checkout` (added from `admin_options()`)
  Checkout_Block.php                         – Block Checkout integration; registers payment method type, cardholder field
  WC_Piraeusbank_Gateway_Checkout_Block.php  – Block-side payment method type (JS + PHP glue)
  Payment_Reference.php                      – Pure: MerchantReference build/parse, IRIS detection (tested)
  Phone_Numbers.php                          – Pure: Greek phone → 3DS MobilePhone/HomePhone routing (tested)
  Currency_Codes.php                         – Pure: ISO 4217 alpha → numeric CurrencyCode (tested)
  Credentials.php                            – Pure: password stored as "md5:<digest>" (tested)
  Health.php                                 – ResultCode 100 admin notice; daily reconciliation cron (`piraeusbank_reconcile`) + ticket-table pruning
  Diagnostics.php                            – Settings-page diagnostics: callback URLs, outbound IP, Cloudflare, "Test callback URL" AJAX
assets/js/
  admin-settings.js                          – Copy buttons + callback test on the gateway settings page
uninstall.php                                – Drops the tickets table, options, transients, cron
tests/
  run.php                                    – Zero-dependency tests: `php tests/run.php`
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

**Payment flow** — redirect to Piraeus paycenter → bank POSTs back to `/?wc-api=WC_Piraeusbank_Gateway` → `check_piraeusbank_response()` handles success / fail / cancel. Always a Sale (`RequestType 02`); pre-authorisation was removed in 3.5.0 (no capture step existed, yet orders were marked paid).

**Callback authentication** — the bank POSTs cross-site, so the WC session cookie is **not** sent (SameSite=Lax): never use the session to validate a callback. Instead the `MerchantReference` is `{order_id}-{12 random chars}` (`Payment_Reference`), stored with its ticket in `wp_piraeusbank_transactions.merch_ref`; a callback must match a stored row, the order must use this gateway, and a paid order is never changed. A HashKey that fails verification leaves the order unchanged (note only). Declines on the fail URL usually carry an empty HashKey, so the reference secret is their only authentication. Plain order-ID references (pre-3.5.0) are accepted for 24h only. IRIS `ResponseCode 09` = transfer pending → on-hold, never failed.

**Customer messages** — callbacks store the message in `_piraeusbank_message` order meta (never `wc_add_notice()`: the session is not available in the bank's POST); `Application::piraeusbank_message()` shows it on the order-received page, or on the order's pay page where declined payments are redirected (default return-page setting). A MerchantReference starting `WAFTEST-` is the settings-page callback test: the handler answers with the `X-Piraeusbank-Callback: reached` header and touches nothing. Behind Cloudflare the test pins the hostname to `SERVER_ADDR` (cURL `CURLOPT_RESOLVE` via `http_api_curl`) so it tests the origin firewall; Cloudflare's bot protection 403s server-sent requests, while the real callbacks come from the customer's browser. A 403 with `cf-ray` / `server: cloudflare` is reported as a warning, not "Blocked".

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
- **Installments logic duplicated** — the `pb_installments_variation` parsing loop appears in `payment_fields()`, `Checkout_Block::calculate_max_installments()`. Should be extracted to a single method on the gateway.

## Security notes

- Bank password: only `md5:<digest>` is stored (the digest is what the SOAP call sends); plain values from older versions are converted in the gateway constructor. The settings field (`pb_secret` type) always renders empty.
- Success responses are verified via SHA-256 / HMAC-SHA256 of the attempt's transaction ticket before any order status change; see *Callback authentication* above for the rest.
