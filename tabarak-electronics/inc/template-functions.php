<?php
/**
 * Template helper functions.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tabarak_body_classes' ) ) {
    /**
     * Add helpful body classes.
     *
     * @param array $classes Existing classes.
     * @return array
     */
    function tabarak_body_classes( $classes ) {
        if ( ! is_singular() ) {
            $classes[] = 'hfeed';
        }
        if ( function_exists( 'is_woocommerce' ) && ( is_shop() || is_product_category() || is_product_taxonomy() ) ) {
            $classes[] = 'tabarak-shop';
        }
        return $classes;
    }
}
add_filter( 'body_class', 'tabarak_body_classes' );

if ( ! function_exists( 'tabarak_pingback_header' ) ) {
    /**
     * Add a pingback header on singular pages.
     */
    function tabarak_pingback_header() {
        if ( is_singular() && pings_open() ) {
            printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
        }
    }
}
add_action( 'wp_head', 'tabarak_pingback_header' );

if ( ! function_exists( 'tabarak_get_business' ) ) {
    /**
     * Return business info, allowing the companion plugin to override via filter.
     *
     * @param string $key Info key.
     * @return string
     */
    function tabarak_get_business( $key ) {
        $defaults = array(
            'name'    => 'Tabarak Electronics Kenya',
            'phone'   => '0721606030',
            'hours'   => 'Mon - Fri / 9:00 AM - 6:00 PM',
            'address' => 'Nairobi Central, Luthuli St, Nairobi Sky Mall Bldg, Nairobi, Kenya',
            'whatsapp' => '254721606030',
        );
        $data = apply_filters( 'tabarak_business_info', $defaults );
        return isset( $data[ $key ] ) ? $data[ $key ] : '';
    }
}

if ( ! function_exists( 'tabarak_posted_on' ) ) {
    /**
     * Print human-readable post date.
     */
    function tabarak_posted_on() {
        printf(
            '<span class="posted-on">%s</span>',
            esc_html( get_the_date() )
        );
    }
}

if ( ! function_exists( 'tabarak_account_url' ) ) {
    /**
     * Return the account URL (My Account when WooCommerce is active).
     *
     * @return string
     */
    function tabarak_account_url() {
        if ( function_exists( 'wc_get_page_permalink' ) ) {
            $url = wc_get_page_permalink( 'myaccount' );
            if ( $url ) {
                return $url;
            }
        }
        return wp_registration_url();
    }
}

if ( ! function_exists( 'tabarak_primary_menu_fallback' ) ) {
    /**
     * Fallback primary menu shown before the user assigns a menu.
     */
    function tabarak_primary_menu_fallback() {
        echo '<ul id="primary-menu" class="menu">';
        echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'tabarak-electronics' ) . '</a></li>';
        if ( function_exists( 'wc_get_page_permalink' ) ) {
            echo '<li><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Shop', 'tabarak-electronics' ) . '</a></li>';
        }
        echo '<li><a href="' . esc_url( home_url( '/?page_id=2' ) ) . '">' . esc_html__( 'About', 'tabarak-electronics' ) . '</a></li>';
        echo '</ul>';
    }
}

if ( ! function_exists( 'tabarak_render_products' ) ) {
    /**
     * Render a WooCommerce product shortcode grid, guarded for non-WooCommerce sites.
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    function tabarak_render_products( $atts ) {
        if ( ! function_exists( 'shortcode_exists' ) || ! shortcode_exists( 'products' ) ) {
            return '';
        }
        $defaults = array(
            'limit'      => '8',
            'columns'    => '4',
            'visibility' => 'featured',
        );
        $atts  = wp_parse_args( $atts, $defaults );
        $parts = array();
        foreach ( $atts as $key => $value ) {
            $parts[] = $key . '="' . esc_attr( $value ) . '"';
        }
        return do_shortcode( '[products ' . implode( ' ', $parts ) . ']' );
    }
}
