=== GN In-Store Coupons ===
Tags: woocommerce, coupons
Requires PHP: 5.6.20
Requires at least: 6.5
Requires Plugins: woocommerce, mail-mint
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The GN In-Store Coupons plugin for WordPress.

== Description ==

GN In-Store Coupons is under development. This version provides the plugin
foundation and GitHub update support; coupon functionality is not yet implemented.

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
