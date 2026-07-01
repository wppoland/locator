=== Plogins Locator - Store Locator for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, store locator, store finder, locations, shortcode
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.4
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show your physical store locations with a searchable, accessible list customers can filter by city, postcode or name.

== Description ==

Locator lists your physical shops on the storefront. You enter each location once
in wp-admin (name, address, city, postcode, country, phone and opening hours), then
add the `[locator]` shortcode to any page to print a searchable directory.

There is no map and no external service. No Google Maps key, no API call, no
tracking script. Every location is printed in the page HTML, so the directory is
visible even with JavaScript turned off. When JavaScript runs, the search box hides
and shows cards as the visitor types, filtering by city, postcode or store name
entirely in the browser.

Source and issues: https://github.com/wppoland/plogins-locator

**Features**

* Store Locations live as their own post type under the WooCommerce menu.
* Each location keeps its address, city, postcode, country, phone and opening hours.
* The `[locator]` shortcode renders the directory; you can leave the search box off if you only have a couple of shops.
* Search runs client-side over name, address, city, postcode and country. No request is sent while typing.
* Per-card display toggles for address, opening hours and phone (the store name always shows).
* The result count is announced through an ARIA live region, the search field is keyboard-operable, and cards use focus-visible outlines.
* Stylesheet and script load only on pages where the shortcode actually rendered, and the markup avoids layout shift.
* Storefront styles follow the visitor's light/dark preference and honour prefers-reduced-motion.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/locator`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Store Locations and add your stores.
4. Configure WooCommerce → Store Locator (search box and visible fields).
5. Add the `[locator]` shortcode to any page.

== Frequently Asked Questions ==

= Documentation and links =

* **Documentation** - https://plogins.com/plogins-locator/docs/
* **Plugin page** - https://plogins.com/plogins-locator/
* **Source code** - https://github.com/wppoland/plogins-locator
* **Bug reports and feature requests** - https://github.com/wppoland/plogins-locator/issues


= Does it require WooCommerce? =

Yes. Locator registers under the WooCommerce menu and requires WooCommerce to be active.

= Does it show a map? =

No. Locator renders a searchable list of cards, not a map, so it needs no map key or external service. You can still store latitude and longitude per location for use by an add-on.

= Which details can I show on each card? =

The store name is always shown. You can toggle address, opening hours and phone in the settings.

= How does the search box work? =

The `[locator]` shortcode filters location cards client-side as the shopper types.

= Can I add stores without custom code? =

Yes. Add locations under **WooCommerce → Store Locations** and place `[locator]` on any page.

== Screenshots ==

1. The searchable storefront directory.
2. The Store Locator settings page.

== External Services ==

Locator does not connect to any external service. It registers no remote API, sends no HTTP request, and loads no third-party script, font, map or tile. Your store data never leaves your site.

Every location is stored on your own server as a `locator_store` post, with its address, city, postcode, country, phone, opening hours, email and any latitude/longitude kept in that post's meta. Settings live in the `locator_settings` option. Coordinates are typed in by hand on the location screen - nothing is geocoded against an outside provider. The storefront search filters cards in the visitor's browser, so no request is made while typing, and the plugin sends no email.

== Changelog ==

= 0.1.4 =
* Renamed to Plogins Locator for WooCommerce for a more distinctive plugin name.

= 0.1.3 =
* Fix: store locations no longer take over the `manage_woocommerce` capability. Registering the locations post type mapped meta caps onto `manage_woocommerce`, which made every `manage_woocommerce` check fail while the plugin was active, hiding the whole WooCommerce admin menu (Settings, Status, Orders) and the plugin's own settings page.

= 0.1.2 =
* Programmatic store import via `StoreWriter` with `locator/import_store_fields` filter and `locator/store_imported` action.

= 0.1.1 =
* Add `locator/store_groups` filter so add-ons can group the storefront directory by region or country.

= 0.1.0 =
* Initial release: store-location post type, [locator] shortcode, searchable list, settings.
