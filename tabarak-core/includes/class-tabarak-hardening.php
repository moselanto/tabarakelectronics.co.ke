<?php
/**
 * SAFETY: Read-only to shop data. Adds security hardening, anti-spam and
 * performance trimming only. Never creates/edits/deletes products or categories.
 *
 * Tabarak Core - Hardening, anti-spam and speed module.
 *
 * @package Tabarak_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Tabarak_Hardening' ) ) :

class Tabarak_Hardening {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->hooks();
    }

    private function hooks() {
        /* ---------------- Security ---------------- */
        add_filter( 'xmlrpc_enabled', '__return_false' );
        add_filter( 'xmlrpc_methods', array( $this, 'remove_pingback_method' ) );
        add_filter( 'wp_headers', array( $this, 'remove_pingback_header' ) );
        add_filter( 'pings_open', '__return_false', 10 );
        remove_action( 'wp_head', 'rsd_link' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'wp_shortlink_wp_head' );
        add_filter( 'the_generator', '__return_empty_string' );
        add_action( 'template_redirect', array( $this, 'block_author_scan' ), 1 );
        add_filter( 'oembed_response_data', array( $this, 'strip_oembed_author' ) );
        add_filter( 'author_link', array( $this, 'hide_author_link' ) );
        add_action( 'login_form', array( $this, 'honeypot_field' ) );
        add_filter( 'authenticate', array( $this, 'check_login_honeypot' ), 25, 1 );
        add_filter( 'rest_pre_dispatch', array( $this, 'limit_store_checkout' ), 10, 3 );
        add_action( 'woocommerce_checkout_process', array( $this, 'limit_classic_checkout' ), 1 );
        add_action( 'woocommerce_login_form', array( $this, 'honeypot_field' ) );
        add_filter( 'rest_endpoints', array( $this, 'restrict_user_rest' ) );
        add_filter( 'login_errors', array( $this, 'generic_login_error' ) );
        add_action( 'send_headers', array( $this, 'security_headers' ) );
        if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
            define( 'DISALLOW_FILE_EDIT', true );
        }

        /* ---------------- Comments: product reviews only ---------------- */
        add_action( 'init', array( $this, 'lock_down_comments' ) );
        add_filter( 'comments_open', array( $this, 'comments_only_products' ), 10, 2 );

        /* ---------------- Anti-spam honeypot + time trap ---------------- */
        add_action( 'comment_form_after_fields', array( $this, 'honeypot_field' ) );
        add_action( 'comment_form_logged_in_after', array( $this, 'honeypot_field' ) );
        add_filter( 'preprocess_comment', array( $this, 'check_comment_honeypot' ) );
        add_action( 'register_form', array( $this, 'honeypot_field' ) );
        add_filter( 'registration_errors', array( $this, 'check_register_honeypot' ), 10, 3 );
        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_review_order_before_submit', array( $this, 'honeypot_field' ) );
            add_action( 'woocommerce_checkout_process', array( $this, 'check_checkout_honeypot' ) );
            add_action( 'woocommerce_register_form', array( $this, 'honeypot_field' ) );
            add_filter( 'woocommerce_registration_errors', array( $this, 'check_register_honeypot' ), 10, 3 );
        }

        /* ---------------- Speed / resource trimming ---------------- */
        add_action( 'init', array( $this, 'disable_emojis' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'trim_assets' ), 99 );
        add_filter( 'heartbeat_settings', array( $this, 'slow_heartbeat' ) );
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
        remove_action( 'wp_head', 'rest_output_link_wp_head' );
        add_filter( 'wp_resource_hints', array( $this, 'resource_hints' ), 10, 2 );

        /* ---------------- Extra anti-spam / anti-brute-force ---------------- */
        add_filter( 'preprocess_comment', array( $this, 'filter_spam_comment' ), 9 );
        add_filter( 'registration_errors', array( $this, 'block_spam_email' ), 9, 3 );
        add_filter( 'authenticate', array( $this, 'throttle_login' ), 30, 1 );
        add_action( 'wp_login_failed', array( $this, 'note_failed_login' ) );
        if ( class_exists( 'WooCommerce' ) ) {
            add_filter( 'woocommerce_registration_errors', array( $this, 'block_spam_email' ), 9, 3 );
        }
    }

    /* ---------- Security callbacks ---------- */

    public function remove_pingback_method( $methods ) {
        unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
        return $methods;
    }

    public function remove_pingback_header( $headers ) {
        if ( is_array( $headers ) && isset( $headers['X-Pingback'] ) ) {
            unset( $headers['X-Pingback'] );
        }
        return $headers;
    }

    public function block_author_scan() {
        if ( is_admin() ) {
            return;
        }
        if ( is_user_logged_in() ) {
            return;
        }
        // Stop username discovery through ?author=N and /author/name/ archives.
        if ( isset( $_GET['author'] ) || is_author() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            wp_safe_redirect( home_url( '/' ), 301 );
            exit;
        }
    }

    public function restrict_user_rest( $endpoints ) {
        if ( is_user_logged_in() ) {
            return $endpoints;
        }
        foreach ( array_keys( $endpoints ) as $route ) {
            if ( is_string( $route ) && 0 === strpos( $route, '/wp/v2/users' ) ) {
                unset( $endpoints[ $route ] );
            }
        }
        return $endpoints;
    }

    public function generic_login_error() {
        return esc_html__( 'Invalid login details. Please try again.', 'tabarak-core' );
    }

    public function security_headers() {
        if ( is_admin() ) {
            return;
        }
        if ( headers_sent() ) {
            return;
        }
        header_remove( 'X-Powered-By' );
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );
        header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
    }

    /* ---------- Comments ---------- */

    public function lock_down_comments() {
        remove_post_type_support( 'post', 'comments' );
        remove_post_type_support( 'post', 'trackbacks' );
        remove_post_type_support( 'page', 'comments' );
        remove_post_type_support( 'page', 'trackbacks' );
    }

    public function comments_only_products( $open, $post_id ) {
        if ( 'product' === get_post_type( $post_id ) ) {
            return $open;
        }
        return false;
    }

    /* ---------- Honeypot ---------- */

    public function honeypot_field() {
        echo '<div class="tabarak-hp-wrap" style="position:absolute!important;left:-9999px!important;top:-9999px!important;height:0;overflow:hidden;" aria-hidden="true">';
        echo '<label>' . esc_html__( 'Leave this field empty', 'tabarak-core' ) . '<input type="text" name="tabarak_hp" tabindex="-1" autocomplete="off" value="" /></label>';
        echo '<input type="hidden" name="tabarak_ht" value="' . esc_attr( (string) time() ) . '" />';
        echo '</div>';
    }

    private function honeypot_tripped() {
        if ( ! empty( $_POST['tabarak_hp'] ) ) {
            return true;
        }
        if ( isset( $_POST['tabarak_ht'] ) ) {
            $t = (int) $_POST['tabarak_ht'];
            if ( $t > 0 && ( time() - $t ) < 3 ) {
                return true;
            }
        }
        return false;
    }

    public function check_comment_honeypot( $commentdata ) {
        if ( $this->honeypot_tripped() ) {
            wp_die( esc_html__( 'Spam detected. Your submission was blocked.', 'tabarak-core' ), esc_html__( 'Blocked', 'tabarak-core' ), array( 'response' => 403 ) );
        }
        return $commentdata;
    }

    public function check_register_honeypot( $errors, $login = '', $email = '' ) {
        if ( $this->honeypot_tripped() ) {
            if ( is_wp_error( $errors ) ) {
                $errors->add( 'tabarak_spam', esc_html__( 'Spam detected. Please try again.', 'tabarak-core' ) );
            }
        }
        return $errors;
    }

    public function check_checkout_honeypot() {
        if ( $this->honeypot_tripped() && function_exists( 'wc_add_notice' ) ) {
            wc_add_notice( esc_html__( 'Spam detected. Please refresh the page and try again.', 'tabarak-core' ), 'error' );
        }
    }

    /* ---------- Speed ---------- */

    public function disable_emojis() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
        add_filter( 'tiny_mce_plugins', array( $this, 'disable_emojis_tinymce' ) );
        add_filter( 'emoji_svg_url', '__return_false' );
    }

    public function disable_emojis_tinymce( $plugins ) {
        if ( is_array( $plugins ) ) {
            return array_diff( $plugins, array( 'wpemoji' ) );
        }
        return array();
    }

    public function trim_assets() {
        if ( is_admin() ) {
            return;
        }
        $keep = ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'is_account_page' ) && is_account_page() );
        if ( ! $keep ) {
            wp_dequeue_script( 'wc-cart-fragments' );
        }
    }

    public function slow_heartbeat( $settings ) {
        $settings['interval'] = 60;
        return $settings;
    }

    public function resource_hints( $hints, $relation ) {
        if ( 'preconnect' === $relation ) {
            $hints[] = 'https://fonts.gstatic.com';
        }
        return $hints;
    }
    /* ---------- Extra anti-spam callbacks ---------- */

    public function filter_spam_comment( $commentdata ) {
        if ( is_user_logged_in() ) {
            return $commentdata;
        }
        // Off-site (or missing) referer on a comment POST is almost always a bot.
        $ref  = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
        $host = wp_parse_url( home_url(), PHP_URL_HOST );
        if ( '' === $ref || ( $host && false === strpos( $ref, (string) $host ) ) ) {
            wp_die( esc_html__( 'Comment blocked: invalid request origin.', 'tabarak-core' ), '', array( 'response' => 403 ) );
        }
        // Product reviews never need an author URL - drop it.
        if ( ! empty( $commentdata['comment_author_url'] ) ) {
            $commentdata['comment_author_url'] = '';
        }
        // Reject bodies stuffed with links.
        $content = isset( $commentdata['comment_content'] ) ? $commentdata['comment_content'] : '';
        if ( preg_match_all( '#https?://#i', $content, $m ) && count( $m[0] ) > 1 ) {
            wp_die( esc_html__( 'Comment blocked: too many links.', 'tabarak-core' ), '', array( 'response' => 403 ) );
        }
        return $commentdata;
    }

    public function block_spam_email( $errors, $login = '', $email = '' ) {
        if ( ! is_email( $email ) || ! is_wp_error( $errors ) ) {
            return $errors;
        }
        $domain = strtolower( (string) substr( strrchr( $email, '@' ), 1 ) );
        $bad = array( 'mailinator.com', 'guerrillamail.com', '10minutemail.com', 'trashmail.com', 'tempmail.com', 'temp-mail.org', 'yopmail.com', 'sharklasers.com', 'getnada.com', 'dispostable.com', 'maildrop.cc', 'fakeinbox.com', 'throwawaymail.com', 'mailnesia.com' );
        if ( $domain && in_array( $domain, $bad, true ) ) {
            $errors->add( 'tabarak_disposable_email', __( 'Please register with a permanent email address.', 'tabarak-core' ) );
        }
        return $errors;
    }

    private function client_ip() {
        return self::visitor_ip();
    }

    /**
     * Visitor IP that cannot be spoofed with request headers.
     * CF-Connecting-IP is only trusted when the request really comes from a
     * Cloudflare edge; otherwise REMOTE_ADDR (set by the web server) is used.
     */
    public static function visitor_ip() {
        $remote = isset( $_SERVER['REMOTE_ADDR'] ) ? trim( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( ! filter_var( $remote, FILTER_VALIDATE_IP ) ) {
            return '';
        }
        if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && self::is_cloudflare( $remote ) ) {
            $cf = trim( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if ( filter_var( $cf, FILTER_VALIDATE_IP ) ) {
                return $cf;
            }
        }
        return $remote;
    }

    private static function is_cloudflare( $ip ) {
        $ranges = array(
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
            '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
        );
        foreach ( $ranges as $r ) {
            if ( self::ip_in_range( $ip, $r ) ) {
                return true;
            }
        }
        return false;
    }

    private static function ip_in_range( $ip, $cidr ) {
        list( $net, $bits ) = explode( '/', $cidr );
        $ipb  = @inet_pton( $ip ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        $netb = @inet_pton( $net ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        if ( false === $ipb || false === $netb || strlen( $ipb ) !== strlen( $netb ) ) {
            return false;
        }
        $bits  = (int) $bits;
        $bytes = intdiv( $bits, 8 );
        if ( substr( $ipb, 0, $bytes ) !== substr( $netb, 0, $bytes ) ) {
            return false;
        }
        $rem = $bits % 8;
        if ( 0 === $rem ) {
            return true;
        }
        $mask = chr( ( 0xff << ( 8 - $rem ) ) & 0xff );
        return ( $ipb[ $bytes ] & $mask ) === ( $netb[ $bytes ] & $mask );
    }

    /** Remove the author name/URL that oEmbed exposes for every page. */
    public function strip_oembed_author( $data ) {
        unset( $data['author_name'], $data['author_url'] );
        return $data;
    }

    /** Author archive links point to the homepage for visitors. */
    public function hide_author_link( $link ) {
        return is_user_logged_in() ? $link : home_url( '/' );
    }

    /** Honeypot on wp-login.php and the WooCommerce login form. */
    public function check_login_honeypot( $user ) {
        $is_post = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
        if ( $is_post && ( isset( $_POST['log'] ) || isset( $_POST['username'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ( ! empty( $_POST['tabarak_hp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
                return new WP_Error( 'tabarak_spam', __( 'Invalid login details. Please try again.', 'tabarak-core' ) );
            }
        }
        return $user;
    }

    /** Per-IP limiter shared by the checkout guards. */
    private function over_limit( $bucket, $max, $window ) {
        $ip = self::visitor_ip();
        if ( '' === $ip ) {
            return false;
        }
        $key = 'tabarak_rl_' . $bucket . '_' . md5( $ip );
        $n   = (int) get_transient( $key );
        if ( $n >= $max ) {
            return true;
        }
        set_transient( $key, $n + 1, $window );
        return false;
    }

    /** Card-testing / fake-order guard for the Store API (block checkout). */
    public function limit_store_checkout( $result, $server, $request ) {
        if ( null !== $result || ! is_object( $request ) || 'POST' !== $request->get_method() ) {
            return $result;
        }
        if ( 0 === strpos( (string) $request->get_route(), '/wc/store/v1/checkout' ) && ! current_user_can( 'manage_woocommerce' ) ) {
            if ( $this->over_limit( 'chk', 8, 10 * MINUTE_IN_SECONDS ) ) {
                return new WP_Error( 'tabarak_rate_limited', __( 'Too many order attempts. Please wait a few minutes or call us to order.', 'tabarak-core' ), array( 'status' => 429 ) );
            }
        }
        return $result;
    }

    /** Same guard for the classic checkout form. */
    public function limit_classic_checkout() {
        if ( current_user_can( 'manage_woocommerce' ) ) {
            return;
        }
        if ( $this->over_limit( 'chk', 8, 10 * MINUTE_IN_SECONDS ) && function_exists( 'wc_add_notice' ) ) {
            wc_add_notice( __( 'Too many order attempts. Please wait a few minutes or call us to order.', 'tabarak-core' ), 'error' );
        }
    }

    public function note_failed_login( $username ) {
        $ip = $this->client_ip();
        if ( ! $ip ) {
            return;
        }
        $key = 'tabarak_lf_' . md5( $ip );
        $n   = (int) get_transient( $key );
        set_transient( $key, $n + 1, 15 * MINUTE_IN_SECONDS );
    }

    public function throttle_login( $user ) {
        if ( empty( $_POST ) ) {
            return $user;
        }
        $ip = $this->client_ip();
        if ( ! $ip ) {
            return $user;
        }
        $key = 'tabarak_lf_' . md5( $ip );
        if ( (int) get_transient( $key ) >= 10 ) {
            return new WP_Error( 'tabarak_locked', __( 'Too many failed login attempts. Please wait 15 minutes and try again.', 'tabarak-core' ) );
        }
        return $user;
    }
}

Tabarak_Hardening::instance();

endif;
