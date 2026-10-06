<?php
/**
 * Main plugin class.
 *
 * @package Tabarak_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Tabarak_Core singleton.
 */
final class Tabarak_Core {

    /**
     * Single instance.
     *
     * @var Tabarak_Core|null
     */
    private static $instance = null;

    /**
     * Get the instance.
     *
     * @return Tabarak_Core
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * Hook everything.
     */
    private function init() {
        add_filter( 'tabarak_business_info', array( $this, 'business_info' ) );
        add_action( 'init', array( $this, 'register_brand_taxonomy' ) );
        add_action( 'wp_head', array( $this, 'output_schema' ), 5 );
        add_shortcode( 'tabarak_trust_badges', array( $this, 'trust_badges_shortcode' ) );
        add_shortcode( 'tabarak_return_policy', array( $this, 'return_policy_shortcode' ) );
        add_shortcode( 'tabarak_delivery', array( $this, 'delivery_shortcode' ) );
        add_shortcode( 'tabarak_about', array( $this, 'about_shortcode' ) );
        add_shortcode( 'tabarak_contact', array( $this, 'contact_shortcode' ) );
        add_shortcode( 'tabarak_shipping', array( $this, 'shipping_shortcode' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields' ) );
        add_filter( 'woocommerce_default_address_fields', array( $this, 'default_address_fields' ) );
        add_action( 'woocommerce_before_checkout_form', array( $this, 'checkout_payment_note' ), 5 );
        add_action( 'tabarak_brand_add_form_fields', array( $this, 'brand_add_image_field' ) );
        add_action( 'tabarak_brand_edit_form_fields', array( $this, 'brand_edit_image_field' ) );
        add_action( 'created_tabarak_brand', array( $this, 'brand_save_image' ) );
        add_action( 'edited_tabarak_brand', array( $this, 'brand_save_image' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'brand_admin_assets' ) );
        add_action( 'admin_init', array( $this, 'ensure_home_page' ) );
        if ( is_admin() && class_exists( 'Tabarak_Setup_Wizard' ) ) {
            Tabarak_Setup_Wizard::init();
        }
    }

    /**
     * Business details consumed by the theme.
     *
     * @param array $info Existing info.
     * @return array
     */
    public function business_info( $info ) {
        $stored = get_option( 'tabarak_business', array() );
        $defaults = array(
            'name'     => 'Tabarak Electronics Kenya',
            'phone'    => '0721606030',
            'hours'    => 'Mon - Fri / 9:00 AM - 6:00 PM',
            'address'  => 'Nairobi Central, Luthuli St, Nairobi Sky Mall Bldg, Nairobi, Kenya',
            'whatsapp' => '254721606030',
            'email'    => 'info@tabarakelectronics.co.ke',
            'url'       => 'https://tabarakelectronics.co.ke/',
            'facebook'  => '',
            'instagram' => '',
            'tiktok'    => '',
            'youtube'   => '',
            'twitter'   => '',
            'checkout_note' => 'Pay by M-Pesa, cash on delivery, or bank transfer. Place your order and our team will call or WhatsApp you to confirm payment and delivery before dispatch.',
        );
        return wp_parse_args( is_array( $stored ) ? $stored : array(), wp_parse_args( $info, $defaults ) );
    }

    /**
     * Register a Brand taxonomy attached to products (and posts as fallback).
     */
    public function register_brand_taxonomy() {
        // Avoid duplicating WooCommerce's native Brands taxonomy.
        if ( taxonomy_exists( 'product_brand' ) ) {
            return;
        }
        $object_types = array( 'post' );
        if ( post_type_exists( 'product' ) ) {
            $object_types[] = 'product';
        }

        register_taxonomy(
            'tabarak_brand',
            $object_types,
            array(
                'labels'            => array(
                    'name'          => __( 'Brands', 'tabarak-core' ),
                    'singular_name' => __( 'Brand', 'tabarak-core' ),
                    'menu_name'     => __( 'Brands', 'tabarak-core' ),
                ),
                'hierarchical'      => true,
                'public'            => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => array( 'slug' => 'brand' ),
            )
        );
    }

    /**
     * Output Organization + LocalBusiness JSON-LD.
     */
    public function output_schema() {
        // RankMath (or Yoast) owns schema when active - never duplicate it.
        if ( defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) ) {
            return;
        }
        if ( ! is_front_page() && ! is_home() ) {
            return;
        }
        $info    = $this->business_info( array() );
        $home    = trailingslashit( $info['url'] );
        $same_as = array();
        foreach ( array( 'facebook', 'instagram', 'tiktok', 'youtube', 'twitter' ) as $net ) {
            if ( ! empty( $info[ $net ] ) ) {
                $same_as[] = $info[ $net ];
            }
        }
        $store = array(
            '@context'           => 'https://schema.org',
            '@type'              => 'ElectronicsStore',
            '@id'                => $home . '#store',
            'name'               => $info['name'],
            'url'                => $info['url'],
            'telephone'          => $info['phone'],
            'email'              => $info['email'],
            'priceRange'         => 'KSh',
            'currenciesAccepted' => 'KES',
            'paymentAccepted'    => 'M-Pesa, Cash, Bank Transfer',
            'areaServed'         => array( '@type' => 'Country', 'name' => 'Kenya' ),
            'address'            => array(
                '@type'           => 'PostalAddress',
                'streetAddress'   => 'Luthuli St, Nairobi Sky Mall Bldg',
                'addressLocality' => 'Nairobi',
                'addressRegion'   => 'Nairobi',
                'addressCountry'  => 'KE',
            ),
            'geo'                => array(
                '@type'     => 'GeoCoordinates',
                'latitude'  => '-1.2833',
                'longitude' => '36.8256',
            ),
            'openingHoursSpecification' => array(
                array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
                    'opens'     => '09:00',
                    'closes'    => '18:00',
                ),
            ),
        );
        if ( ! empty( $same_as ) ) {
            $store['sameAs'] = $same_as;
        }
        $website = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => $home . '#website',
            'url'             => $info['url'],
            'name'            => $info['name'],
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => array(
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $home . '?s={search_term_string}&post_type=product',
                ),
                'query-input' => 'required name=search_term_string',
            ),
        );
        echo '<script type="application/ld+json">' . wp_json_encode( array( $store, $website ) ) . '</script>';
    }

    /**
     * Trust badges shortcode.
     *
     * @param array $atts Attributes.
     * @return string
     */
    public function trust_badges_shortcode( $atts ) {
        $badges = array(
            __( 'Genuine Products', 'tabarak-core' ),
            __( 'Warranty Backed', 'tabarak-core' ),
            __( 'M-Pesa & Cards', 'tabarak-core' ),
            __( 'Nationwide Delivery', 'tabarak-core' ),
        );
        $out = '<ul class="tabarak-trust-badges">';
        foreach ( $badges as $badge ) {
            $out .= '<li>' . esc_html( $badge ) . '</li>';
        }
        $out .= '</ul>';
        return $out;
    }

    /**
     * Checkout fields: name, town/city and phone are required; street is
     * optional; postcode / ZIP and state are removed completely.
     *
     * @param array $fields WooCommerce checkout fields.
     * @return array
     */
    public function checkout_fields( $fields ) {
        if ( ! isset( $fields['billing'] ) || ! is_array( $fields['billing'] ) ) {
            return $fields;
        }
        foreach ( array( 'billing', 'shipping' ) as $group ) {
            if ( isset( $fields[ $group ] ) && is_array( $fields[ $group ] ) ) {
                unset( $fields[ $group ][ $group . '_postcode' ] );
                unset( $fields[ $group ][ $group . '_state' ] );
            }
        }
        $required = array( 'billing_first_name', 'billing_last_name', 'billing_city', 'billing_phone' );
        foreach ( $required as $key ) {
            if ( isset( $fields['billing'][ $key ] ) ) {
                $fields['billing'][ $key ]['required'] = true;
            }
        }
        $optional = array( 'billing_address_1', 'billing_address_2', 'billing_company' );
        foreach ( $optional as $key ) {
            if ( isset( $fields['billing'][ $key ] ) ) {
                $fields['billing'][ $key ]['required'] = false;
            }
        }
        if ( isset( $fields['billing']['billing_address_1'] ) ) {
            $fields['billing']['billing_address_1']['label']       = __( 'Street address (optional)', 'tabarak-core' );
            $fields['billing']['billing_address_1']['placeholder'] = __( 'Estate, street, house / apartment', 'tabarak-core' );
        }
        if ( isset( $fields['billing']['billing_city'] ) ) {
            $fields['billing']['billing_city']['label'] = __( 'Town / City', 'tabarak-core' );
        }
        return $fields;
    }

    /**
     * Drop postcode / state from My Account address forms too; keep street
     * optional and town/city required there.
     *
     * @param array $fields Default address fields.
     * @return array
     */
    public function default_address_fields( $fields ) {
        unset( $fields['postcode'] );
        unset( $fields['state'] );
        if ( isset( $fields['address_1'] ) ) {
            $fields['address_1']['required'] = false;
        }
        if ( isset( $fields['city'] ) ) {
            $fields['city']['required'] = true;
        }
        return $fields;
    }

    /**
     * Editable payment note shown at the top of checkout (COD / M-Pesa / bank).
     */
    public function checkout_payment_note() {
        $info  = $this->business_info( array() );
        $note  = isset( $info['checkout_note'] ) ? $info['checkout_note'] : '';
        $phone = isset( $info['phone'] ) ? $info['phone'] : '';
        if ( ! $note ) {
            return;
        }
        echo '<div class="tabarak-checkout-note woocommerce-info">';
        echo '<strong>' . esc_html__( 'How payment works', 'tabarak-core' ) . '</strong> ';
        echo esc_html( $note );
        if ( $phone ) {
            $tel = preg_replace( '/[^0-9+]/', '', $phone );
            echo ' <span class="tabarak-checkout-note__phone">' . esc_html__( 'Questions? Call or WhatsApp', 'tabarak-core' ) . ' <a href="tel:' . esc_attr( $tel ) . '">' . esc_html( $phone ) . '</a></span>';
        }
        echo '</div>';
    }

    /**
     * Brand image field (add-term screen).
     */
    public function brand_add_image_field() {
        ?>
        <div class="form-field term-tabarak-brand-image">
            <label><?php esc_html_e( 'Brand image / logo', 'tabarak-core' ); ?></label>
            <input type="hidden" name="tabarak_brand_image_id" id="tabarak_brand_image_id" value="" />
            <div id="tabarak_brand_image_preview" style="margin:8px 0;"></div>
            <button type="button" class="button tabarak-brand-upload"><?php esc_html_e( 'Upload / select image', 'tabarak-core' ); ?></button>
            <button type="button" class="button tabarak-brand-remove"><?php esc_html_e( 'Remove', 'tabarak-core' ); ?></button>
            <p class="description"><?php esc_html_e( 'Shown on brand pages / storefront. Recommended 600x600px.', 'tabarak-core' ); ?></p>
        </div>
        <?php
    }

    /**
     * Brand image field (edit-term screen).
     *
     * @param WP_Term $term Term being edited.
     */
    public function brand_edit_image_field( $term ) {
        $id  = (int) get_term_meta( $term->term_id, 'tabarak_brand_image_id', true );
        $src = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';
        ?>
        <tr class="form-field term-tabarak-brand-image">
            <th scope="row"><label><?php esc_html_e( 'Brand image / logo', 'tabarak-core' ); ?></label></th>
            <td>
                <input type="hidden" name="tabarak_brand_image_id" id="tabarak_brand_image_id" value="<?php echo esc_attr( $id ); ?>" />
                <div id="tabarak_brand_image_preview" style="margin:8px 0;">
                    <?php if ( $src ) : ?><img src="<?php echo esc_url( $src ); ?>" style="max-width:100px;height:auto;" /><?php endif; ?>
                </div>
                <button type="button" class="button tabarak-brand-upload"><?php esc_html_e( 'Upload / select image', 'tabarak-core' ); ?></button>
                <button type="button" class="button tabarak-brand-remove"><?php esc_html_e( 'Remove', 'tabarak-core' ); ?></button>
                <p class="description"><?php esc_html_e( 'Recommended 600x600px.', 'tabarak-core' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Persist the chosen brand image id.
     *
     * @param int $term_id Term id.
     */
    public function brand_save_image( $term_id ) {
        if ( isset( $_POST['tabarak_brand_image_id'] ) ) {
            $id = absint( wp_unslash( $_POST['tabarak_brand_image_id'] ) );
            if ( $id ) {
                update_term_meta( $term_id, 'tabarak_brand_image_id', $id );
            } else {
                delete_term_meta( $term_id, 'tabarak_brand_image_id' );
            }
        }
    }

    /**
     * Load the media uploader only on the Brands taxonomy screens.
     *
     * @param string $hook Current admin page hook.
     */
    public function brand_admin_assets( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || 'tabarak_brand' !== $screen->taxonomy ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script( 'tabarak-brand-admin', plugins_url( 'assets/js/brand-admin.js', dirname( __FILE__, 2 ) . '/tabarak-core.php' ), array( 'jquery' ), '1.7.0', true );
    }

    /**
     * Admin settings page.
     */
    public function admin_menu() {
        add_menu_page(
            __( 'Tabarak', 'tabarak-core' ),
            __( 'Tabarak', 'tabarak-core' ),
            'manage_options',
            'tabarak-core',
            array( $this, 'render_admin' ),
            'dashicons-store',
            58
        );
    }

    /**
     * Render the admin settings screen and save on POST.
     */
    public function render_admin() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_POST['tabarak_core_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tabarak_core_nonce'] ) ), 'tabarak_core_save' ) ) {
            $fields = array( 'name', 'phone', 'hours', 'address', 'whatsapp', 'email', 'url', 'facebook', 'instagram', 'tiktok', 'youtube', 'twitter', 'checkout_note' );
            $save   = array();
            foreach ( $fields as $field ) {
                if ( ! isset( $_POST[ $field ] ) ) {
                    continue;
                }
                if ( 'checkout_note' === $field ) {
                    $save[ $field ] = sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) );
                    continue;
                }
                $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
                $save[ $field ] = in_array( $field, array( 'url', 'facebook', 'instagram', 'tiktok', 'youtube' ), true ) ? esc_url_raw( $value ) : $value;
            }
            update_option( 'tabarak_business', $save );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Saved.', 'tabarak-core' ) . '</p></div>';
        }

        $info = $this->business_info( array() );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Tabarak Business Information', 'tabarak-core' ); ?></h1>
            <form method="post">
                <?php wp_nonce_field( 'tabarak_core_save', 'tabarak_core_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <?php
                    $labels = array(
                        'name'     => __( 'Business name', 'tabarak-core' ),
                        'phone'    => __( 'Phone', 'tabarak-core' ),
                        'hours'    => __( 'Opening hours', 'tabarak-core' ),
                        'address'  => __( 'Address', 'tabarak-core' ),
                        'whatsapp' => __( 'WhatsApp (digits only)', 'tabarak-core' ),
                        'email'    => __( 'Email', 'tabarak-core' ),
                        'url'       => __( 'Website URL', 'tabarak-core' ),
                        'facebook'  => __( 'Facebook URL', 'tabarak-core' ),
                        'instagram' => __( 'Instagram URL', 'tabarak-core' ),
                        'tiktok'    => __( 'TikTok URL', 'tabarak-core' ),
                        'youtube'   => __( 'YouTube URL', 'tabarak-core' ),
                        'twitter'   => __( 'X / Twitter URL', 'tabarak-core' ),
                        'checkout_note' => __( 'Checkout payment note', 'tabarak-core' ),
                    );
                    foreach ( $labels as $key => $label ) :
                        ?>
                        <tr>
                            <th scope="row"><label for="tabarak-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                            <td>
                                <?php if ( 'checkout_note' === $key ) : ?>
                                    <textarea name="<?php echo esc_attr( $key ); ?>" id="tabarak-<?php echo esc_attr( $key ); ?>" rows="3" class="large-text"><?php echo esc_textarea( isset( $info[ $key ] ) ? $info[ $key ] : '' ); ?></textarea>
                                    <p class="description"><?php esc_html_e( 'Shown at the top of checkout. Explain M-Pesa / cash / bank transfer and that you confirm each order by call or WhatsApp.', 'tabarak-core' ); ?></p>
                                <?php else : ?>
                                    <input name="<?php echo esc_attr( $key ); ?>" id="tabarak-<?php echo esc_attr( $key ); ?>" type="text" class="regular-text" value="<?php echo esc_attr( isset( $info[ $key ] ) ? $info[ $key ] : '' ); ?>" />
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Small helper: business phone / whatsapp / email for policy pages.
     */
    private function biz( $key ) {
        $info = apply_filters( 'tabarak_business_info', array() );
        return isset( $info[ $key ] ) ? $info[ $key ] : '';
    }

    /**
     * [tabarak_return_policy] — styled, comprehensive returns & refunds page.
     *
     * @return string
     */
    public function return_policy_shortcode() {
        $phone = $this->biz( 'phone' );
        $email = $this->biz( 'email' );
        $wa    = $this->biz( 'whatsapp' );
        ob_start();
        ?>
        <div class="tabarak-policy tabarak-policy--returns">
            <div class="tabarak-policy__intro">
                <p><?php esc_html_e( 'We want you to shop with total confidence. Every product is genuine and warranty-backed, and if something is not right we will make it right. Please read the simple steps below.', 'tabarak-core' ); ?></p>
            </div>
            <ul class="tabarak-policy__cards">
                <li class="tabarak-policy__card"><span class="tabarak-policy__num">7</span><h3><?php esc_html_e( '7-Day Returns', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'Return most items within 7 days of delivery for a refund, exchange or store credit.', 'tabarak-core' ); ?></p></li>
                <li class="tabarak-policy__card"><span class="tabarak-policy__num">12</span><h3><?php esc_html_e( 'Warranty Cover', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'Manufacturer warranty on applicable products, handled through the brand service centre.', 'tabarak-core' ); ?></p></li>
                <li class="tabarak-policy__card"><span class="tabarak-policy__num">0</span><h3><?php esc_html_e( 'Free Fault Pickup', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'If an item arrives damaged or faulty, we arrange collection at no cost to you.', 'tabarak-core' ); ?></p></li>
            </ul>
            <h3><?php esc_html_e( 'What can be returned', 'tabarak-core' ); ?></h3>
            <ul class="tabarak-policy__list tabarak-policy__list--yes">
                <li><?php esc_html_e( 'Items that are unused, in original packaging with all accessories and the receipt.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Items delivered damaged, defective, or different from what you ordered.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Products that develop a fault covered by the manufacturer warranty.', 'tabarak-core' ); ?></li>
            </ul>
            <h3><?php esc_html_e( 'What cannot be returned', 'tabarak-core' ); ?></h3>
            <ul class="tabarak-policy__list tabarak-policy__list--no">
                <li><?php esc_html_e( 'Items damaged through misuse, accident, or unauthorised repair.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Products returned after 7 days without a warranty claim, or without proof of purchase.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Consumables, installed items, and items with broken security seals (for hygiene/safety).', 'tabarak-core' ); ?></li>
            </ul>
            <h3><?php esc_html_e( 'How to start a return', 'tabarak-core' ); ?></h3>
            <ol class="tabarak-policy__steps">
                <li><?php esc_html_e( 'Contact us within 7 days with your order number and a short description (a photo helps for damage).', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'We confirm the return and, for faulty items, arrange free pickup or a service-centre booking.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Once checked, we process your refund, exchange or store credit within 3-5 business days.', 'tabarak-core' ); ?></li>
            </ol>
            <div class="tabarak-policy__cta">
                <p><strong><?php esc_html_e( 'Need to start a return or ask a question?', 'tabarak-core' ); ?></strong></p>
                <p class="tabarak-policy__contacts">
                    <?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
                    <?php if ( $wa ) : ?><a href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'WhatsApp us', 'tabarak-core' ); ?></a><?php endif; ?>
                    <?php if ( $email ) : ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><?php endif; ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [tabarak_delivery] — styled, comprehensive delivery & installation page.
     *
     * @return string
     */
    public function delivery_shortcode() {
        $phone = $this->biz( 'phone' );
        $wa    = $this->biz( 'whatsapp' );
        ob_start();
        ?>
        <div class="tabarak-policy tabarak-policy--delivery">
            <div class="tabarak-policy__intro">
                <p><?php esc_html_e( 'Fast, careful, countrywide delivery. We deliver across Kenya with same or next-day dispatch in Nairobi and reliable courier partners for every other county.', 'tabarak-core' ); ?></p>
            </div>
            <ul class="tabarak-policy__cards">
                <li class="tabarak-policy__card"><h3><?php esc_html_e( 'Nairobi & Metro', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'Same-day or next-day delivery on orders placed before 2:00 PM.', 'tabarak-core' ); ?></p></li>
                <li class="tabarak-policy__card"><h3><?php esc_html_e( 'Major Towns', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'Mombasa, Kisumu, Nakuru, Eldoret and more within 1-3 business days.', 'tabarak-core' ); ?></p></li>
                <li class="tabarak-policy__card"><h3><?php esc_html_e( 'Countrywide', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'Delivery to any town via trusted couriers, typically 2-5 business days.', 'tabarak-core' ); ?></p></li>
            </ul>
            <h3><?php esc_html_e( 'Delivery fees', 'tabarak-core' ); ?></h3>
            <ul class="tabarak-policy__list">
                <li><?php esc_html_e( 'Nairobi CBD & nearby estates: low flat rate, shown at checkout.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Upcountry & courier deliveries: calculated by destination and item size at checkout.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Large appliances (fridges, cookers, washing machines) may attract a handling fee for safe transport.', 'tabarak-core' ); ?></li>
            </ul>
            <h3><?php esc_html_e( 'Installation & setup', 'tabarak-core' ); ?></h3>
            <ul class="tabarak-policy__list tabarak-policy__list--yes">
                <li><?php esc_html_e( 'Optional installation for TVs, cookers, hoods and washing machines in Nairobi (book in advance).', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Our team explains basic setup and warranty registration on delivery of major appliances.', 'tabarak-core' ); ?></li>
            </ul>
            <h3><?php esc_html_e( 'How it works', 'tabarak-core' ); ?></h3>
            <ol class="tabarak-policy__steps">
                <li><?php esc_html_e( 'Place your order and choose delivery at checkout.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'We confirm your order by call or WhatsApp and schedule dispatch.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Track with our team until your item arrives safely at your door.', 'tabarak-core' ); ?></li>
            </ol>
            <div class="tabarak-policy__cta">
                <p><strong><?php esc_html_e( 'Questions about delivery to your area?', 'tabarak-core' ); ?></strong></p>
                <p class="tabarak-policy__contacts">
                    <?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
                    <?php if ( $wa ) : ?><a href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'WhatsApp us', 'tabarak-core' ); ?></a><?php endif; ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [tabarak_about] - comprehensive, styled About Us page.
     *
     * @return string
     */
    public function about_shortcode() {
        $phone = $this->biz( 'phone' );
        $wa    = $this->biz( 'whatsapp' );
        $addr  = $this->biz( 'address' );
        $hours = $this->biz( 'hours' );
        $shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
        ob_start();
        ?>
        <div class="tabarak-about">
            <section class="tabarak-about__hero">
                <div class="tabarak-about__hero-inner">
                    <span class="tabarak-about__eyebrow"><?php esc_html_e( 'About Tabarak Electronics', 'tabarak-core' ); ?></span>
                    <h2><?php esc_html_e( 'Kenya\'s home for genuine electronics & appliances', 'tabarak-core' ); ?></h2>
                    <p><?php esc_html_e( 'We help homes and businesses across Kenya buy the right electronics with confidence - genuine, warranty-backed products at honest prices, delivered and set up by people who actually care.', 'tabarak-core' ); ?></p>
                    <div class="tabarak-about__hero-cta">
                        <a class="tabarak-btn tabarak-btn--primary" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Shop now', 'tabarak-core' ); ?></a>
                        <?php if ( $wa ) : ?><a class="tabarak-btn tabarak-btn--ghost" href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Chat on WhatsApp', 'tabarak-core' ); ?></a><?php endif; ?>
                    </div>
                </div>
            </section>

            <ul class="tabarak-about__stats">
                <li><strong><?php esc_html_e( '100%', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'Genuine & warranty-backed', 'tabarak-core' ); ?></span></li>
                <li><strong><?php esc_html_e( '47', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'Counties we deliver to', 'tabarak-core' ); ?></span></li>
                <li><strong><?php esc_html_e( '7-Day', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'Easy returns', 'tabarak-core' ); ?></span></li>
                <li><strong><?php esc_html_e( 'M-PESA', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'Cash & bank transfer', 'tabarak-core' ); ?></span></li>
            </ul>

            <section class="tabarak-about__story">
                <h3><?php esc_html_e( 'Who we are', 'tabarak-core' ); ?></h3>
                <p><?php esc_html_e( 'Tabarak Electronics Kenya is an online and in-store retailer of electronics and home appliances based in Nairobi. From televisions, fridges and cookers to washing machines, microwaves, water dispensers and small kitchen appliances, we stock the brands Kenyan families and businesses trust - and we stand behind every one of them.', 'tabarak-core' ); ?></p>
                <p><?php esc_html_e( 'We started with a simple idea: buying appliances online in Kenya should feel safe, clear and fair. No fake products, no hidden costs, no disappearing after the sale. Just genuine goods, real prices, and a team you can call.', 'tabarak-core' ); ?></p>
            </section>

            <section class="tabarak-about__values">
                <h3><?php esc_html_e( 'Why shop with us', 'tabarak-core' ); ?></h3>
                <div class="tabarak-about__grid">
                    <div class="tabarak-about__value"><h4><?php esc_html_e( 'Genuine products', 'tabarak-core' ); ?></h4><p><?php esc_html_e( 'Every item is authentic and warranty-backed, sourced through trusted channels.', 'tabarak-core' ); ?></p></div>
                    <div class="tabarak-about__value"><h4><?php esc_html_e( 'Honest prices', 'tabarak-core' ); ?></h4><p><?php esc_html_e( 'Fair, competitive pricing with regular offers - what you see is what you pay.', 'tabarak-core' ); ?></p></div>
                    <div class="tabarak-about__value"><h4><?php esc_html_e( 'Fast delivery & setup', 'tabarak-core' ); ?></h4><p><?php esc_html_e( 'Countrywide delivery with optional installation for major appliances.', 'tabarak-core' ); ?></p></div>
                    <div class="tabarak-about__value"><h4><?php esc_html_e( 'Real after-sales', 'tabarak-core' ); ?></h4><p><?php esc_html_e( 'Warranty help and support long after your order arrives - just call or WhatsApp.', 'tabarak-core' ); ?></p></div>
                </div>
            </section>

            <section class="tabarak-about__cta">
                <div>
                    <h3><?php esc_html_e( 'Visit us or order from home', 'tabarak-core' ); ?></h3>
                    <?php if ( $addr ) : ?><p class="tabarak-about__addr"><?php echo esc_html( $addr ); ?></p><?php endif; ?>
                    <?php if ( $hours ) : ?><p class="tabarak-about__hours"><?php echo esc_html( $hours ); ?></p><?php endif; ?>
                </div>
                <div class="tabarak-about__cta-actions">
                    <?php if ( $phone ) : ?><a class="tabarak-btn tabarak-btn--primary" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( sprintf( __( 'Call %s', 'tabarak-core' ), $phone ) ); ?></a><?php endif; ?>
                    <?php if ( $wa ) : ?><a class="tabarak-btn tabarak-btn--ghost" href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'WhatsApp us', 'tabarak-core' ); ?></a><?php endif; ?>
                </div>
            </section>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [tabarak_contact] - styled Contact Us page with channels, hours & map.
     *
     * @return string
     */
    public function contact_shortcode() {
        $phone = $this->biz( 'phone' );
        $wa    = $this->biz( 'whatsapp' );
        $email = $this->biz( 'email' );
        $addr  = $this->biz( 'address' );
        $hours = $this->biz( 'hours' );
        $map   = $addr ? 'https://www.google.com/maps?q=' . rawurlencode( $addr ) . '&output=embed' : '';
        ob_start();
        ?>
        <div class="tabarak-contact">
            <div class="tabarak-contact__intro">
                <h2><?php esc_html_e( 'We\'d love to help', 'tabarak-core' ); ?></h2>
                <p><?php esc_html_e( 'Questions about a product, an order, delivery or warranty? Reach us any way you like - the fastest reply is on WhatsApp or a phone call during working hours.', 'tabarak-core' ); ?></p>
            </div>

            <div class="tabarak-contact__grid">
                <?php if ( $phone ) : ?>
                <a class="tabarak-contact__card" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
                    <span class="tabarak-contact__ico" aria-hidden="true">&#9742;</span>
                    <span class="tabarak-contact__k"><?php esc_html_e( 'Call us', 'tabarak-core' ); ?></span>
                    <span class="tabarak-contact__v"><?php echo esc_html( $phone ); ?></span>
                </a>
                <?php endif; ?>
                <?php if ( $wa ) : ?>
                <a class="tabarak-contact__card tabarak-contact__card--wa" href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener nofollow">
                    <span class="tabarak-contact__ico" aria-hidden="true">&#128172;</span>
                    <span class="tabarak-contact__k"><?php esc_html_e( 'WhatsApp', 'tabarak-core' ); ?></span>
                    <span class="tabarak-contact__v"><?php esc_html_e( 'Chat & order instantly', 'tabarak-core' ); ?></span>
                </a>
                <?php endif; ?>
                <?php if ( $email ) : ?>
                <a class="tabarak-contact__card" href="mailto:<?php echo esc_attr( $email ); ?>">
                    <span class="tabarak-contact__ico" aria-hidden="true">&#9993;</span>
                    <span class="tabarak-contact__k"><?php esc_html_e( 'Email', 'tabarak-core' ); ?></span>
                    <span class="tabarak-contact__v"><?php echo esc_html( $email ); ?></span>
                </a>
                <?php endif; ?>
                <?php if ( $addr ) : ?>
                <div class="tabarak-contact__card tabarak-contact__card--static">
                    <span class="tabarak-contact__ico" aria-hidden="true">&#128205;</span>
                    <span class="tabarak-contact__k"><?php esc_html_e( 'Visit our shop', 'tabarak-core' ); ?></span>
                    <span class="tabarak-contact__v"><?php echo esc_html( $addr ); ?></span>
                </div>
                <?php endif; ?>
                <?php if ( $hours ) : ?>
                <div class="tabarak-contact__card tabarak-contact__card--static">
                    <span class="tabarak-contact__ico" aria-hidden="true">&#128336;</span>
                    <span class="tabarak-contact__k"><?php esc_html_e( 'Working hours', 'tabarak-core' ); ?></span>
                    <span class="tabarak-contact__v"><?php echo esc_html( $hours ); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ( $map ) : ?>
            <div class="tabarak-contact__mapwrap">
                <iframe class="tabarak-contact__map" src="<?php echo esc_url( $map ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?php esc_attr_e( 'Our location on the map', 'tabarak-core' ); ?>"></iframe>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * [tabarak_shipping] - detailed Shipping Policy page.
     *
     * @return string
     */
    public function shipping_shortcode() {
        $phone = $this->biz( 'phone' );
        $wa    = $this->biz( 'whatsapp' );
        ob_start();
        ?>
        <div class="tabarak-policy tabarak-policy--shipping">
            <div class="tabarak-policy__intro">
                <p><?php esc_html_e( 'Here is exactly how your order gets to you - processing times, coverage, costs and what to expect. For delivery to your specific area, just ask us on WhatsApp.', 'tabarak-core' ); ?></p>
            </div>
            <ul class="tabarak-policy__cards">
                <li class="tabarak-policy__card"><h3><?php esc_html_e( 'Order processing', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'Orders are confirmed and prepared within a few hours on business days after payment or confirmation.', 'tabarak-core' ); ?></p></li>
                <li class="tabarak-policy__card"><h3><?php esc_html_e( 'Dispatch', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'Same or next-day dispatch in Nairobi; upcountry orders leave within 1-2 business days.', 'tabarak-core' ); ?></p></li>
                <li class="tabarak-policy__card"><h3><?php esc_html_e( 'Tracking', 'tabarak-core' ); ?></h3><p><?php esc_html_e( 'We keep you updated by call or WhatsApp from dispatch until the item is in your hands.', 'tabarak-core' ); ?></p></li>
            </ul>
            <h3><?php esc_html_e( 'Where we deliver & how long it takes', 'tabarak-core' ); ?></h3>
            <ul class="tabarak-policy__list">
                <li><?php esc_html_e( 'Nairobi & metro: same-day or next-day on orders placed before 2:00 PM.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Major towns (Mombasa, Kisumu, Nakuru, Eldoret, Thika, etc.): 1-3 business days.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Other towns countrywide: 2-5 business days via trusted courier partners.', 'tabarak-core' ); ?></li>
            </ul>
            <h3><?php esc_html_e( 'Shipping costs', 'tabarak-core' ); ?></h3>
            <ul class="tabarak-policy__list">
                <li><?php esc_html_e( 'Shipping is calculated at checkout based on your location and the size of the items.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Nairobi CBD and nearby estates enjoy a low flat rate.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Large appliances may attract a handling fee to guarantee safe transport.', 'tabarak-core' ); ?></li>
            </ul>
            <h3><?php esc_html_e( 'What to expect', 'tabarak-core' ); ?></h3>
            <ul class="tabarak-policy__list tabarak-policy__list--yes">
                <li><?php esc_html_e( 'A confirmation call or WhatsApp before dispatch to verify your address and timing.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Careful, secure packaging - especially for screens and glass items.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Please inspect major appliances on delivery and report any transit damage immediately.', 'tabarak-core' ); ?></li>
            </ul>
            <h3><?php esc_html_e( 'Failed or delayed deliveries', 'tabarak-core' ); ?></h3>
            <ul class="tabarak-policy__list tabarak-policy__list--no">
                <li><?php esc_html_e( 'Please give an accurate address and a reachable phone number to avoid delays.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'If a delivery cannot be completed, we will reschedule; repeat failed attempts may attract a re-delivery fee.', 'tabarak-core' ); ?></li>
                <li><?php esc_html_e( 'Rare delays due to weather, courier or stock issues will always be communicated to you.', 'tabarak-core' ); ?></li>
            </ul>
            <div class="tabarak-policy__cta">
                <p><strong><?php esc_html_e( 'Want a delivery estimate for your area?', 'tabarak-core' ); ?></strong></p>
                <p class="tabarak-policy__contacts">
                    <?php if ( $phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
                    <?php if ( $wa ) : ?><a href="https://wa.me/<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'WhatsApp us', 'tabarak-core' ); ?></a><?php endif; ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Ensure the site uses a dedicated static "Home" page as the front page,
     * so the homepage is a real, selectable page (not "latest posts"). The
     * theme's front-page.php still renders the storefront design for it.
     * Runs once, and never overrides a static front page you already set.
     */
    public function ensure_home_page() {
        if ( get_option( 'tabarak_home_setup_done' ) ) {
            return;
        }
        // Respect an existing static front page - do not touch it.
        if ( 'page' === get_option( 'show_on_front' ) && (int) get_option( 'page_on_front' ) > 0 ) {
            update_option( 'tabarak_home_setup_done', 1 );
            return;
        }
        $home = get_page_by_path( 'home' );
        if ( $home instanceof WP_Post ) {
            $home_id = $home->ID;
        } else {
            $home_id = wp_insert_post(
                array(
                    'post_title'   => 'Home',
                    'post_name'    => 'home',
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '',
                )
            );
        }
        if ( $home_id && ! is_wp_error( $home_id ) ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', (int) $home_id );
        }
        update_option( 'tabarak_home_setup_done', 1 );
    }

    /**
     * Activation hook. Registers taxonomy then flushes rewrite rules.
     */
    public static function activate() {
        self::instance()->register_brand_taxonomy();
        set_transient( 'tabarak_activation_redirect', 1, 60 );
        flush_rewrite_rules();
    }

    /**
     * Deactivation hook.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}