=== Locator - Store Locator for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, store locator, store finder, locations, shortcode
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your physical store locations with a searchable, accessible list customers can filter by city, postcode or name.

== Description ==

Locator adds a simple, fast store-locator to your WooCommerce shop. Add each
physical store — name, address, phone and opening hours — as a managed location,
then drop the `[locator]` shortcode on any page to render a clean, searchable
directory your customers can instantly filter by city, postcode or name.

The plugin is a self-contained, privacy-friendly directory: there is no external
API, no tracking and no map key required. Every location is rendered server-side,
so the list works even with JavaScript disabled; when JavaScript is available the
search box filters cards instantly in the browser.

**Features**

* Manage store locations as a dedicated post type under WooCommerce.
* Each location stores address, city, postcode, country, phone and opening hours.
* `[locator]` shortcode renders a searchable, accessible directory.
* Instant client-side filtering by city, postcode or store name — no data leaves the page.
* Choose which fields appear on each card (address, opening hours, phone).
* Accessible by design: ARIA live region for result counts, keyboard-friendly, focus-visible styles, screen-reader text.
* Core Web Vitals friendly: no layout shift, assets enqueued only when the shortcode is on the page.
* Dark-mode aware admin and storefront styles; respects prefers-reduced-motion.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/locator`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Store Locations and add your stores.
4. Configure WooCommerce → Store Locator (search box and visible fields).
5. Add the `[locator]` shortcode to any page.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Locator registers under the WooCommerce menu and requires WooCommerce to be active.

= Does it show a map? =

No. Locator renders a searchable list — no map key or external service needed.

= Which details can I show on each card? =

The store name is always shown. You can toggle address, opening hours and phone in the settings.

== Screenshots ==

1. The searchable storefront directory.
2. Managing a store location in the admin.
3. The Store Locator settings page.

== Changelog ==

= 0.1.0 =
* Initial release: store-location post type, [locator] shortcode, searchable list, settings.
