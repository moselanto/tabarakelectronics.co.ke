<?php
/**
 * Editable rich footer with pages, contacts and social links.
 *
 * @package Tabarak_Electronics_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$has_biz = function_exists( 'tabarak_get_business' );

$socials = array(
    'facebook'  => __( 'Facebook', 'tabarak-electronics-child' ),
    'instagram' => __( 'Instagram', 'tabarak-electronics-child' ),
    'tiktok'    => __( 'TikTok', 'tabarak-electronics-child' ),
    'youtube'   => __( 'YouTube', 'tabarak-electronics-child' ),
    'twitter'   => __( 'X', 'tabarak-electronics-child' ),
);
$social_icons = array(
    'facebook'  => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5H16.7V3.6c-.3 0-1.3-.13-2.46-.13-2.43 0-4.1 1.48-4.1 4.2v2.34H7.4V13h2.74v8h3.36z"/></svg>',
    'instagram' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/><circle cx="17.2" cy="6.8" r="1.3" fill="currentColor"/></svg>',
    'tiktok'    => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.5 3c.3 2.1 1.5 3.4 3.5 3.6v2.5c-1.2.1-2.4-.2-3.5-.9v5.9c0 3.2-2.4 5.4-5.3 5.4A5.2 5.2 0 016 14.9c0-3.1 2.9-5.5 6-4.9v2.7c-.4-.1-.9-.2-1.3-.2-1.4 0-2.5 1.1-2.5 2.5s1.1 2.6 2.5 2.6c1.5 0 2.6-1.1 2.6-2.9V3h3.2z"/></svg>',
    'youtube'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12s0-3.1-.4-4.6a2.5 2.5 0 00-1.8-1.8C18.3 5.2 12 5.2 12 5.2s-6.3 0-7.8.4A2.5 2.5 0 002.4 7.4C2 8.9 2 12 2 12s0 3.1.4 4.6a2.5 2.5 0 001.8 1.8c1.5.4 7.8.4 7.8.4s6.3 0 7.8-.4a2.5 2.5 0 001.8-1.8C22 15.1 22 12 22 12zM10 15V9l5 3-5 3z"/></svg>',
    'twitter'   => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 3h3l-6.6 7.5L21.7 21h-5.4l-4.2-5.5L7.2 21H4.1l7-8L3 3h5.5l3.8 5.1L17.5 3zm-1.9 16h1.5L8.5 4.8H6.9L15.6 19z"/></svg>',
);
?>
    <footer id="colophon" class="site-footer tabarak-footer" role="contentinfo">
        <div class="tabarak-container tabarak-footer__grid">

            <div class="tabarak-footer__col tabarak-footer__about">
                <h2 class="tabarak-footer__brand"><?php echo esc_html( $has_biz ? tabarak_get_business( 'name' ) : get_bloginfo( 'name' ) ); ?></h2>
                <p><?php esc_html_e( 'Genuine, warranty-backed electronics for homes and businesses across Kenya, with delivery, installation and expert support.', 'tabarak-electronics-child' ); ?></p>
                <?php if ( $has_biz ) : ?>
                    <ul class="tabarak-social">
                        <?php foreach ( $socials as $key => $label ) : ?>
                            <?php $url = tabarak_get_business( $key ); ?>
                            <?php if ( $url ) : ?>
                                <li><a class="tabarak-social__link tabarak-social__link--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php echo esc_attr( $label ); ?>"><?php echo isset( $social_icons[ $key ] ) ? $social_icons[ $key ] : esc_html( $label ); ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="tabarak-footer__col">
                <h3 class="widget-title"><?php esc_html_e( 'Shop', 'tabarak-electronics-child' ); ?></h3>
                <ul>
                    <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'tabarak-electronics-child' ); ?></a></li>
                    <?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
                        <li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'All products', 'tabarak-electronics-child' ); ?></a></li>
                    <?php endif; ?>
                    <?php tabarak_child_footer_link( 'about-us', __( 'About Us', 'tabarak-electronics-child' ) ); ?>
                    <?php tabarak_child_footer_link( 'contact-us', __( 'Contact Us', 'tabarak-electronics-child' ) ); ?>
                    <?php tabarak_child_footer_link( 'faqs', __( 'FAQs', 'tabarak-electronics-child' ) ); ?>
                </ul>
            </div>

            <div class="tabarak-footer__col">
                <h3 class="widget-title"><?php esc_html_e( 'Customer care', 'tabarak-electronics-child' ); ?></h3>
                <ul>
                    <?php tabarak_child_footer_link( 'delivery-installation', __( 'Delivery & Installation', 'tabarak-electronics-child' ) ); ?>
                    <?php tabarak_child_footer_link( 'warranty-service', __( 'Warranty & Service', 'tabarak-electronics-child' ) ); ?>
                    <?php tabarak_child_footer_link( 'returns-refund', __( 'Returns & Refund Policy', 'tabarak-electronics-child' ) ); ?>
                    <?php tabarak_child_footer_link( 'shipping-policy', __( 'Shipping Policy', 'tabarak-electronics-child' ) ); ?>
                    <?php tabarak_child_footer_link( 'privacy-policy', __( 'Privacy Policy', 'tabarak-electronics-child' ) ); ?>
                    <?php tabarak_child_footer_link( 'terms-and-conditions', __( 'Terms & Conditions', 'tabarak-electronics-child' ) ); ?>
                </ul>
            </div>

            <div class="tabarak-footer__col tabarak-footer__contact">
                <h3 class="widget-title"><?php esc_html_e( 'Contact', 'tabarak-electronics-child' ); ?></h3>
                <?php if ( $has_biz ) : ?>
                    <p><?php echo esc_html( tabarak_get_business( 'address' ) ); ?></p>
                    <p><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', tabarak_get_business( 'phone' ) ) ); ?>"><?php echo esc_html( tabarak_get_business( 'phone' ) ); ?></a></p>
                    <p><a href="mailto:<?php echo esc_attr( tabarak_get_business( 'email' ) ); ?>"><?php echo esc_html( tabarak_get_business( 'email' ) ); ?></a></p>
                    <p><?php echo esc_html( tabarak_get_business( 'hours' ) ); ?></p>
                <?php endif; ?>
                <p class="tabarak-pay__label"><?php esc_html_e( 'We accept', 'tabarak-electronics-child' ); ?></p>
                <ul class="tabarak-pay">
                    <li class="tabarak-pay__badge tabarak-pay__badge--cash"><?php esc_html_e( 'Cash', 'tabarak-electronics-child' ); ?></li>
                    <li class="tabarak-pay__badge tabarak-pay__badge--mpesa">M-PESA</li>
                    <li class="tabarak-pay__badge tabarak-pay__badge--bank"><?php esc_html_e( 'Bank Transfer', 'tabarak-electronics-child' ); ?></li>
                </ul>
            </div>
        </div>

        <div class="tabarak-footer__bar">
            <div class="tabarak-container tabarak-footer__bar-inner">
                <p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $has_biz ? tabarak_get_business( 'name' ) : get_bloginfo( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'tabarak-electronics-child' ); ?></p>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<?php
/* WhatsApp order button: visible on every page and device, bottom-right. */
if ( $has_biz ) :
    $wa = tabarak_get_business( 'whatsapp' );
    if ( $wa ) :
        $wa_msg = rawurlencode( 'Hello Tabarak, I would like to place an order.' );
        ?>
        <a class="tabarak-fab tabarak-fab--whatsapp js-tf-order" href="https://wa.me/<?php echo esc_attr( $wa ); ?>?text=<?php echo $wa_msg; ?>" target="_blank" rel="noopener nofollow" aria-label="<?php esc_attr_e( 'Order on WhatsApp', 'tabarak-electronics-child' ); ?>">
            <span class="tabarak-fab__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 2a8 8 0 11-4.1 14.9l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 0112 4zm4.3 10.2c-.2-.1-1.3-.7-1.5-.8s-.4-.1-.5.1-.6.8-.8 1-.3.2-.5.1a6.5 6.5 0 01-1.9-1.2 7.2 7.2 0 01-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.4.2-.4v-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5a.9.9 0 00-.7.3A2.8 2.8 0 006 8.6c0 1.6 1.2 3.2 1.3 3.4s2.3 3.6 5.7 5c.8.3 1.4.5 1.9.7.8.2 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.3.2-1.5z"/></svg></span>
            <span class="tabarak-fab__label"><?php esc_html_e( 'Order on WhatsApp', 'tabarak-electronics-child' ); ?></span>
        </a>
        <?php
    endif;
endif;
?>
<button class="tabarak-back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'tabarak-electronics-child' ); ?>">&uarr;</button>

<nav class="tabarak-bottomnav" aria-label="<?php esc_attr_e( 'Quick navigation', 'tabarak-electronics-child' ); ?>">
    <a class="tabarak-bottomnav__item" href="<?php echo esc_url( home_url( '/' ) ); ?>">
        <span class="tabarak-bottomnav__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 11l8-6 8 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 10v9h12v-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 19v-5h4v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span class="tabarak-bottomnav__label"><?php esc_html_e( 'Home', 'tabarak-electronics-child' ); ?></span>
    </a>
    <a class="tabarak-bottomnav__item" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>">
        <span class="tabarak-bottomnav__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 9l1-4h14l1 4M5 9v10h14V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 19v-5h6v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span class="tabarak-bottomnav__label"><?php esc_html_e( 'Shop', 'tabarak-electronics-child' ); ?></span>
    </a>
    <button class="tabarak-bottomnav__item tabarak-bottomnav__menu" type="button" aria-controls="tabarak-drawer" aria-expanded="false">
        <span class="tabarak-bottomnav__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
        <span class="tabarak-bottomnav__label"><?php esc_html_e( 'Menu', 'tabarak-electronics-child' ); ?></span>
    </button>
    <a class="tabarak-bottomnav__item" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>">
        <span class="tabarak-bottomnav__icon"><svg viewBox="0 0 24 24" fill="none"><path d="M3 4h2l2.4 12.2a1.5 1.5 0 001.5 1.2h8.3a1.5 1.5 0 001.5-1.2L21 8H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="10" cy="20" r="1.4" fill="currentColor"/><circle cx="18" cy="20" r="1.4" fill="currentColor"/></svg></span>
        <span class="tabarak-bottomnav__label"><?php esc_html_e( 'Cart', 'tabarak-electronics-child' ); ?></span>
        <span class="tabarak-cart-count" aria-live="polite"><?php echo function_exists( 'WC' ) && WC()->cart ? esc_html( WC()->cart->get_cart_contents_count() ) : '0'; ?></span>
    </a>
</nav>

<?php wp_footer(); ?>
</body>
</html>
