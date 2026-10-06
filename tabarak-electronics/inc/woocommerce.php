<?php
/**
 * WooCommerce integration. Safe to load even when WooCommerce is inactive.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tabarak_is_woocommerce_active' ) ) {
    /**
     * Whether WooCommerce is available.
     *
     * @return bool
     */
    function tabarak_is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }
}

if ( ! function_exists( 'tabarak_wc_wrapper_start' ) ) {
    /**
     * Open the shop content wrapper.
     */
    function tabarak_wc_wrapper_start() {
        echo '<main id="primary" class="site-main tabarak-shop-main"><div class="tabarak-container">';
    }
}

if ( ! function_exists( 'tabarak_wc_wrapper_end' ) ) {
    /**
     * Close the shop content wrapper.
     */
    function tabarak_wc_wrapper_end() {
        echo '</div></main>';
    }
}

if ( ! function_exists( 'tabarak_wc_setup' ) ) {
    /**
     * Configure WooCommerce hooks. Runs on init so WooCommerce is fully loaded.
     */
    function tabarak_wc_setup() {
        if ( ! tabarak_is_woocommerce_active() ) {
            return;
        }

        remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
        remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
        add_action( 'woocommerce_before_main_content', 'tabarak_wc_wrapper_start', 10 );
        add_action( 'woocommerce_after_main_content', 'tabarak_wc_wrapper_end', 10 );

        // Products per row and per page.
        add_filter( 'loop_shop_columns', 'tabarak_wc_loop_columns' );
        add_filter( 'loop_shop_per_page', 'tabarak_wc_products_per_page' );
    }
}
add_action( 'init', 'tabarak_wc_setup' );

if ( ! function_exists( 'tabarak_wc_loop_columns' ) ) {
    /**
     * Number of product columns.
     *
     * @return int
     */
    function tabarak_wc_loop_columns() {
        return 4;
    }
}

if ( ! function_exists( 'tabarak_wc_products_per_page' ) ) {
    /**
     * Number of products per page.
     *
     * @return int
     */
    function tabarak_wc_products_per_page() {
        return 12;
    }
}

if ( ! function_exists( 'tabarak_wc_cart_count_fragment' ) ) {
    /**
     * Keep the header cart count in sync via AJAX fragments.
     *
     * @param array $fragments Cart fragments.
     * @return array
     */
    function tabarak_wc_cart_count_fragment( $fragments ) {
        if ( ! tabarak_is_woocommerce_active() || is_null( WC()->cart ) ) {
            return $fragments;
        }
        ob_start();
        ?>
        <span class="tabarak-cart-count" aria-live="polite"><?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?></span>
        <?php
        $fragments['span.tabarak-cart-count'] = ob_get_clean();
        return $fragments;
    }
}
add_filter( 'woocommerce_add_to_cart_fragments', 'tabarak_wc_cart_count_fragment' );
