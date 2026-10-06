# Changelog

All notable changes to the Tabarak Electronics platform.

## Tabarak Electronics Child 1.11.0 - UX overhaul (2026-10-06)

### Navigation
- New primary mega menu: *All Categories* panel (top 30 categories with product counts and a deals promo), *Brands* panel (logo grid), *Hot Deals*, quick links (TVs, Fridges, Cookers, Washing, Audio, Small Kitchen), plus Delivery, Warranty, Contact and a *Call to order* number. Works with mouse, click and keyboard (Esc and Arrow Down).
- Mobile drawer now has quick tiles for Hot Deals, Delivery, Warranty, FAQs and Contact, and Call and WhatsApp buttons.
- New `?tab_sale=1` filter: Hot Deals shows only discounted products, with a clear "Show all products" chip.

### Product pages
- Two-column layout with a sticky gallery on desktop, and Brand, In stock and SKU chips under the title.
- The price is now dark and bold with the old price struck through. This fixes the olive, underlined WooCommerce default.
- Quantity stepper (+/-), a highlighted add-to-cart panel and full-width buttons on mobile.
- A sticky add-to-cart bar appears after you scroll past the buy button, and sits above the mobile bottom navigation.
- Products whose description only repeats their name now show an *Overview* with a spec table (category, brand, model, attributes, warranty, delivery, payment).
- New *Delivery & Warranty* tab, a *Need advice?* call box and restyled tabs.

### Pages
- New page template with a dark branded hero (breadcrumb, title, intro), a readable content column and a *Still have a question?* help strip.
- Fixed About and Contact rendering in a monospace font inside a box. Their content was wrapped in a table and code block in the editor, and it is now unwrapped automatically.
- Fixed the duplicate "Warranty & Service" heading.
- New 404 page with search, shop buttons and popular categories.

### Shop, cart, checkout, account
- Styled sort dropdown, pagination, notices, form fields and buttons site-wide.
- Product cards have equal heights, two-line titles and a hover zoom.
- Cart and checkout (classic and block) use card layouts, and the empty cart shows popular categories.
- Login and register sit side by side as cards, and the account navigation is styled.

### Fixes
- The footer *Privacy Policy* link pointed to a draft page and returned a 404. It now only links published pages, and falls back to the WordPress privacy page.
- The reCAPTCHA badge no longer covers *Cart* in the mobile bottom navigation.
- The menu and homepage caches now clear when products or categories change.
- Dark mode covers all the new components.

## 2026-10-06 (README visuals)
- Added a branded README banner, framed browser screenshots, a three-phone mobile showcase and a colour palette card.
- Switched README images to HTML so they render reliably on GitHub, and added a stats bar and table of contents.
- Styled the architecture diagrams in brand colours and added a customer-journey diagram and an asset-loading table.

## 2026-10-06

### Repository
- Rewrote the root README with live screenshots, architecture diagrams, feature tables, configuration and troubleshooting.
- Added `docs/screenshots/` (home, shop, category, product and mobile).
- Expanded the child theme README and updated the installation guide to cover the child theme and the Tabarak Core plugin.
- Added `.gitignore` and this changelog.

### Tabarak Electronics (parent) 1.2.1
- Fixed `TABARAK_VERSION` (was `1.0.0` while the theme header said `1.2.0`), so CSS and JS cache-busting now follows the real version.
- Removed the unused `fonts.gstatic.com` preconnect. The theme uses a system font stack, so the hint only cost an extra connection.
- Removed a misleading RTL registration that pointed to a stylesheet that does not exist.

### Tabarak Electronics Child 1.10.1
- Compressed the four homepage hero images from about 2.5 MB to about 0.4 MB in total, with no change in dimensions.

### Tabarak Core 1.9.1
- Aligned `readme.txt` *Stable tag* with the plugin version (was `1.9.0`).
