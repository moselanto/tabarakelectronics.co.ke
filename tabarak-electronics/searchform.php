<?php
/**
 * Custom search form.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$tabarak_action = home_url( '/' );
if ( function_exists( 'wc_get_page_permalink' ) ) {
    $tabarak_action = home_url( '/' );
}
?>
<form role="search" method="get" class="tabarak-search-form" action="<?php echo esc_url( $tabarak_action ); ?>">
    <label class="screen-reader-text" for="tabarak-search-field"><?php esc_html_e( 'Search for:', 'tabarak-electronics' ); ?></label>
    <input type="search" id="tabarak-search-field" class="tabarak-search-form__field" placeholder="<?php esc_attr_e( 'Search TVs, fridges, laptops...', 'tabarak-electronics' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
    <?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
        <input type="hidden" name="post_type" value="product" />
    <?php endif; ?>
    <button type="submit" class="tabarak-search-form__submit"><?php esc_html_e( 'Search', 'tabarak-electronics' ); ?></button>
</form>
