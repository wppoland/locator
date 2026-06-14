# Locator - Store Locator for WooCommerce

Locator shows your physical store locations as a searchable, accessible list customers can filter by city, postcode or name. It is privacy-friendly and needs no external API or map key — all data stays on your site.

## Features

- Manage store locations under **WooCommerce → Store Locations** (name, address, city, postcode, country, phone, email, opening hours and optional coordinates).
- `[locator]` shortcode renders a searchable directory on any page.
- Instant client-side filtering by city, postcode or name — no data leaves the page, and the list still works with JavaScript disabled.
- List or responsive grid layout, with control over which fields appear per card.
- "Get directions" links built from coordinates or the postal address.
- Accessible and Core Web Vitals friendly: assets load only when the shortcode is present, and the layout is dark-mode aware.
- Theme-overridable template: `your-theme/locator/locator-list.php`.

## Installation

1. Upload the plugin to `/wp-content/plugins/locator`, or install it via **Plugins → Add New**.
2. Activate it. WooCommerce must be active.
3. Add your stores under **WooCommerce → Store Locations**, then place the `[locator]` shortcode on a page.

## Frequently Asked Questions

**Does it need a Google Maps or other API key?**
No. Locator is self-contained and renders a searchable list with no external map service.

**Does the directory work without JavaScript?**
Yes. Every store is server-rendered, so the list is fully visible; JavaScript only adds instant filtering.

Built by WPPoland — https://plogins.com

License: GPL-2.0-or-later
