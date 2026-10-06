# Tabarak Electronics Child Theme

The update-safe layer. Activate THIS theme (not the parent) once both are installed,
so future parent-theme updates never overwrite your changes.

## How to use
- Add CSS to `style.css` (loads after the parent automatically).
- Add PHP hooks/filters to `functions.php`.
- To override a parent template, copy the file from the parent theme into this folder
  keeping the same path (e.g. copy `template-parts/content.php` here and edit the copy).

## Install order
1. Install & keep the parent theme `tabarak-electronics` (do not delete it).
2. Install this child ZIP: Appearance > Themes > Add New > Upload.
3. Activate **Tabarak Electronics Child**.
