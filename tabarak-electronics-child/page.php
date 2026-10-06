<?php
/**
 * Page template (child): branded hero, readable content column, help strip.
 * Cart, checkout and account pages use a wide layout without the help strip.
 *
 * @package Tabarak_Electronics_Child
 */

if ( \! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
$tabarak_is_app = function_exists( 'tabarak_ux_is_wc_page' ) && tabarak_ux_is_wc_page();
?>
<main id="primary" class="site-main">
    <?php
    while ( have_posts() ) :
        the_post();
        if ( function_exists( 'tabarak_ux_page_hero' ) ) {
            tabarak_ux_page_hero();
        }
        ?>
        <div class="tabarak-container">
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'tabarak-page tux-page' . ( $tabarak_is_app ? ' tux-page--wide' : '' ) ); ?>>
                <div class="tux-page__body">
                    <div class="entry-content">
                        <?php
                        the_content();
                        wp_link_pages(
                            array(
                                'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'tabarak-electronics-child' ),
                                'after'  => '</div>',
                            )
                        );
                        ?>
                    </div>
                </div>
                <?php
                if ( \! $tabarak_is_app && function_exists( 'tabarak_ux_help_strip' ) ) {
                    tabarak_ux_help_strip();
                }
                ?>
            </article>
        </div>
        <?php
    endwhile;
    ?>
</main>
<?php
get_footer();
