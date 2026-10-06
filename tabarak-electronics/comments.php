<?php
/**
 * Comments template.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( post_password_required() ) {
    return;
}
?>
<div id="comments" class="comments-area">
    <?php if ( have_comments() ) : ?>
        <h2 class="comments-title">
            <?php
            $tabarak_count = get_comments_number();
            /* translators: %s: comment count. */
            printf( esc_html( _n( '%s comment', '%s comments', $tabarak_count, 'tabarak-electronics' ) ), esc_html( number_format_i18n( $tabarak_count ) ) );
            ?>
        </h2>
        <ol class="comment-list">
            <?php
            wp_list_comments(
                array(
                    'style'      => 'ol',
                    'short_ping' => true,
                    'avatar_size' => 48,
                )
            );
            ?>
        </ol>
        <?php the_comments_navigation(); ?>
    <?php endif; ?>

    <?php if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) : ?>
        <p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'tabarak-electronics' ); ?></p>
    <?php endif; ?>

    <?php comment_form(); ?>
</div>
