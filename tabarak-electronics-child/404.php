<?php
/**
 * 404 template (child): friendly message, search and popular categories.
 *
 * @package Tabarak_Electronics_Child
 */

if ( \! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main">
    <div class="tabarak-container tux-404">
        <p class="tux-404__code" aria-hidden="true">404</p>
        <h1><?php esc_html_e( 'We could not find that page', 'tabarak-electronics-child' ); ?></h1>
        <p><?php esc_html_e( 'It may have moved or the link may be old. Search for a product or pick a category below.', 'tabarak-electronics-child' ); ?></p>
        <div class="tux-404__search"><?php get_search_form(); ?></div>
        <p>
            <a class="tux-btn tux-btn--accent" href="<?php echo esc_url( function_exists( 'tabarak_ux_shop_url' ) ? tabarak_ux_shop_url() : home_url( '/' ) ); ?>"><?php esc_html_e( 'Browse the shop', 'tabarak-electronics-child' ); ?></a>
            <a class="tux-btn tux-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to homepage', 'tabarak-electronics-child' ); ?></a>
        </p>
        <?php
        if ( function_exists( 'tabarak_ux_empty_cart_cats' ) ) {
            tabarak_ux_empty_cart_cats();
        }
        ?>
    </div>
</main>
<?php
get_footer();
