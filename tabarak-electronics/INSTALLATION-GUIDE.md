# Tabarak Electronics - Installation Guide

This package contains two production ZIP files:

- `tabarak-electronics.zip` - the WordPress theme
- `tabarak-core.zip` - the companion plugin (business info, brands, SEO schema, trust badges)

## Requirements
- WordPress 6.2+
- PHP 7.4+ (tested to PHP 8.3)
- WooCommerce 8+ (recommended, for the shop features)

## Step-by-step

### 1. Install the theme
1. In WordPress admin go to **Appearance > Themes > Add New > Upload Theme**.
2. Choose `tabarak-electronics.zip` and click **Install Now**.
3. Click **Activate**. The theme activates with no fatal errors even before WooCommerce is installed.

### 2. Install the companion plugin
1. Go to **Plugins > Add New > Upload Plugin**.
2. Choose `tabarak-core.zip`, click **Install Now**, then **Activate**.
3. A new **Tabarak** menu appears. Open it and confirm your business details
   (name, phone `0721606030`, hours, Luthuli St address, WhatsApp number).

### 3. Install WooCommerce (for the shop)
1. **Plugins > Add New**, search for **WooCommerce**, install and activate.
2. Run the WooCommerce setup (currency KES, Kenya, M-Pesa/cards as needed).
3. Your homepage will automatically show Featured products, Best sellers and New arrivals.

### 4. Set your logo and menu
1. **Appearance > Customize > Site Identity** - the logo ships in the theme; you can
   also upload your own here.
2. **Appearance > Menus** - create a menu and assign it to **Primary Menu**.
   Until you do, a sensible fallback menu (Home / Shop / About) is shown.

### 5. Homepage
Set a static homepage under **Settings > Reading > A static page**, or the theme's
`front-page.php` will act as the homepage automatically.

## Notes
- The theme is safe to activate on its own. All shop sections check for WooCommerce
  and degrade gracefully when it is not active.
- Product categories on the homepage link to `/product-category/<slug>/`. Create
  matching WooCommerce categories (Televisions, Refrigerators, Washing Machines,
  Cookers, Microwaves, Air Conditioners, Laptops, Mobile Phones, Audio Systems,
  Smart Home, CCTV & Security, Small Kitchen Appliances) to make them resolve.
