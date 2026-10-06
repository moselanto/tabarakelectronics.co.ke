<?php
/**
 * Search form.
 *
 * Routes the header search box to FiboSearch (Ajax Search for WooCommerce) when
 * that plugin is active, so shoppers get instant AJAX autocomplete results.
 * Falls back to the standard WooCommerce product search form if FiboSearch is
 * unavailable.
 *
 * @package Tabarak_Electronics_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="tabarak-search-wrap">
<?php if ( shortcode_exists( 'fibosearch' ) ) : ?>
    <div class="tabarak-search-fibo"><?php echo do_shortcode( '[fibosearch]' ); ?></div>
<?php else : ?>
    <form role="search" method="get" class="tabarak-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <span class="tabarak-search-form__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3.2-3.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </span>
        <label class="screen-reader-text" for="tabarak-search-field"><?php esc_html_e( 'Search for products', 'tabarak-electronics-child' ); ?></label>
        <input type="search" id="tabarak-search-field" class="tabarak-search-form__field" data-tabarak-search role="combobox" aria-autocomplete="list" aria-label="Search products, categories and brands" placeholder="<?php esc_attr_e( 'Search products, categories, brands...', 'tabarak-electronics-child' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" autocomplete="off" aria-expanded="false" aria-controls="tabarak-search-results" />
        <?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
            <input type="hidden" name="post_type" value="product" />
        <?php endif; ?>
        <button type="submit" class="tabarak-search-form__submit"><?php esc_html_e( 'Search', 'tabarak-electronics-child' ); ?></button>
    </form>
    <div class="tabarak-search-results" id="tabarak-search-results" role="listbox" hidden></div>
<?php endif; ?>
</div>
