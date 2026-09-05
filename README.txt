=== GN In-Store Coupons ===
Tags: woocommerce, coupons
Requires PHP: 5.6.20
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The GN In-Store Coupons plugin for WordPress.

== Description ==

GN In-Store Coupons is under development. This version provides the plugin
foundation and GitHub update support; coupon functionality is not yet implemented.

== Installation ==

1. Upload the gn-in-store-coupons directory to wp-content/plugins/.
2. Activate GN In-Store Coupons through the Plugins screen in WordPress.

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

= 1.0.1 =
* Integrate Plugin Update Checker 5.7 for GitHub-hosted WordPress updates.

= 1.0.0 =
* Initial plugin foundation.

== Third-party Libraries ==

Plugin Update Checker 5.7 by Janis Elsts is bundled under
includes/plugin-update-checker/ and licensed under the MIT license.
Source: https://github.com/YahnisElsts/plugin-update-checker/tree/v5.7
The upstream license is included in that directory as license.txt.
