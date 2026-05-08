# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.3.0] - 2026-05-08

### Added
- Self-hosted update checker (`TTRP_Update_Checker`) via `update-checker.php`: integrates with `plugins.ttrp.gr`, shows update notifications in WP admin, populates the plugin info modal, and fixes the plugin directory name after a GitHub-zip update.
- GitHub Actions release workflow (`.github/workflows/release.yml`): builds a clean plugin zip (dev files excluded) and publishes a GitHub Release on tag push.

## [3.2.4] - 2026-04-18

### Changed
- Replace deprecated `global $woocommerce` with `WC()` in `payment_fields()`. Added null guard for cart object to prevent errors in admin context.

## [3.2.3] - 2025-xx-xx

### Added
- HPOS (High-Performance Order Storage) compatibility declaration
- Block-based Checkout support (`Checkout_Block`, `WC_Piraeusbank_Gateway_Checkout_Block`)
- 3DS v2 fields: billing/shipping address, phone, cardholder name, email sent to bank
- IRIS payment detection via `PaymentMethod` and `CardType` fields in response
- Session-based validation for fail/cancel callbacks to prevent replay
- `safe_log()` with sensitive data redaction (password, HashKey)
- HMAC-SHA256 hash verification alongside legacy SHA256
- Installments variation by order amount (`pb_installments_variation`)
- Cardholder name field for Block Checkout via `woocommerce_register_additional_checkout_field`

### Changed
- Forked from abandoned upstream plugin by emspace.gr
- Refactored into PSR-4 namespace `Papaki\PiraeusBank\WooCommerce\`
- Replaced global functions in `functions.php` with class methods in `Application` and `WC_Piraeusbank_Gateway`
- PHP 7.4/8.x compatibility fixes (typed properties, null coalescing, removed deprecated patterns)

### Fixed
- Feature declarations moved to top-level of entry point (outside `plugins_loaded`) so `before_woocommerce_init` fires correctly
- Duplicate hook registration when `WC_Piraeusbank_Gateway` was instantiated multiple times
