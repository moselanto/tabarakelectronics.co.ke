# Changelog

All notable changes to the Tabarak Electronics platform.

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
