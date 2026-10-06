<?php
/**
 * The header.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'tabarak-electronics' ); ?></a>

<div id="page" class="site">

    <div class="tabarak-topbar">
        <div class="tabarak-container tabarak-topbar__inner">
            <p class="tabarak-topbar__msg"><?php echo esc_html( get_theme_mod( 'tabarak_topbar_text', __( 'Free delivery within Nairobi on orders over KSh 20,000', 'tabarak-electronics' ) ) ); ?></p>
            <p class="tabarak-topbar__contact">
                <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', tabarak_get_business( 'phone' ) ) ); ?>"><?php echo esc_html( tabarak_get_business( 'phone' ) ); ?></a>
                <span class="tabarak-topbar__hours"><?php echo esc_html( tabarak_get_business( 'hours' ) ); ?></span>
            </p>
        </div>
    </div>

    <header id="masthead" class="site-header" role="banner">
        <div class="tabarak-container site-header__inner">
            <div class="site-branding">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
                <?php endif; ?>
            </div>

            <div class="site-search">
                <?php get_search_form(); ?>
            </div>

            <div class="site-header__actions">
                <a class="header-action header-action--account" href="<?php echo esc_url( tabarak_account_url() ); ?>">
                    <span class="header-action__label"><?php esc_html_e( 'Account', 'tabarak-electronics' ); ?></span>
                </a>
                <?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
                    <a class="header-action header-action--cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
                        <span class="header-action__label"><?php esc_html_e( 'Cart', 'tabarak-electronics' ); ?></span>
                        <span class="tabarak-cart-count" aria-live="polite"><?php echo function_exists( 'WC' ) && WC()->cart ? esc_html( WC()->cart->get_cart_contents_count() ) : '0'; ?></span>
                    </a>
                <?php endif; ?>
                <button class="tabarak-menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                    <span class="screen-reader-text"><?php esc_html_e( 'Menu', 'tabarak-electronics' ); ?></span>
                    <span class="tabarak-menu-toggle__bar"></span>
                </button>
            </div>
        </div>

        <nav id="site-navigation" class="main-navigation" role="navigation" aria-label="<?php esc_attr_e( 'Primary', 'tabarak-electronics' ); ?>">
            <div class="tabarak-container">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'menu_id'        => 'primary-menu',
                        'container'      => false,
                        'fallback_cb'    => 'tabarak_primary_menu_fallback',
                    )
                );
                ?>
            </div>
        </nav>
    </header>
