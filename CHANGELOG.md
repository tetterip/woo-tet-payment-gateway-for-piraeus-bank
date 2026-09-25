# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [3.6.0] - 2026-09-25

### Added
- **Admin warning when the bank rejects the credentials.** ResultCode 100 (Authentication Error) means the Username or Password of the Ticketing Web Service is wrong, and it blocks every card payment. Until now it only showed up as a failed customer payment and an order note. A warning now appears on every admin page, saying since when and with the bank's response, with a link to the settings. It cannot be dismissed and disappears by itself after the next successful payment attempt.
- **Daily check for payments without a bank response.** Orders that were sent to the bank but never got a response (for example because a firewall blocked it) are flagged once, with an order note listing their MerchantReference, and listed in a dismissible admin notice. Order status is never changed: most are abandoned payments, but an approved payment whose response was lost looks the same, so they are reported for checking in the Piraeus Bank AdminTool.
- **Callback diagnostics** on the gateway settings page:
  - **Test callback URL** sends a realistic declined-payment response from the server to its own Success and Failure URLs and reports whether it reached the plugin. It detects host firewalls (ModSecurity, Imunify360, BitNinja, LiteSpeed, security plugins) that block the bank's responses. No order is created or changed.
  - **Cloudflare detection**: when the site is served through Cloudflare, the page explains how to add a WAF skip rule for the callback path and lists Cloudflare's current IPv4 ranges to copy.
  - A **Copy all to clipboard** button for the technical data to send to the bank.
- **Google Pay**: the `PanCardType` response field (FPAN = real card number, DPAN = device token) is stored on the order, and the order note says "Paid via Google Pay".
- `uninstall.php`: deleting the plugin removes its transaction-ticket table, settings, cached data and scheduled task. Order data is kept.

### Changed
- **Server IP**: the settings page now shows the server's **outbound** IP (the value the bank must register) separately from the IP the website is reached on, and warns when they differ. Previously a single "Server IP" was shown, which on some hosts was the wrong one. The lookup now uses the WordPress HTTP API instead of raw cURL. If the IP cannot be found, the page explains how to get it. A host can supply it with the `piraeusbank_outbound_ip` filter.
- **3-D Secure phone numbers**: a Greek number is now sent only in the field that matches its type (69x → MobilePhone, 2x → HomePhone) instead of in all three fields. This gives the card issuer better data for approving payments without a challenge screen. Athens numbers starting 211 / 213 were previously mistaken for South Sudan / Algeria prefixes. Numbers from other countries are sent as before.
- **Declined payments** now return the customer to the order's pay page, with the decline message and a way to try again, instead of the order-received page. This applies when the return page setting is the default "Thank You Page".
- **Currency**: the order's currency is sent to the bank instead of always EUR (978). The gateway is hidden when the store currency has no known ISO 4217 code, so an amount can no longer be charged as euros by mistake.
- **Bank password**: only the MD5 hash the bank needs is stored, not the password itself. An existing password is converted automatically on the first page load after the update. The field now always appears empty: leave it blank to keep the saved password.
- The ticket table is pruned: rows older than 30 days are deleted by the daily check.
- The settings-page texts are now translatable, including the note about the callback URLs, which was hardcoded in Greek.

---

## [3.5.1] - 2026-09-25

The payment-handling changes below shipped in the 3.5.0 code but were missing from its changelog. This release adds their Greek translations and documents them.

### Security
- **Bank callbacks are now authenticated before any order is changed.** Previously anyone could send a request to the callback URL with a guessed order number and set that order to *Failed*, including orders that were already paid, and orders paid with other gateways. Now:
  - The MerchantReference sent to the bank is `{order number}-{12 random characters}`. The random part is a per-attempt secret, stored with the transaction ticket, and a callback must echo it back.
  - The order must have been paid with this gateway, and an order that is already paid is never changed.
  - A response whose HashKey does not verify no longer marks the order *Failed*. The status stays the same and an order note asks you to check the transaction in the Piraeus Bank AdminTool.
- References in the old format (plain order number) from payments started before the update are accepted for 24 hours, so payments in progress during the update still complete.

### Fixed
- **Failed and cancelled payments were ignored.** The failure and cancel callbacks were checked against the customer's WooCommerce session, but the bank sends them cross-site and browsers do not send the session cookie with them (SameSite=Lax). So declined payments stayed in *Pending payment* with no message. They are now authenticated by the MerchantReference instead.
- **Cancel on the bank page** now cancels the order and shows "Your payment was cancelled" when the bank's cancel request includes the MerchantReference. Otherwise the customer is returned to the checkout as before.
- **IRIS ResponseCode 09** (transfer started but not yet confirmed) put the order in *Failed*, even though the payment may still settle. IRIS payments cannot be refunded, so a customer paying again could be charged twice. The order now goes to *On hold* with a note to check it in the AdminTool, and the customer is told not to pay again.
- The custom return page redirect passed an undefined `type` value.

### Removed
- **Pre-authorisation** (the "Pre-Authorize" setting and its admin notice). Pre-authorised orders were marked paid although the funds were only reserved and had to be captured in the AdminTool, otherwise they expired. Piraeus Bank has been phasing the service out. Every transaction is now a Sale (captured immediately). Stores that had the setting enabled switch to Sale automatically.

### Changed
- In the Piraeus Bank AdminTool, transactions now appear as `1234-A8K2…` instead of `1234`. The order number is still at the start, so searching by order number still works. The callback URLs registered with the bank do not change.

### Added
- `tests/run.php`: zero-dependency tests for the MerchantReference and IRIS helpers (`php tests/run.php`). Excluded from the release zip.

---

## [3.5.0] - 2026-09-25

### Added
- **"All Plugins" page** under **ttrp.gr Plugins**: lists the ttrp.gr plugins available to this site, with install / activate / update buttons. Installation goes through WordPress's own installer. This comes from the shared `ttrp-common/` library, which is bundled in each ttrp.gr plugin; only the newest copy on a site is loaded.

### Changed
- Updates now come through `ttrp-common/` instead of `update-checker.php` (removed). The update check also sends the installed plugin version, so the update server can show which site runs which version.

---

## [3.4.8] - 2026-09-25

### Fixed
- Credits corrected: the direct upstream is *Piraeus Bank WooCommerce Payment Gateway* by Papaki (Enartia S.A.; contributors enartia, ggeorgopoulos, akatopodis), which is still listed and maintained on wordpress.org, and is itself based on the emspace.gr original. 3.4.7 wrongly said the original was no longer listed. The README and the header `Based on` line are updated.

---

## [3.4.7] - 2026-09-25

### Added
- `LICENSE` file with the full GPL-3.0 text.
- README *Credits* section: the original plugin by emspace.gr, later maintained by Papaki, the modifications by ttrp.gr, and a note that this is not an official Piraeus Bank plugin. The `Based on` header line now also names Papaki.

---

## [3.4.6] - 2026-09-25

### Changed
- `update-checker.php`: passes plugin icons and banners from the update server to WordPress, so the plugin gets its icon in **Dashboard → Updates** and the details modal.

---

## [3.4.5] - 2026-06-05

### Changed
- Update the technical-setup contact email shown in the gateway settings (and README) from `epayments@piraeusbank.gr` to `OnlineServices@epayworldwide.gr`. Updated the English source string and the Greek translation, and recompiled the `.mo`.

### Fixed
- Greek translation of that notice used curly quotes (`“ ”`) around the `mailto:` link, producing a broken `href`; replaced with straight quotes so the email link works.

---

## [3.4.4] - 2026-05-27

### Fixed
- Remove UTF-8 BOM from `update-checker.php` — the BOM was causing a stray character to be output before headers on sites that use the update checker's directory-rename feature.

---

## [3.4.3] - 2026-05-27

### Fixed
- Guard `$wp_filesystem` global before calling `move()` in the update checker's `fix_directory_name()` — call `WP_Filesystem()` if not already initialised to prevent a fatal error during plugin updates.

### Changed
- Tested up to WordPress 7.0.

---

## [3.4.2] - 2026-05-14

### Fixed
- Block Checkout installments now behave like classic checkout: the field is hidden when the cart total does not qualify for any installment options, and it is not registered at all when the merchant has not configured installments.

---

## [3.4.1] - 2026-05-13

### Fixed
- Removed UTF-8 BOM from the main plugin file. The BOM was being output before the HTML document, causing a stray character to appear at the start of every page.

---

## [3.4.0] - 2026-05-12

### Added
- **ttrp.gr Plugins** top-level admin menu with a **Piraeus Bank** submenu entry that links directly to the gateway settings (**WooCommerce → Settings → Payments → Piraeus Bank**). The menu is shared across all ttrp.gr plugins — whichever plugin loads first registers it.

---

## [3.3.1] - 2026-05-08

### Changed
- Renamed plugin to **Piraeus Bank Payment Gateway for WooCommerce** (follows WooCommerce title guidelines — feature name first, "for WooCommerce" as suffix).

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
