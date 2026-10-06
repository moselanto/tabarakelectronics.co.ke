<?php
/**
 * Single post content part.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'tabarak-single-post' ); ?>>
    <header class="entry-header">
        <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
        <div class="entry-meta"><?php tabarak_posted_on(); ?></div>
    </header>
    <?php if ( has_post_thumbnail() ) : ?>
        <div class="entry-media"><?php the_post_thumbnail( 'large' ); ?></div>
    <?php endif; ?>
    <div class="entry-content">
        <?php
        the_content();
        wp_link_pages(
            array(
                'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'tabarak-electronics' ),
                'after'  => '</div>',
            )
        );
        ?>
    </div>
    <footer class="entry-footer">
        <?php the_tags( '<span class="tags-links">', ', ', '</span>' ); ?>
    </footer>
</article>
