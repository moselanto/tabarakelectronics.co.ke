<?php
/**
 * The template for displaying 404 pages.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main">
    <div class="tabarak-container tabarak-404">
        <section class="error-404 not-found">
            <header class="page-header">
                <h1 class="page-title"><?php esc_html_e( 'This page could not be found', 'tabarak-electronics' ); ?></h1>
            </header>
            <div class="page-content">
                <p><?php esc_html_e( 'The page you are looking for may have moved. Try a search or head back to the homepage.', 'tabarak-electronics' ); ?></p>
                <?php get_search_form(); ?>
                <a class="tabarak-btn tabarak-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to homepage', 'tabarak-electronics' ); ?></a>
            </div>
        </section>
    </div>
</main>
<?php
get_footer();
