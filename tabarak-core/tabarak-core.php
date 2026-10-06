<?php
/**
 * SAFETY: This code is strictly non-destructive to shop data. It NEVER creates,
 * renames, deletes, or reassigns product categories (product_cat) or products.
 * All category/product access is read-only (get_terms/get_term/get_term_by/WP_Query).
 * The only content it can create is standard WordPress PAGES, and only when you
 * explicitly run the Setup Wizard (and it skips pages that already exist).
 */
/**
 * Plugin Name:       Tabarak Core
 * Plugin URI:        https://tabarakelectronics.co.ke/
 * Description:       Companion plugin for the Tabarak Electronics theme. Adds business information, product brands, SEO structured data and trust components. Update-safe layer separate from the theme.
 * Version:           1.12.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Tabarak Electronics Kenya
 * Author URI:        https://tabarakelectronics.co.ke/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tabarak-core
 *
 * @package Tabarak_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TABARAK_CORE_VERSION', '1.12.0' );
define( 'TABARAK_CORE_FILE', __FILE__ );
define( 'TABARAK_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'TABARAK_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once TABARAK_CORE_DIR . 'includes/class-tabarak-core.php';
require_once TABARAK_CORE_DIR . 'includes/class-tabarak-setup-wizard.php';
require_once TABARAK_CORE_DIR . 'includes/class-tabarak-hardening.php';
require_once TABARAK_CORE_DIR . 'includes/class-tabarak-funnels.php';

/**
 * Boot the plugin.
 */
function tabarak_core() {
    return Tabarak_Core::instance();
}
tabarak_core();

register_activation_hook( __FILE__, array( 'Tabarak_Core', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Tabarak_Core', 'deactivate' ) );
register_activation_hook( __FILE__, function () { delete_option( 'tabarak_funnels_rw' ); } );
