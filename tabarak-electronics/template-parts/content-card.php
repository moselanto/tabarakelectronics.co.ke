<?php
/**
 * Card content part used on the homepage.
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
            <?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); ?>
        </a>
    <?php endif; ?>
    <div class="tabarak-card__body">
        <?php the_title( sprintf( '<h3 class="tabarak-card__title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h3>' ); ?>
        <div class="tabarak-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></div>
    </div>
</article>
