=== GN In-Store Coupons ===
Tags: woocommerce, coupons
Requires PHP: 7.4
Requires at least: 6.5
Requires Plugins: woocommerce, mail-mint
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The GN In-Store Coupons plugin for WordPress.

== Description ==

Issue a branded, single-use in-store coupon to newly registered WooCommerce
customers and subscribed contacts in selected Mail Mint lists. Coupons are
emailed automatically when issuance is enabled. They never work at online checkout.

The In-Store Coupons admin menu contains a searchable register and settings for
discount percentage, product categories, expiry, store logo, color, and terms.
Administrators configure issuance; administrators and WooCommerce shop managers
can inspect, redeem, revoke, and resend the same coupon.

One coupon is allowed per normalized email address and linked WordPress account,
ever. Redeemed, expired, and revoked records remain in the permanent ledger.
Different email addresses belonging to the same person cannot be identified
unless linked to the same WordPress account. Coupon records and settings are
retained on uninstall to avoid accidentally resetting lifetime eligibility.

== Configuration ==

Issuance starts paused. In In-Store Coupons > Settings, choose the discount,
categories (none means all), validity (0 means no expiry), branding and Mail Mint
lists. Enabling automatic issuance emails both existing subscribed contacts in
those lists and future eligible customers. Mail Mint Pro is not required.

Mail Mint contacts are processed in batches of 50; email batches contain up to 25
coupons. Background work uses WordPress Cron and requires site traffic or a server
cron runner. Failed emails can be retried from the coupon detail screen without
issuing another code. "Accepted by mailer" is not confirmation of inbox delivery.
An interrupted send remains unconfirmed; staff may retry the same coupon after
10 minutes, which can resend a previously accepted email.

Issued coupons retain a snapshot of their discount, categories and branding.
Staff verify applicable products and record redemption before completing payment.
Public coupon links are secret bearer links with no customer email address shown.
Exclude the gn_store_coupon query parameter from any full-page/CDN cache.

== Installation ==

1. Use WordPress 6.5 or newer and install and activate WooCommerce and Mail Mint.
2. Upload the gn-in-store-coupons directory to wp-content/plugins/.
3. Activate GN In-Store Coupons through the Plugins screen in WordPress.

WordPress blocks activation until both required plugins are installed and active.
Mail Mint Pro is optional and does not replace the required Mail Mint plugin.

== Updates ==

Updates are provided by the public GitHub repository:
https://github.com/GeorgeWebDevCy/gn-in-store-coupons

Plugin Update Checker checks for updates periodically. Use "Check for updates"
on the WordPress Plugins screen to check manually. No GitHub token is required.
WordPress controls whether available updates are installed automatically.

The checker uses the latest non-prerelease GitHub release, then the highest
version tag, and falls back to the main branch when neither is available.
Update packages use GitHub source archives and include the bundled updater.

== Changelog ==

= 1.1.0 =
* Add lifetime issuance ledger, staff coupon register, redemption and revocation.
* Add automatic WooCommerce/Mail Mint eligibility and queued coupon email.
* Add branded coupon views, expiry, settings, previews, and delivery status.

= 1.0.2 =
* Require WooCommerce and Mail Mint through WordPress native plugin dependencies.
* Require WordPress 6.5 or newer for dependency enforcement.

= 1.0.1 =
* Integrate Plugin Update Checker 5.7 for GitHub-hosted WordPress updates.

= 1.0.0 =
* Initial plugin foundation.

== Third-party Libraries ==

Plugin Update Checker 5.7 by Janis Elsts is bundled under
includes/plugin-update-checker/ and licensed under the MIT license.
Source: https://github.com/YahnisElsts/plugin-update-checker/tree/v5.7
The upstream license is included in that directory as license.txt.
