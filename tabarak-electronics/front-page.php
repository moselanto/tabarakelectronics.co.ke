<?php
/**
 * The homepage template.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$tabarak_has_wc = function_exists( 'wc_get_page_permalink' );
$tabarak_shop   = $tabarak_has_wc ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

$tabarak_categories = array(
    array( 'label' => __( 'Televisions', 'tabarak-electronics' ), 'slug' => 'televisions' ),
    array( 'label' => __( 'Refrigerators', 'tabarak-electronics' ), 'slug' => 'refrigerators' ),
    array( 'label' => __( 'Washing Machines', 'tabarak-electronics' ), 'slug' => 'washing-machines' ),
    array( 'label' => __( 'Cookers', 'tabarak-electronics' ), 'slug' => 'cookers' ),
    array( 'label' => __( 'Microwaves', 'tabarak-electronics' ), 'slug' => 'microwaves' ),
    array( 'label' => __( 'Air Conditioners', 'tabarak-electronics' ), 'slug' => 'air-conditioners' ),
    array( 'label' => __( 'Laptops', 'tabarak-electronics' ), 'slug' => 'laptops' ),
    array( 'label' => __( 'Mobile Phones', 'tabarak-electronics' ), 'slug' => 'mobile-phones' ),
    array( 'label' => __( 'Audio Systems', 'tabarak-electronics' ), 'slug' => 'audio-systems' ),
    array( 'label' => __( 'Smart Home', 'tabarak-electronics' ), 'slug' => 'smart-home' ),
    array( 'label' => __( 'CCTV & Security', 'tabarak-electronics' ), 'slug' => 'cctv-security' ),
    array( 'label' => __( 'Small Appliances', 'tabarak-electronics' ), 'slug' => 'small-kitchen-appliances' ),
);
?>

<main id="primary" class="site-main tabarak-home">

    <section class="tabarak-hero">
        <div class="tabarak-container tabarak-hero__inner">
            <div class="tabarak-hero__content">
                <p class="tabarak-hero__eyebrow"><?php esc_html_e( 'Genuine electronics. Nationwide delivery.', 'tabarak-electronics' ); ?></p>
                <h1 class="tabarak-hero__title"><?php esc_html_e( 'Premium electronics for every Kenyan home & business', 'tabarak-electronics' ); ?></h1>
                <p class="tabarak-hero__text"><?php esc_html_e( 'TVs, fridges, washing machines, laptops and more from the brands you trust, with warranty, installation and financing.', 'tabarak-electronics' ); ?></p>
                <div class="tabarak-hero__actions">
                    <a class="tabarak-btn tabarak-btn--primary" href="<?php echo esc_url( $tabarak_shop ); ?>"><?php esc_html_e( 'Shop now', 'tabarak-electronics' ); ?></a>
                    <a class="tabarak-btn tabarak-btn--ghost" href="https://wa.me/<?php echo esc_attr( tabarak_get_business( 'whatsapp' ) ); ?>" rel="noopener nofollow"><?php esc_html_e( 'Talk to us', 'tabarak-electronics' ); ?></a>
                </div>
            </div>
        </div>
    </section>

    <section class="tabarak-services" aria-label="<?php esc_attr_e( 'Our promise', 'tabarak-electronics' ); ?>">
        <div class="tabarak-container tabarak-services__grid">
            <div class="tabarak-service"><h3><?php esc_html_e( 'Free Nairobi Delivery', 'tabarak-electronics' ); ?></h3><p><?php esc_html_e( 'On qualifying orders', 'tabarak-electronics' ); ?></p></div>
            <div class="tabarak-service"><h3><?php esc_html_e( 'Genuine Warranty', 'tabarak-electronics' ); ?></h3><p><?php esc_html_e( 'Authorised products only', 'tabarak-electronics' ); ?></p></div>
            <div class="tabarak-service"><h3><?php esc_html_e( 'Secure Payments', 'tabarak-electronics' ); ?></h3><p><?php esc_html_e( 'M-Pesa & cards', 'tabarak-electronics' ); ?></p></div>
            <div class="tabarak-service"><h3><?php esc_html_e( 'Expert Support', 'tabarak-electronics' ); ?></h3><p><?php echo esc_html( tabarak_get_business( 'hours' ) ); ?></p></div>
        </div>
    </section>

    <section class="tabarak-section tabarak-categories">
        <div class="tabarak-container">
            <header class="tabarak-section__head">
                <h2 class="tabarak-section__title"><?php esc_html_e( 'Shop by category', 'tabarak-electronics' ); ?></h2>
                <a class="tabarak-section__link" href="<?php echo esc_url( $tabarak_shop ); ?>"><?php esc_html_e( 'View all', 'tabarak-electronics' ); ?></a>
            </header>
            <div class="tabarak-category-grid">
                <?php foreach ( $tabarak_categories as $cat ) : ?>
                    <?php
                    $cat_url = $tabarak_has_wc ? home_url( '/product-category/' . $cat['slug'] . '/' ) : $tabarak_shop;
                    ?>
                    <a class="tabarak-category-card" href="<?php echo esc_url( $cat_url ); ?>">
                        <span class="tabarak-category-card__label"><?php echo esc_html( $cat['label'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ( $tabarak_has_wc ) : ?>
        <section class="tabarak-section tabarak-featured">
            <div class="tabarak-container">
                <header class="tabarak-section__head">
                    <h2 class="tabarak-section__title"><?php esc_html_e( 'Featured products', 'tabarak-electronics' ); ?></h2>
                </header>
                <?php echo tabarak_render_products( array( 'visibility' => 'featured', 'limit' => '8', 'columns' => '4' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </section>

        <section class="tabarak-section tabarak-bestsellers">
            <div class="tabarak-container">
                <header class="tabarak-section__head">
                    <h2 class="tabarak-section__title"><?php esc_html_e( 'Best sellers', 'tabarak-electronics' ); ?></h2>
                </header>
                <?php echo tabarak_render_products( array( 'orderby' => 'popularity', 'limit' => '8', 'columns' => '4', 'visibility' => 'visible' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </section>

        <section class="tabarak-section tabarak-new">
            <div class="tabarak-container">
                <header class="tabarak-section__head">
                    <h2 class="tabarak-section__title"><?php esc_html_e( 'New arrivals', 'tabarak-electronics' ); ?></h2>
                </header>
                <?php echo tabarak_render_products( array( 'orderby' => 'date', 'order' => 'DESC', 'limit' => '8', 'columns' => '4', 'visibility' => 'visible' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </section>
    <?php else : ?>
        <section class="tabarak-section">
            <div class="tabarak-container tabarak-notice">
                <p><?php esc_html_e( 'Activate WooCommerce and the Tabarak Core plugin to display featured products, best sellers and new arrivals here automatically.', 'tabarak-electronics' ); ?></p>
            </div>
        </section>
    <?php endif; ?>

    <section class="tabarak-section tabarak-cta-band">
        <div class="tabarak-container tabarak-cta-band__inner">
            <div>
                <h2><?php esc_html_e( 'Need help choosing? Talk to a specialist.', 'tabarak-electronics' ); ?></h2>
                <p><?php echo esc_html( tabarak_get_business( 'address' ) ); ?></p>
            </div>
            <div class="tabarak-cta-band__actions">
                <a class="tabarak-btn tabarak-btn--primary" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', tabarak_get_business( 'phone' ) ) ); ?>"><?php echo esc_html( sprintf( __( 'Call %s', 'tabarak-electronics' ), tabarak_get_business( 'phone' ) ) ); ?></a>
                <a class="tabarak-btn tabarak-btn--ghost" href="https://wa.me/<?php echo esc_attr( tabarak_get_business( 'whatsapp' ) ); ?>" rel="noopener nofollow"><?php esc_html_e( 'WhatsApp us', 'tabarak-electronics' ); ?></a>
            </div>
        </div>
    </section>

    <?php
    $home_query = new WP_Query(
        array(
            'post_type'           => 'post',
            'posts_per_page'      => 3,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        )
    );
    if ( $home_query->have_posts() ) :
        ?>
        <section class="tabarak-section tabarak-articles">
            <div class="tabarak-container">
                <header class="tabarak-section__head">
                    <h2 class="tabarak-section__title"><?php esc_html_e( 'Latest articles & buying guides', 'tabarak-electronics' ); ?></h2>
                </header>
                <div class="tabarak-post-list tabarak-post-list--home">
                    <?php
                    while ( $home_query->have_posts() ) :
                        $home_query->the_post();
                        get_template_part( 'template-parts/content', 'card' );
                    endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

</main>
<?php
get_footer();
