<?php
/**
 * Default content part.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'tabarak-card' ); ?>>
    <?php if ( has_post_thumbnail() ) : ?>
        <a class="tabarak-card__media" href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => the_title_attribute( array( 'echo' => false ) ) ) ); ?>
        </a>
    <?php endif; ?>
    <div class="tabarak-card__body">
        <?php the_title( sprintf( '<h2 class="tabarak-card__title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
        <div class="tabarak-card__meta"><?php tabarak_posted_on(); ?></div>
        <div class="tabarak-card__excerpt"><?php the_excerpt(); ?></div>
    </div>
</article>
