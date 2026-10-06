<?php
/**
 * The footer.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
    <footer id="colophon" class="site-footer" role="contentinfo">
        <div class="tabarak-container site-footer__widgets">
            <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
                <?php if ( is_active_sidebar( 'footer-' . $i ) ) : ?>
                    <div class="footer-column footer-column--<?php echo esc_attr( $i ); ?>">
                        <?php dynamic_sidebar( 'footer-' . $i ); ?>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <div class="site-footer__bar">
            <div class="tabarak-container site-footer__bar-inner">
                <p class="site-footer__copy">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( tabarak_get_business( 'name' ) ); ?>. <?php esc_html_e( 'All rights reserved.', 'tabarak-electronics' ); ?></p>
                <p class="site-footer__meta"><?php echo esc_html( tabarak_get_business( 'address' ) ); ?></p>
            </div>
        </div>
    </footer>
</div><!-- #page -->

<a class="tabarak-fab tabarak-fab--whatsapp" href="https://wa.me/<?php echo esc_attr( tabarak_get_business( 'whatsapp' ) ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'tabarak-electronics' ); ?>">
    <span aria-hidden="true">WhatsApp</span>
</a>
<button class="tabarak-back-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'tabarak-electronics' ); ?>">&uarr;</button>

<?php wp_footer(); ?>
</body>
</html>
