<?php
/**
 * Child theme homepage: hero (category rail + slider), service promises,
 * shop-by-category tiles, new arrivals and grouped category carousels.
 * Fully data-driven; categories sorted by product count (highest first).
 *
 * @package Tabarak_Electronics_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$tabarak_has_wc = function_exists( 'wc_get_page_permalink' );
$tabarak_shop   = $tabarak_has_wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

$tabarak_cats = array();
if ( $tabarak_has_wc && taxonomy_exists( 'product_cat' ) && function_exists( 'tabarak_child_rail_terms' ) ) {
    $tabarak_cats = tabarak_child_rail_terms();
}
$tabarak_tiles = function_exists( 'tabarak_child_tile_terms' ) ? tabarak_child_tile_terms( $tabarak_cats ) : array_slice( $tabarak_cats, 0, 12 );
$tabarak_slides = function_exists( 'tabarak_child_hero_slides' ) ? tabarak_child_hero_slides() : array();
?>

<main id="primary" class="site-main tabarak-home">

    <h1 class="screen-reader-text"><?php esc_html_e( 'Tabarak Electronics Kenya - TVs, Fridges, Cookers, Washing Machines & Home Appliances', 'tabarak-electronics-child' ); ?></h1>

    <section class="tabarak-hero2">
        <div class="tabarak-container tabarak-hero2__grid">
            <nav class="tabarak-catrail" aria-label="<?php esc_attr_e( 'Product categories', 'tabarak-electronics-child' ); ?>">
                <button type="button" class="tabarak-catrail__toggle" aria-expanded="false" aria-controls="tabarak-catrail-list">
                    <span class="tabarak-catrail__icon" aria-hidden="true"></span>
                    <span><?php esc_html_e( 'All Categories', 'tabarak-electronics-child' ); ?></span>
                    <span class="tabarak-catrail__chev" aria-hidden="true">&rsaquo;</span>
                </button>
                <ul class="tabarak-catrail__list" id="tabarak-catrail-list">
                    <?php if ( ! empty( $tabarak_cats ) ) : ?>
                        <?php foreach ( $tabarak_cats as $cat ) : ?>
                            <li><a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><span><?php echo esc_html( $cat->name ); ?></span><span class="tabarak-catrail__count"><?php echo esc_html( $cat->count ); ?></span></a></li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><a href="<?php echo esc_url( $tabarak_shop ); ?>"><span><?php esc_html_e( 'Shop all products', 'tabarak-electronics-child' ); ?></span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="tabarak-slider" data-autoplay="6000">
                <div class="tabarak-slider__viewport">
                    <div class="tabarak-slider__track">
                        <?php foreach ( $tabarak_slides as $index => $slide ) : ?>
                            <div class="tabarak-slide<?php echo 0 === $index ? ' is-active' : ''; ?>">
                                <div class="tabarak-slide__bg" style="background-image:url('<?php echo esc_url( $slide['img'] ); ?>');"></div>
                                <div class="tabarak-slide__scrim"></div>
                                <div class="tabarak-slide__inner">
                                    <span class="tabarak-slide__eyebrow"><?php esc_html_e( 'Tabarak Electronics', 'tabarak-electronics-child' ); ?></span>
                                    <h2 class="tabarak-slide__title"><?php echo esc_html( $slide['title'] ); ?></h2>
                                    <p class="tabarak-slide__text"><?php echo esc_html( $slide['text'] ); ?></p>
                                    <a class="tabarak-btn tabarak-btn--primary" href="<?php echo esc_url( $slide['link'] ); ?>"><?php esc_html_e( 'Shop now', 'tabarak-electronics-child' ); ?></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="tabarak-slider__nav tabarak-slider__nav--prev" aria-label="<?php esc_attr_e( 'Previous slide', 'tabarak-electronics-child' ); ?>">&lsaquo;</button>
                <button class="tabarak-slider__nav tabarak-slider__nav--next" aria-label="<?php esc_attr_e( 'Next slide', 'tabarak-electronics-child' ); ?>">&rsaquo;</button>
                <div class="tabarak-slider__dots" role="tablist"></div>
            </div>
        </div>
    </section>

    <section class="tabarak-services" aria-label="<?php esc_attr_e( 'Our promise', 'tabarak-electronics-child' ); ?>">
        <div class="tabarak-container tabarak-services__grid">
            <div class="tabarak-service">
                <span class="tabarak-service__icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 12h22v20H4zM26 18h9l7 7v7h-16z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><circle cx="14" cy="36" r="3.5" stroke="currentColor" stroke-width="2.5"/><circle cx="34" cy="36" r="3.5" stroke="currentColor" stroke-width="2.5"/></svg></span>
                <div class="tabarak-service__txt"><h3><?php esc_html_e( 'Fast countrywide delivery', 'tabarak-electronics-child' ); ?></h3><p><?php esc_html_e( 'Fast next-day delivery in Nairobi & major towns', 'tabarak-electronics-child' ); ?></p></div>
            </div>
            <div class="tabarak-service">
                <span class="tabarak-service__icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24 5l15 6v9c0 10-6.5 17.5-15 23-8.5-5.5-15-13-15-23v-9z" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/><path d="M17 24l5 5 10-11" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <div class="tabarak-service__txt"><h3><?php esc_html_e( 'Genuine products & warranty', 'tabarak-electronics-child' ); ?></h3><p><?php esc_html_e( 'Authentic brands, backed by warranty', 'tabarak-electronics-child' ); ?></p></div>
            </div>
            <div class="tabarak-service">
                <span class="tabarak-service__icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="5" y="11" width="38" height="26" rx="3" stroke="currentColor" stroke-width="2.5"/><path d="M5 18h38" stroke="currentColor" stroke-width="2.5"/><path d="M12 29h8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg></span>
                <div class="tabarak-service__txt"><h3><?php esc_html_e( 'Secure payments', 'tabarak-electronics-child' ); ?></h3><p><?php esc_html_e( 'M-Pesa, bank transfer & cash', 'tabarak-electronics-child' ); ?></p></div>
            </div>
            <div class="tabarak-service">
                <span class="tabarak-service__icon" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 26v-3a14 14 0 0128 0v3" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/><rect x="6" y="26" width="7" height="12" rx="2.5" stroke="currentColor" stroke-width="2.5"/><rect x="35" y="26" width="7" height="12" rx="2.5" stroke="currentColor" stroke-width="2.5"/><path d="M38 38a8 8 0 01-8 6h-4" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg></span>
                <div class="tabarak-service__txt"><h3><?php esc_html_e( 'Expert support', 'tabarak-electronics-child' ); ?></h3><p><?php echo esc_html( tabarak_get_business( 'hours' ) ); ?></p></div>
            </div>
        </div>
    </section>

        <?php
    if ( $tabarak_has_wc && count( $tabarak_tiles ) > 0 ) :
    ?>
    <section class="tabarak-section tabarak-row tabarak-categories" aria-labelledby="tabarak-cats-title">
        <div class="tabarak-container">
            <header class="tabarak-section__head">
                <h2 id="tabarak-cats-title" class="tabarak-section__title"><?php esc_html_e( 'Shop by category', 'tabarak-electronics-child' ); ?></h2>
                <a class="tabarak-section__link" href="<?php echo esc_url( $tabarak_shop ); ?>"><?php esc_html_e( 'View all', 'tabarak-electronics-child' ); ?></a>
            </header>
            <div class="tabarak-category-grid">
                <?php
                foreach ( $tabarak_tiles as $tabarak_cat ) :
                    $tabarak_link = get_term_link( $tabarak_cat );
                    if ( is_wp_error( $tabarak_link ) ) {
                        continue;
                    }
                    $tabarak_thumb = get_term_meta( $tabarak_cat->term_id, 'thumbnail_id', true );
                    ?>
                    <a class="tabarak-category-card" href="<?php echo esc_url( $tabarak_link ); ?>">
                        <span class="tabarak-category-card__media">
                            <?php
                            if ( $tabarak_thumb ) {
                                echo wp_get_attachment_image( (int) $tabarak_thumb, 'medium', false, array( 'alt' => esc_attr( $tabarak_cat->name ), 'loading' => 'lazy' ) );
                            } else {
                                ?>
                                <span class="tabarak-category-card__ph" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="7" y="7" width="34" height="34" rx="6" stroke="currentColor" stroke-width="2.5"/><path d="M7 32l9-8 6 5 8-9 11 11" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="18" cy="18" r="3.5" stroke="currentColor" stroke-width="2.5"/></svg></span>
                                <?php
                            }
                            ?>
                        </span>
                        <span class="tabarak-category-card__label"><?php echo esc_html( $tabarak_cat->name ); ?></span>
                    </a>
                    <?php
                endforeach;
                ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php
    if ( $tabarak_has_wc ) {
        // New arrivals - 18 products, horizontal carousel.
        tabarak_child_carousel(
            array(
                'posts_per_page' => 18,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ),
            __( 'New arrivals', 'tabarak-electronics-child' ),
            add_query_arg( 'orderby', 'date', $tabarak_shop )
        );

        // Homepage product rows - admin-configured (Customizer) or default set.
        if ( function_exists( 'tabarak_child_home_rows' ) ) {
            tabarak_child_home_rows();
        }

        // Shop-by-brand logo strip, right after the last category row.
        if ( function_exists( 'tabarak_child_brands_strip' ) ) {
            tabarak_child_brands_strip();
        }

        // Recently viewed row (populated client-side; stays hidden if empty).
        if ( function_exists( 'tabarak_child_recent_container' ) ) {
            tabarak_child_recent_container();
        }
    }
    ?>

    <section class="tabarak-section tabarak-cta-band">
        <div class="tabarak-container tabarak-cta-band__inner">
            <div>
                <h2><?php esc_html_e( 'Need help choosing? Talk to a specialist.', 'tabarak-electronics-child' ); ?></h2>
                <p><?php echo esc_html( tabarak_get_business( 'address' ) ); ?></p>
            </div>
            <div class="tabarak-cta-band__actions">
                <a class="tabarak-btn tabarak-btn--primary" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', tabarak_get_business( 'phone' ) ) ); ?>"><?php echo esc_html( sprintf( __( 'Call %s', 'tabarak-electronics-child' ), tabarak_get_business( 'phone' ) ) ); ?></a>
            </div>
        </div>
    </section>

</main>
<?php
get_footer();
