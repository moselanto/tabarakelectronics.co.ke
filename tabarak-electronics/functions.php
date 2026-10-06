<?php
/**
 * Tabarak Electronics theme bootstrap.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TABARAK_VERSION', '1.2.1' );
define( 'TABARAK_DIR', get_template_directory() );
define( 'TABARAK_URI', get_template_directory_uri() );

require_once TABARAK_DIR . '/inc/setup.php';
require_once TABARAK_DIR . '/inc/enqueue.php';
require_once TABARAK_DIR . '/inc/template-functions.php';
require_once TABARAK_DIR . '/inc/customizer.php';
require_once TABARAK_DIR . '/inc/woocommerce.php';
