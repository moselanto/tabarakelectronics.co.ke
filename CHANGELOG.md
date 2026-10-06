# Changelog

All notable changes to the Tabarak Electronics platform.

## Tabarak Electronics Child 1.13.1 - Product card buttons fix (2026-10-06)

- **"Add to cart" button on product cards:** stays on one line instead of wrapping to "Add to / cart".
- **WhatsApp button on product cards:** now a matching round green button at the same 40px height.
- **"View cart" link:** WooCommerce adds it after a product goes into the cart. On cards it is now hidden, so the row does not break; the cart pop-up already shows it.
- **Very small phones (under 380px):** the cart icon is dropped so the text fits.

## Tabarak Core 1.13.1 + Child 1.13.0 - WhatsApp ordering on cards and cart (2026-10-06)

- **Product cards:** every product card on the homepage, shop, category and search pages now has a green WhatsApp button. It opens the same order form for that product, with its photo, price and quantity.
- **Cart page:** a new "Order this cart on WhatsApp" button under Proceed to checkout. The form lists every item with its quantity and subtotal, plus the cart total. On WhatsApp the order arrives as a neat list with a TBK reference, and it is saved under Funnel Orders with all the items.
- **Fallbacks:** if JavaScript is off, every button still opens WhatsApp directly with the product or cart written out.

## Tabarak Core 1.13.0 + Child 1.12.1 - WhatsApp order form on every page (2026-10-06)

- **Every "Order on WhatsApp" button now opens a pop-up order form first.** This includes the button next to Add to cart on product pages and the floating green button on every page. It works the same way as the funnel pages. On phones the form opens as a bottom sheet.
- **The form asks for:**
  - Product (with photo, price and quantity), or "what would you like to order?" from the floating button.
  - Name, plus a +254 phone number, which is validated.
  - Delivery area and estate, or pickup at the shop.
  - Payment method and whether installation is needed.
  - An optional note.
  - The customer's details are remembered for their next order.
- **On "Send order on WhatsApp":** an order reference (TBK-YYMMDD-XXXX) is created, the order is saved under **Tabarak > Funnel Orders** with an email alert, and WhatsApp opens with the order written out neatly.
- **Fallbacks:** if JavaScript is off, the buttons still open WhatsApp directly. Cart, checkout and My Account pages are unchanged.
- **Speed:** the form's stylesheet loads without blocking the page, and its script is deferred.

## Tabarak Electronics Child 1.12.0 - Speed (2026-10-06)

Measured on the live site before the change: server response time about 0.45-0.57 s (LiteSpeed cache working), and a homepage with 186 images, 159 srcsets, 20 scripts and 389 KB of HTML (35 KB compressed).

- **Hero slider:** WebP images (68 KB instead of 119 KB on desktop, and a 25 KB 800px version for phones). The slides are now real `<img>` elements with `srcset`: the first loads at high priority with a responsive preload, and the other three are lazy.
- **Product cards:** a `sizes` hint, so phones download the small thumbnail instead of the 300px one. Images use async decoding and a fixed square ratio, so there is no layout jump.
- **Off-screen homepage rows, brands, call-to-action and footer:** the browser skips rendering them until you scroll near them (`content-visibility`), which makes the first paint much faster on phones.
- **Variation-swatches plugin:** its CSS and JS, plus the WordPress API scripts it pulls in, no longer load on pages without variation swatches.
- **Theme scripts:** load with `defer`.
- **Product page:** the main product photo loads first at high priority.
- **New arrivals:** reduced from 18 to 12 products.

## Security and anti-spam pass (2026-10-06) - Tabarak Core 1.12.1, Child 1.11.3

Found during a live check of the site and a code review, and fixed:

- **Username leak:** `/?author=1` redirected to `/author/moses/`, and the oEmbed endpoint returned the author name. This gave attackers the admin login name. Author archives and `?author=` now redirect to the homepage for visitors, author links are hidden, and the oEmbed author fields are removed. The author scan block now runs before WordPress's canonical redirect.
- **Spoofable IP:** the login lockout trusted `X-Forwarded-For` and `CF-Connecting-IP`, which anyone can fake to dodge the 10-attempt lockout. A new `Tabarak_Hardening::visitor_ip()` only trusts `CF-Connecting-IP` when the request truly comes from a Cloudflare IP range, and otherwise uses the server's `REMOTE_ADDR`. The funnel lead limiter uses it too.
- **Login form spam:** wp-login.php and the WooCommerce login form had no honeypot. Both now do.
- **Fake orders and card testing:** order attempts are limited to 8 per IP per 10 minutes, on both the block checkout (Store API `/wc/store/v1/checkout`) and the classic checkout. Shop managers are exempt.
- **PHP version leak:** the `X-Powered-By: PHP/8.4.23` header is now removed by the plugin, and a server rule is added to the `.htaccess` snippet.
- **Cached pages broke search and lead saving:** live search and funnel lead saving required a nonce. LiteSpeed serves cached pages with nonces that expire after about 24 hours, so search returned `-1` and leads failed to save. Live search is read-only and public, so it now works without a nonce, with the search term capped at 60 characters and results cached for 10 minutes. Funnel leads use a honeypot, a fill-time trap (forms submitted in under 2.5 seconds are ignored), link-spam filtering, field length caps and the per-IP limit.
- **Server snippet** (`tabarak-electronics-child/security/root-htaccess-block.txt`): adds rules that block `wp-admin/install.php`, `readme.html`, `license.txt`, `error_log` and `debug.log`, block `?author=N` scans before WordPress loads, and unset `X-Powered-By`.

Already good on the live site: HTTPS redirect, HSTS, nosniff, X-Frame-Options, Referrer-Policy and Permissions-Policy headers, xmlrpc.php blocked (403), `/wp-json/wp/v2/users` hidden, `.env`, `.git`, readme and license blocked, uploads and wp-includes directory listing blocked, direct PHP file access returns blank, and the server firewall blocks `<script>` in search.

## Tabarak Core 1.12.0 - Funnel refinements (2026-10-06)

- **Accessories kept out of auto-picked deals.** Each funnel has a price floor and a list of skip words. Televisions skip products under KSh 8,000 and names containing remote, mount, bracket, cable or stand, and the other funnels have their own. This fixes the TV funnel showing a remote control as "Top pick" and "From KSh 1,800". Both settings can be edited under Tabarak > Sales Funnels, and products you pick yourself are always shown.
- **Hero spotlight.** The right side of the hero now shows the Top pick product (big photo, brand, price, savings and an *Order now* button that opens the order form) instead of a 3-photo collage. A custom hero image still overrides it.
- **Budget cards filter the deals in place.** Each card shows how many of today's deals fit. Tapping one filters the grid without leaving the page, with a *Show all* button and a *More in this budget* link. Budgets with no deals on the page still open the filtered category.
- **Desktop floating "Ask on WhatsApp" button** opens the order form. The theme's floating button is hidden on funnels, so nothing overlaps the reCAPTCHA badge.
- Cleaner line breaks in headings and titles.

## Tabarak Core 1.11.0 - WhatsApp order form for funnels (2026-10-06)

- Every *Order on WhatsApp* button on the funnels now opens a polished order form (a centred modal on desktop, a bottom sheet on mobile). It shows the product photo, brand, price and a quantity stepper. The customer enters their name, Kenyan phone number (validated, e.g. 0712 345 678), delivery area (19 Nairobi areas and towns), estate or street, delivery or shop pickup, payment method (M-Pesa on delivery, M-Pesa now, cash, bank) and installation, plus an optional note.
- On submit, an order reference (e.g. `TBK-261006-7KQ2`) is created, the order is saved in wp-admin, and WhatsApp opens with a neatly formatted order (bold labels, total for more than one item, product link) ready to send.
- A success screen shows the reference, with a "WhatsApp did not open? Tap here" fallback.
- New **Get my best price** section near the end of each funnel: a contact form with budget and "what do you need", sent the same way.
- New **Tabarak > Funnel Orders** list: every submission with reference, name, phone (call and WhatsApp links), location, product and status (New, Contacted, Sold, Not sold). An email alert goes to the business email for each new order.
- Spam protection with a nonce, a honeypot and a limit of 15 submissions per hour per IP. Name, phone and location are remembered on the device for repeat orders.
- Cards show an "In stock" marker. Fires a `generate_lead` / `Lead` event when Google Analytics or the Meta Pixel is installed.

## Tabarak Electronics Child 1.11.2 - Menu hover fix (2026-10-06)

- Fixed links on the dark menu bar (Call to order, Hot Deals, quick links) turning white on hover. The parent theme's light hover background was overriding the dark bar.
- Dropdown and panel links keep a soft orange hover.

## Tabarak Electronics Child 1.11.1 - Menu fixes (2026-10-06)

- Fixed menu items overlapping near *Audio* (quick links ran under the Delivery and Warranty links). The left part of the menu now shrinks cleanly and hides quick links on narrower screens instead of overlapping.
- Delivery, Warranty, Returns, FAQs and Contact moved into one **Help** dropdown, which frees space in the bar.
- **Hot Deals** is now a panel listing the category sales funnels (TV deals, Fridge deals and the rest) with category images, plus a *View all deals* button.
- Quick links trimmed to TVs, Fridges, Cookers, Washing and Audio.
- The menu cache clears when funnel settings are saved.

## Tabarak Core 1.10.0 - Category sales funnels (2026-10-06)

- 13 sales funnel pages for the biggest categories: `/lp-televisions/`, `/lp-refrigerators/`, `/lp-cookers/`, `/lp-washing/`, `/lp-cookware/`, `/lp-blenders/`, `/lp-microwaves/`, `/lp-irons/`, `/lp-kettles/`, `/lp-dispensers/`, `/lp-heaters/`, `/lp-hoods/` and `/lp-audio/`.
- Each page is short and direct: a hero with a "From KSh" price, WhatsApp and call buttons, a trust bar, budget buttons, a *Top deals* grid with an *Order on WhatsApp* button on every product (the message is pre-filled with the product name, price and link), brand chips, 3 order steps, FAQs, a final call to action and a sticky Call / WhatsApp bar on mobile.
- New admin screen **Tabarak > Sales Funnels**. For each funnel you can pick products with a WooCommerce product search (in your own order), switch auto-fill of empty slots on or off, set the number of products, headline, sub-headline, promo bar and hero image, budget buttons and extra FAQs, and turn the page on or off.
- Adds ItemList and FAQPage structured data, meta description and canonical (skipped when Yoast or Rank Math is active).
- No pages or products are created. Settings live in one option, and product picks are cached for 15 minutes and cleared on save or product update.

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
