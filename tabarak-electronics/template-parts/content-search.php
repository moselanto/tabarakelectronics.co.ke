<?php
/**
 * Search result content part.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'tabarak-card' ); ?>>
    <div class="tabarak-card__body">
        <?php the_title( sprintf( '<h2 class="tabarak-card__title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
        <div class="tabarak-card__excerpt"><?php the_excerpt(); ?></div>
    </div>
</article>
