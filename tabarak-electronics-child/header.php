<?php
/**
 * Child header: topbar (phone + email), branding, live search, actions, drawer.
 *
 * @package Tabarak_Electronics_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$tabarak_phone = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'phone' ) : '';
$tabarak_email = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'email' ) : '';
$tabarak_hours = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'hours' ) : '';
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
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'tabarak-electronics-child' ); ?></a>

<div id="page" class="site">

    <div class="tabarak-topbar">
        <div class="tabarak-container tabarak-topbar__inner">
            <ul class="tabarak-topbar__contacts">
                <?php if ( $tabarak_phone ) : ?>
                    <li><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tabarak_phone ) ); ?>"><span class="tabarak-topbar__ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M6 3h3l2 5-2.5 1.5a11 11 0 005 5L16 12l5 2v3a2 2 0 01-2 2A16 16 0 013 5a2 2 0 013-2z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span><span><?php echo esc_html( $tabarak_phone ); ?></span></a></li>
                <?php endif; ?>
                <?php if ( $tabarak_email ) : ?>
                    <li><a href="mailto:<?php echo esc_attr( $tabarak_email ); ?>"><span class="tabarak-topbar__ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span><span><?php echo esc_html( $tabarak_email ); ?></span></a></li>
                <?php endif; ?>
            </ul>
            <?php if ( $tabarak_hours ) : ?>
                <p class="tabarak-topbar__hours"><span class="tabarak-topbar__ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><?php echo esc_html( $tabarak_hours ); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <header id="masthead" class="site-header" role="banner">
        <div class="tabarak-container site-header__inner">
            <div class="site-branding">
                <?php if ( has_custom_logo() ) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a class="tabarak-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                        <img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/logo.png' ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="180" height="40" />
                    </a>
                <?php endif; ?>
            </div>

            <div class="site-search">
                <?php get_search_form(); ?>
            </div>

            <div class="site-header__actions">
                <a class="header-action header-action--account" href="<?php echo esc_url( tabarak_account_url() ); ?>">
                    <span class="header-action__ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M4 20c0-4 3.6-6 8-6s8 2 8 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                    <span class="header-action__label"><?php esc_html_e( 'Account', 'tabarak-electronics-child' ); ?></span>
                </a>
                <?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
                    <a class="header-action header-action--cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
                        <span class="header-action__ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.4 12.2a1.5 1.5 0 001.5 1.2h8.3a1.5 1.5 0 001.5-1.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.5" fill="currentColor"/><circle cx="18" cy="20" r="1.5" fill="currentColor"/></svg></span>
                        <span class="header-action__label"><?php esc_html_e( 'Cart', 'tabarak-electronics-child' ); ?></span>
                        <span class="tabarak-cart-count" aria-live="polite"><?php echo function_exists( 'WC' ) && WC()->cart ? esc_html( WC()->cart->get_cart_contents_count() ) : '0'; ?></span>
                    </a>
                <?php endif; ?>
                <button class="tabarak-theme-toggle" type="button" aria-label="Toggle dark mode" aria-pressed="false" title="Toggle dark / light mode">
                    <span class="tabarak-theme-toggle__ic tabarak-theme-toggle__ic--sun" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="4.2" stroke="currentColor" stroke-width="1.9"/><path d="M12 2.2v2.6M12 19.2v2.6M2.2 12h2.6M19.2 12h2.6M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg></span>
                    <span class="tabarak-theme-toggle__ic tabarak-theme-toggle__ic--moon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M20.5 14.8A8.2 8.2 0 019.4 3.6a7.2 7.2 0 1011.1 11.2z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/></svg></span>
                </button>
                <button class="tabarak-menu-toggle" type="button" aria-controls="tabarak-drawer" aria-expanded="false">
                    <span class="screen-reader-text"><?php esc_html_e( 'Menu', 'tabarak-electronics-child' ); ?></span>
                    <span class="tabarak-burger" aria-hidden="true"><span></span><span></span><span></span></span>
                </button>
            </div>
        </div>

        <?php
        if ( function_exists( 'tabarak_child_mega_nav' ) ) {
            tabarak_child_mega_nav();
        }
        ?>
    </header>

<div class="tabarak-drawer" id="tabarak-drawer" aria-hidden="true">
    <button class="tabarak-drawer__backdrop" type="button" tabindex="-1" aria-label="<?php esc_attr_e( 'Close menu', 'tabarak-electronics-child' ); ?>"></button>
    <div class="tabarak-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'tabarak-electronics-child' ); ?>">
        <div class="tabarak-drawer__head">
            <span class="tabarak-drawer__title"><?php esc_html_e( 'Menu', 'tabarak-electronics-child' ); ?></span>
            <button class="tabarak-drawer__close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'tabarak-electronics-child' ); ?>">&times;</button>
        </div>
        <nav class="tabarak-drawer__nav" aria-label="<?php esc_attr_e( 'Mobile primary', 'tabarak-electronics-child' ); ?>">
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'tabarak-drawer__menu',
                    'fallback_cb'    => 'tabarak_primary_menu_fallback',
                    'depth'          => 2,
                )
            );
            ?>
        </nav>
        <?php
        if ( function_exists( 'tabarak_child_drawer_extras' ) ) {
            tabarak_child_drawer_extras();
        }
        if ( function_exists( 'wc_get_page_permalink' ) && taxonomy_exists( 'product_cat' ) ) :
            $tabarak_drawer_cats = get_terms(
                array(
                    'taxonomy'   => 'product_cat',
                    'hide_empty' => true,
                    'orderby'    => 'count',
                    'order'      => 'DESC',
                    'number'     => 40,
                    'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
                )
            );
            if ( ! is_wp_error( $tabarak_drawer_cats ) && ! empty( $tabarak_drawer_cats ) ) :
                ?>
                <h3 class="tabarak-drawer__subtitle"><?php esc_html_e( 'Shop by category', 'tabarak-electronics-child' ); ?></h3>
                <ul class="tabarak-drawer__catlist">
                    <?php foreach ( $tabarak_drawer_cats as $tabarak_dc ) : ?>
                        <li><a href="<?php echo esc_url( get_term_link( $tabarak_dc ) ); ?>"><span><?php echo esc_html( $tabarak_dc->name ); ?></span><span class="tabarak-drawer__count"><?php echo esc_html( $tabarak_dc->count ); ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php
            endif;
        endif;
        ?>
    </div>
</div>
