<?php
/**
 * "No results" content part.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<section class="no-results not-found tabarak-notice">
    <header class="page-header">
        <h1 class="page-title"><?php esc_html_e( 'Nothing found', 'tabarak-electronics' ); ?></h1>
    </header>
    <div class="page-content">
        <p><?php esc_html_e( 'We could not find what you were looking for. Try a different search.', 'tabarak-electronics' ); ?></p>
        <?php get_search_form(); ?>
    </div>
</section>
