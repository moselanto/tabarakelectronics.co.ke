<?php
/**
 * Enqueue styles and scripts.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tabarak_enqueue_assets' ) ) {
    /**
     * Front-end assets.
     */
    function tabarak_enqueue_assets() {
        // Main stylesheet (theme header lives in style.css but presentation is in main.css).
        wp_enqueue_style(
            'tabarak-main',
            TABARAK_URI . '/assets/css/main.css',
            array(),
            TABARAK_VERSION
        );


        wp_enqueue_script(
            'tabarak-main',
            TABARAK_URI . '/assets/js/main.js',
            array(),
            TABARAK_VERSION,
            true
        );

        wp_localize_script(
            'tabarak-main',
            'tabarakData',
            array(
                'ajaxUrl' => esc_url( admin_url( 'admin-ajax.php' ) ),
                'nonce'   => wp_create_nonce( 'tabarak_nonce' ),
            )
        );

        if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
            wp_enqueue_script( 'comment-reply' );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'tabarak_enqueue_assets' );

// No web fonts are loaded (system font stack), so no font preconnect is needed.
