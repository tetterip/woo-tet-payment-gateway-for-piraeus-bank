# Piraeus Bank WooCommerce Payment Gateway — Claude Code Context

## Project overview

WooCommerce payment gateway for Piraeus Bank (Greek market). Uses a redirect flow to the bank's paycenter. Supports pre-auth, instalments, IRIS, 3DS v2, and Block-based Checkout.

Forked from the abandoned upstream plugin by emspace.gr; our primary contributions are HPOS compatibility, Block Checkout support, PHP 7.4/8.x fixes, and ongoing maintenance.

## Architecture

```
wooshop-piraeus.php                          – Plugin entry point; autoloader, feature declarations, bootstraps Application
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

Follows [Semantic Versioning](https://semver.org/). Always update `Version:` in `wooshop-piraeus.php` and add a changelog entry when releasing.

## Commit style

Short imperative subject line, no period. Body optional. Example:

```
Fix cardholder name field not cleared after failed payment
```
