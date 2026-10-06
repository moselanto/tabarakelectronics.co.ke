<?php
/**
 * Theme setup: supports, menus, image sizes.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tabarak_setup' ) ) {
	/**
	 * Register theme supports and features.
	 */
	function tabarak_setup() {
		load_theme_textdomain( 'tabarak-electronics', TABARAK_DIR . '/languages' );

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'customize-selective-refresh-widgets' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );

		add_theme_support(
			'html5',
			array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
		);

		add_theme_support(
			'custom-logo',
			array(
				'height'      => 48,
				'width'       => 200,
				'flex-width'  => true,
				'flex-height' => true,
			)
		);

		// WooCommerce support.
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );

		register_nav_menus(
			array(
				'primary'   => __( 'Primary Menu', 'tabarak-electronics' ),
				'mobile'    => __( 'Mobile Menu', 'tabarak-electronics' ),
				'footer'    => __( 'Footer Menu', 'tabarak-electronics' ),
				'top-bar'   => __( 'Top Bar Menu', 'tabarak-electronics' ),
			)
		);

		add_image_size( 'tabarak-product-card', 480, 480, true );
		add_image_size( 'tabarak-hero', 1600, 720, true );
	}
}
add_action( 'after_setup_theme', 'tabarak_setup' );

if ( ! function_exists( 'tabarak_content_width' ) ) {
	/**
	 * Set the content width in pixels.
	 */
	function tabarak_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'tabarak_content_width', 1280 );
	}
}
add_action( 'after_setup_theme', 'tabarak_content_width', 0 );

if ( ! function_exists( 'tabarak_widgets_init' ) ) {
	/**
	 * Register widget areas.
	 */
	function tabarak_widgets_init() {
		$defaults = array(
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		);

		register_sidebar( array_merge( $defaults, array(
			'name' => __( 'Shop Sidebar', 'tabarak-electronics' ),
			'id'   => 'shop-sidebar',
		) ) );

		for ( $i = 1; $i <= 4; $i++ ) {
			register_sidebar( array_merge( $defaults, array(
				/* translators: %d: footer column number. */
				'name' => sprintf( __( 'Footer Column %d', 'tabarak-electronics' ), $i ),
				'id'   => 'footer-' . $i,
			) ) );
		}
	}
}
add_action( 'widgets_init', 'tabarak_widgets_init' );
