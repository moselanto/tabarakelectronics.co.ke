<?php
/**
 * Tabarak setup wizard: guides plugin install and sample content creation.
 *
 * @package Tabarak_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Setup wizard controller.
 */
final class Tabarak_Setup_Wizard {

    /**
     * Wizard admin page slug.
     */
    const SLUG = 'tabarak-setup';

    /**
     * Boot the wizard hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_page' ) );
        add_action( 'admin_init', array( __CLASS__, 'maybe_redirect' ) );
        add_action( 'admin_notices', array( __CLASS__, 'activation_notice' ) );
        add_action( 'wp_ajax_tabarak_install_plugin', array( __CLASS__, 'ajax_install_plugin' ) );
        add_action( 'wp_ajax_tabarak_setup_content', array( __CLASS__, 'ajax_setup_content' ) );
    }

    /**
     * Register a hidden admin page for the wizard.
     */
    public static function register_page() {
        add_submenu_page(
            'tabarak-core',
            __( 'Setup Wizard', 'tabarak-core' ),
            __( 'Setup Wizard', 'tabarak-core' ),
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render' )
        );
    }

    /**
     * Redirect to the wizard once, right after activation.
     */
    public static function maybe_redirect() {
        if ( ! get_transient( 'tabarak_activation_redirect' ) ) {
            return;
        }
        delete_transient( 'tabarak_activation_redirect' );
        if ( wp_doing_ajax() || is_network_admin() ) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['activate-multi'] ) ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
        exit;
    }

    /**
     * Show a prompt to launch the wizard.
     */
    public static function activation_notice() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $screen = get_current_screen();
        if ( $screen && self::SLUG === $screen->parent_base ) {
            return;
        }
        if ( get_option( 'tabarak_wizard_complete' ) ) {
            return;
        }
        $url = admin_url( 'admin.php?page=' . self::SLUG );
        echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Tabarak Electronics is almost ready.', 'tabarak-core' ) . '</strong> ';
        echo esc_html__( 'Run the setup wizard to install the required plugins and create your pages.', 'tabarak-core' ) . ' ';
        echo '<a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Start setup wizard', 'tabarak-core' ) . '</a></p></div>';
    }

    /**
     * The curated list of free wp.org plugins the theme relies on.
     *
     * @return array
     */
    public static function plugins() {
        return array(
            array( 'slug' => 'woocommerce', 'file' => 'woocommerce/woocommerce.php', 'name' => 'WooCommerce', 'required' => true ),
            array( 'slug' => 'elementor', 'file' => 'elementor/elementor.php', 'name' => 'Elementor', 'required' => false ),
            array( 'slug' => 'contact-form-7', 'file' => 'contact-form-7/wp-contact-form-7.php', 'name' => 'Contact Form 7', 'required' => false ),
            array( 'slug' => 'advanced-custom-fields', 'file' => 'advanced-custom-fields/acf.php', 'name' => 'Advanced Custom Fields', 'required' => false ),
            array( 'slug' => 'wordpress-seo', 'file' => 'wordpress-seo/wp-seo.php', 'name' => 'Yoast SEO', 'required' => false ),
            array( 'slug' => 'safe-svg', 'file' => 'safe-svg/safe-svg.php', 'name' => 'Safe SVG', 'required' => false ),
            array( 'slug' => 'woo-variation-swatches', 'file' => 'woo-variation-swatches/woo-variation-swatches.php', 'name' => 'Variation Swatches for WooCommerce', 'required' => false ),
            array( 'slug' => 'ajax-search-for-woocommerce', 'file' => 'ajax-search-for-woocommerce/ajax-search-for-woocommerce.php', 'name' => 'FiboSearch (Ajax Search)', 'required' => false ),
            array( 'slug' => 'ti-woocommerce-wishlist', 'file' => 'ti-woocommerce-wishlist/ti-woocommerce-wishlist.php', 'name' => 'TI WooCommerce Wishlist', 'required' => false ),
            array( 'slug' => 'litespeed-cache', 'file' => 'litespeed-cache/litespeed-cache.php', 'name' => 'LiteSpeed Cache', 'required' => false ),
            array( 'slug' => 'google-listings-and-ads', 'file' => 'google-listings-and-ads/google-listings-and-ads.php', 'name' => 'Google for WooCommerce', 'required' => false ),
        );
    }

    /**
     * Determine a plugin status: active, installed or missing.
     *
     * @param string $file Plugin main file relative path.
     * @return string
     */
    public static function status( $file ) {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( is_plugin_active( $file ) ) {
            return 'active';
        }
        if ( file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
            return 'installed';
        }
        return 'missing';
    }

    /**
     * Current step from the query string.
     *
     * @return int
     */
    private static function current_step() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $step = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
        return max( 1, min( 4, $step ) );
    }

    /**
     * URL for a given step.
     *
     * @param int $step Step number.
     * @return string
     */
    private static function step_url( $step ) {
        return admin_url( 'admin.php?page=' . self::SLUG . '&step=' . (int) $step );
    }

    /**
     * Render the wizard.
     */
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        $step  = self::current_step();
        $nonce = wp_create_nonce( 'tabarak_wizard' );
        $steps = array(
            1 => __( 'Welcome', 'tabarak-core' ),
            2 => __( 'Plugins', 'tabarak-core' ),
            3 => __( 'Content', 'tabarak-core' ),
            4 => __( 'Ready', 'tabarak-core' ),
        );
        ?>
        <div class="wrap tabarak-wizard">
            <style>
                .tabarak-wizard{max-width:820px;margin:32px auto;}
                .tabarak-wizard h1{font-size:26px;}
                .tabarak-wizard__steps{display:flex;gap:8px;margin:18px 0 26px;padding:0;list-style:none;}
                .tabarak-wizard__steps li{flex:1;text-align:center;padding:10px;border-radius:8px;background:#f0f0f1;font-weight:600;color:#646970;}
                .tabarak-wizard__steps li.is-active{background:#f7a81b;color:#1a1200;}
                .tabarak-wizard__steps li.is-done{background:#0e1116;color:#fff;}
                .tabarak-card{background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:24px;}
                .tabarak-plist{width:100%;border-collapse:collapse;margin:12px 0;}
                .tabarak-plist th,.tabarak-plist td{text-align:left;padding:10px 8px;border-bottom:1px solid #f0f0f1;}
                .tabarak-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;}
                .tabarak-badge--active{background:#e6f4ea;color:#116329;}
                .tabarak-badge--installed{background:#fff4d6;color:#8a6100;}
                .tabarak-badge--missing{background:#fde7e9;color:#8a1c22;}
                .tabarak-actions{margin-top:22px;display:flex;justify-content:space-between;gap:12px;}
                .tabarak-log{margin-top:14px;font-family:monospace;font-size:12px;color:#3c434a;white-space:pre-wrap;}
            </style>

            <h1><?php esc_html_e( 'Tabarak Electronics Setup', 'tabarak-core' ); ?></h1>
            <ul class="tabarak-wizard__steps">
                <?php foreach ( $steps as $num => $label ) : ?>
                    <li class="<?php echo $num === $step ? 'is-active' : ( $num < $step ? 'is-done' : '' ); ?>"><?php echo esc_html( $num . '. ' . $label ); ?></li>
                <?php endforeach; ?>
            </ul>

            <div class="tabarak-card">
                <?php
                if ( 1 === $step ) {
                    self::render_welcome();
                } elseif ( 2 === $step ) {
                    self::render_plugins( $nonce );
                } elseif ( 3 === $step ) {
                    self::render_content( $nonce );
                } else {
                    self::render_ready();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Step 1 markup.
     */
    private static function render_welcome() {
        ?>
        <h2><?php esc_html_e( 'Welcome', 'tabarak-core' ); ?></h2>
        <p><?php esc_html_e( 'This wizard will install the plugins the Tabarak Electronics theme uses, then create your store pages with ready-made content. It takes about two minutes.', 'tabarak-core' ); ?></p>
        <ol>
            <li><?php esc_html_e( 'Install and activate the required plugins.', 'tabarak-core' ); ?></li>
            <li><?php esc_html_e( 'Create your pages (About, Contact, FAQs, delivery, warranty, policies and more).', 'tabarak-core' ); ?></li>
            <li><?php esc_html_e( 'Finish - your products are already imported.', 'tabarak-core' ); ?></li>
        </ol>
        <div class="tabarak-actions">
            <span></span>
            <a class="button button-primary button-hero" href="<?php echo esc_url( self::step_url( 2 ) ); ?>"><?php esc_html_e( 'Get started', 'tabarak-core' ); ?></a>
        </div>
        <?php
    }

    /**
     * Step 2 markup: plugins.
     *
     * @param string $nonce Wizard nonce.
     */
    private static function render_plugins( $nonce ) {
        $plugins = self::plugins();
        ?>
        <h2><?php esc_html_e( 'Install required plugins', 'tabarak-core' ); ?></h2>
        <p><?php esc_html_e( 'These are installed from the official WordPress.org directory. WooCommerce is required; the rest are recommended.', 'tabarak-core' ); ?></p>
        <table class="tabarak-plist">
            <thead><tr><th><?php esc_html_e( 'Plugin', 'tabarak-core' ); ?></th><th><?php esc_html_e( 'Status', 'tabarak-core' ); ?></th></tr></thead>
            <tbody>
            <?php foreach ( $plugins as $p ) : ?>
                <?php $status = self::status( $p['file'] ); ?>
                <tr data-slug="<?php echo esc_attr( $p['slug'] ); ?>" data-file="<?php echo esc_attr( $p['file'] ); ?>">
                    <td><?php echo esc_html( $p['name'] ); ?><?php echo ! empty( $p['required'] ) ? ' <em>(' . esc_html__( 'required', 'tabarak-core' ) . ')</em>' : ''; ?></td>
                    <td class="tabarak-status">
                        <span class="tabarak-badge tabarak-badge--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="tabarak-actions">
            <a class="button" href="<?php echo esc_url( self::step_url( 1 ) ); ?>"><?php esc_html_e( 'Back', 'tabarak-core' ); ?></a>
            <span>
                <button class="button button-primary" id="tabarak-install-all"><?php esc_html_e( 'Install & activate all', 'tabarak-core' ); ?></button>
                <a class="button button-secondary" href="<?php echo esc_url( self::step_url( 3 ) ); ?>"><?php esc_html_e( 'Skip', 'tabarak-core' ); ?></a>
            </span>
        </div>
        <div class="tabarak-log" id="tabarak-log" aria-live="polite"></div>
        <script>
        (function(){
            var nonce = <?php echo wp_json_encode( $nonce ); ?>;
            var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
            var nextUrl = <?php echo wp_json_encode( self::step_url( 3 ) ); ?>;
            var log = document.getElementById('tabarak-log');
            function write(msg){ log.textContent += msg + "\n"; }
            function installRow(row){
                return new Promise(function(resolve){
                    var slug = row.getAttribute('data-slug');
                    var file = row.getAttribute('data-file');
                    var badge = row.querySelector('.tabarak-badge');
                    if(badge && badge.classList.contains('tabarak-badge--active')){ resolve(); return; }
                    write('Installing ' + slug + '...');
                    var body = new URLSearchParams();
                    body.append('action','tabarak_install_plugin');
                    body.append('nonce',nonce);
                    body.append('slug',slug);
                    body.append('file',file);
                    fetch(ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
                    .then(function(r){return r.json();})
                    .then(function(res){
                        var cell = row.querySelector('.tabarak-status');
                        if(res && res.success){
                            cell.innerHTML = '<span class="tabarak-badge tabarak-badge--active">Active</span>';
                            write('  done: ' + slug);
                        } else {
                            var m = (res && res.data && res.data.message) ? res.data.message : 'failed';
                            cell.innerHTML = '<span class="tabarak-badge tabarak-badge--missing">Failed</span>';
                            write('  skipped ' + slug + ': ' + m);
                        }
                        resolve();
                    })
                    .catch(function(e){ write('  error ' + slug + ': ' + e); resolve(); });
                });
            }
            var btn = document.getElementById('tabarak-install-all');
            btn.addEventListener('click', function(e){
                e.preventDefault();
                btn.disabled = true;
                btn.textContent = 'Working...';
                var rows = Array.prototype.slice.call(document.querySelectorAll('.tabarak-plist tbody tr'));
                var chain = Promise.resolve();
                rows.forEach(function(row){ chain = chain.then(function(){ return installRow(row); }); });
                chain.then(function(){ btn.textContent = 'Done'; write('All finished. Continuing...'); setTimeout(function(){ window.location.href = nextUrl; }, 1200); });
            });
        })();
        </script>
        <?php
    }

    /**
     * Step 3 markup: content.
     *
     * @param string $nonce Wizard nonce.
     */
    private static function render_content( $nonce ) {
        ?>
        <h2><?php esc_html_e( 'Create your pages', 'tabarak-core' ); ?></h2>
        <p><?php esc_html_e( 'This creates your store pages with ready-made content and builds the main menu. Existing pages with the same name are left untouched.', 'tabarak-core' ); ?></p>
        <ul>
            <li><?php esc_html_e( 'About Us, Contact Us, FAQs', 'tabarak-core' ); ?></li>
            <li><?php esc_html_e( 'Delivery & Installation, Warranty & Service, Financing Options', 'tabarak-core' ); ?></li>
            <li><?php esc_html_e( 'Returns & Refund, Shipping, Privacy Policy, Terms & Conditions', 'tabarak-core' ); ?></li>
            <li><?php esc_html_e( 'A primary navigation menu linking Home, Shop, and the key pages', 'tabarak-core' ); ?></li>
        </ul>
        <div class="tabarak-actions">
            <a class="button" href="<?php echo esc_url( self::step_url( 2 ) ); ?>"><?php esc_html_e( 'Back', 'tabarak-core' ); ?></a>
            <button class="button button-primary" id="tabarak-create-content"><?php esc_html_e( 'Create pages & menu', 'tabarak-core' ); ?></button>
        </div>
        <div class="tabarak-log" id="tabarak-log" aria-live="polite"></div>
        <script>
        (function(){
            var nonce = <?php echo wp_json_encode( $nonce ); ?>;
            var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
            var nextUrl = <?php echo wp_json_encode( self::step_url( 4 ) ); ?>;
            var log = document.getElementById('tabarak-log');
            var btn = document.getElementById('tabarak-create-content');
            btn.addEventListener('click', function(e){
                e.preventDefault();
                btn.disabled = true; btn.textContent = 'Creating...';
                var body = new URLSearchParams();
                body.append('action','tabarak_setup_content');
                body.append('nonce',nonce);
                fetch(ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
                .then(function(r){return r.json();})
                .then(function(res){
                    if(res && res.success){
                        (res.data.created||[]).forEach(function(line){ log.textContent += line + "\n"; });
                        log.textContent += "Done. Continuing...\n";
                        setTimeout(function(){ window.location.href = nextUrl; }, 1400);
                    } else {
                        log.textContent += 'Error: ' + ((res&&res.data&&res.data.message)||'failed') + "\n";
                        btn.disabled = false; btn.textContent = 'Try again';
                    }
                })
                .catch(function(err){ log.textContent += 'Error: ' + err + "\n"; btn.disabled=false; btn.textContent='Try again'; });
            });
        })();
        </script>
        <?php
    }

    /**
     * Step 4 markup: done.
     */
    private static function render_ready() {
        update_option( 'tabarak_wizard_complete', 1 );
        $home = home_url( '/' );
        $shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : $home;
        ?>
        <h2><?php esc_html_e( 'Your website is ready', 'tabarak-core' ); ?></h2>
        <p><?php esc_html_e( 'Plugins are installed and your pages are live. Next, if products are not showing, run WooCommerce > Status > Tools > Regenerate product lookup tables and Recount terms.', 'tabarak-core' ); ?></p>
        <div class="tabarak-actions">
            <a class="button button-primary" href="<?php echo esc_url( $home ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View homepage', 'tabarak-core' ); ?></a>
            <a class="button" href="<?php echo esc_url( $shop ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View shop', 'tabarak-core' ); ?></a>
        </div>
        <?php
    }

    /**
     * AJAX: install and activate a single plugin from wp.org.
     */
    public static function ajax_install_plugin() {
        check_ajax_referer( 'tabarak_wizard', 'nonce' );
        if ( ! current_user_can( 'install_plugins' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tabarak-core' ) ) );
        }
        $slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
        $file = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';
        if ( '' === $slug || '' === $file ) {
            wp_send_json_error( array( 'message' => __( 'Missing plugin identifier.', 'tabarak-core' ) ) );
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        if ( ! file_exists( WP_PLUGIN_DIR . '/' . $file ) ) {
            $api = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );
            if ( is_wp_error( $api ) ) {
                wp_send_json_error( array( 'message' => $api->get_error_message() ) );
            }
            $skin     = new Automatic_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader( $skin );
            $result   = $upgrader->install( $api->download_link );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            }
            if ( null === $result || false === $result ) {
                wp_send_json_error( array( 'message' => __( 'Install failed.', 'tabarak-core' ) ) );
            }
        }

        $activate = activate_plugin( $file );
        if ( is_wp_error( $activate ) ) {
            wp_send_json_error( array( 'message' => $activate->get_error_message() ) );
        }
        wp_send_json_success( array( 'status' => 'active' ) );
    }

    /**
     * AJAX: create the sample pages and the primary menu.
     */
    public static function ajax_setup_content() {
        check_ajax_referer( 'tabarak_wizard', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'tabarak-core' ) ) );
        }
        $created = self::create_pages();
        self::build_menu();
        update_option( 'tabarak_wizard_complete', 1 );
        wp_send_json_success( array( 'created' => $created ) );
    }

    /**
     * Create the sample content pages.
     *
     * @return array Human-readable log lines.
     */
    private static function create_pages() {
        $log   = array();
        $pages = self::page_definitions();
        foreach ( $pages as $slug => $page ) {
            $existing = get_page_by_path( $slug );
            if ( $existing instanceof WP_Post ) {
                $log[] = 'Exists: ' . $page['title'];
                continue;
            }
            $id = wp_insert_post(
                array(
                    'post_title'   => $page['title'],
                    'post_name'    => $slug,
                    'post_content' => $page['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                )
            );
            if ( $id && ! is_wp_error( $id ) ) {
                $log[] = 'Created: ' . $page['title'];
                if ( 'privacy-policy' === $slug ) {
                    update_option( 'wp_page_for_privacy_policy', $id );
                }
                if ( 'terms-and-conditions' === $slug && function_exists( 'wc_get_page_id' ) ) {
                    update_option( 'woocommerce_terms_page_id', $id );
                }
            } else {
                $log[] = 'Failed: ' . $page['title'];
            }
        }
        return $log;
    }

    /**
     * Build a primary navigation menu.
     */
    private static function build_menu() {
        $menu_name = __( 'Primary Menu', 'tabarak-core' );
        $menu      = wp_get_nav_menu_object( $menu_name );
        if ( ! $menu ) {
            $menu_id = wp_create_nav_menu( $menu_name );
        } else {
            $menu_id = (int) $menu->term_id;
        }
        if ( is_wp_error( $menu_id ) ) {
            return;
        }

        $existing = wp_get_nav_menu_items( $menu_id );
        if ( ! empty( $existing ) ) {
            $locations            = get_theme_mod( 'nav_menu_locations', array() );
            $locations['primary'] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
            return;
        }

        wp_update_nav_menu_item( $menu_id, 0, array(
            'menu-item-title'  => __( 'Home', 'tabarak-core' ),
            'menu-item-url'    => home_url( '/' ),
            'menu-item-status' => 'publish',
        ) );
        if ( function_exists( 'wc_get_page_permalink' ) ) {
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'  => __( 'Shop', 'tabarak-core' ),
                'menu-item-url'    => wc_get_page_permalink( 'shop' ),
                'menu-item-status' => 'publish',
            ) );
        }
        foreach ( array( 'about-us' => __( 'About Us', 'tabarak-core' ), 'financing-options' => __( 'Financing', 'tabarak-core' ), 'contact-us' => __( 'Contact Us', 'tabarak-core' ) ) as $slug => $title ) {
            $page = get_page_by_path( $slug );
            if ( $page instanceof WP_Post ) {
                wp_update_nav_menu_item( $menu_id, 0, array(
                    'menu-item-title'     => $title,
                    'menu-item-object'    => 'page',
                    'menu-item-object-id' => $page->ID,
                    'menu-item-type'      => 'post_type',
                    'menu-item-status'    => 'publish',
                ) );
            }
        }

        $locations            = get_theme_mod( 'nav_menu_locations', array() );
        $locations['primary'] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );
    }

    /**
     * Definitions for the sample pages.
     *
     * @return array
     */
    private static function page_definitions() {
        $name  = 'Tabarak Electronics Kenya';
        $phone = '0721606030';
        $hours = 'Mon - Fri / 9:00 AM - 6:00 PM';
        $addr  = 'Nairobi Central, Luthuli St, Nairobi Sky Mall Bldg, Nairobi, Kenya';
        $email = 'info@tabarakelectronics.co.ke';

        $about = '<h2>About ' . $name . '</h2>'
            . '<p>' . $name . ' is a trusted electronics retailer in the heart of Nairobi, supplying genuine, warranty-backed televisions, refrigerators, washing machines, cookers, microwaves, air conditioners, water dispensers, small kitchen appliances, laptops, mobile phones, audio systems, CCTV and smart-home products to customers across Kenya.</p>'
            . '<p>We serve families setting up a home, students, and businesses of every size - hotels, schools, hospitals, government institutions, interior designers and property developers - helping each choose the right product, then delivering and installing it professionally.</p>'
            . '<h3>Why customers trust us</h3>'
            . '<ul><li>Only genuine products sourced through authorised channels</li><li>Manufacturer warranty on every item</li><li>Nationwide delivery with professional installation</li><li>Secure payment by M-Pesa, cash and bank transfer</li><li>Knowledgeable after-sales support</li></ul>'
            . '<h3>Visit or contact us</h3>'
            . '<p><strong>Shop:</strong> ' . $addr . '<br><strong>Phone / WhatsApp:</strong> ' . $phone . '<br><strong>Email:</strong> ' . $email . '<br><strong>Hours:</strong> ' . $hours . '</p>';

        $contact = '<h2>Contact Us</h2>'
            . '<p>Our team is happy to help you choose the right electronics, arrange delivery and installation, or answer any question about your order.</p>'
            . '<h3>Reach us</h3>'
            . '<p><strong>Address:</strong> ' . $addr . '<br><strong>Phone / WhatsApp:</strong> ' . $phone . '<br><strong>Email:</strong> ' . $email . '<br><strong>Opening hours:</strong> ' . $hours . '</p>'
            . '<p>For the fastest response, call us or message us on WhatsApp during business hours. For corporate, hotel, school or bulk enquiries, contact us for institutional pricing.</p>'
            . '[contact-form-7 title="Contact form"]';

        $faq = '<h2>Frequently Asked Questions</h2>'
            . '<h3>Are your products genuine and covered by warranty?</h3><p>Yes. Every product is genuine and carries the manufacturer warranty. Keep your receipt as proof of purchase.</p>'
            . '<h3>Which payment methods do you accept?</h3><p>M-Pesa, cash, and bank transfer. All payments are processed securely.</p>'
            . '<h3>Do you deliver countrywide?</h3><p>Yes. Delivery within Nairobi and major towns is fast (often next-day). Other locations are served through trusted couriers.</p>'
            . '<h3>Can I pick up from your shop?</h3><p>Yes. Choose store pickup at checkout and collect from our Nairobi shop during business hours.</p>'
            . '<h3>Do you install appliances?</h3><p>Yes. We offer professional installation for TVs, air conditioners, cookers, water dispensers and built-in appliances.</p>'
            . '<h3>What is your return policy?</h3><p>Unused items in original packaging may be returned within 7 days. See our Returns & Refund Policy for details.</p>';

        $delivery = '<h2>Delivery & Installation</h2>'
            . '<p>We offer two convenient options at checkout: <strong>Shipping</strong> to your address, or <strong>Store pickup</strong> from our Nairobi shop.</p>'
            . '<h3>Delivery areas and timelines</h3>'
            . '<ul><li><strong>Nairobi & major towns:</strong> free next-day delivery on qualifying orders.</li><li><strong>Other locations:</strong> 2-5 business days via trusted couriers; cost is calculated at checkout by location and item size.</li></ul>'
            . '<h3>Store pickup</h3><p>Select store pickup at checkout and collect from ' . $addr . ' during ' . $hours . '. We will notify you when your order is ready.</p>'
            . '<h3>Installation</h3><p>Professional installation is available for large appliances and electronics. Add installation to your order or ask our team on ' . $phone . '.</p>';

        $warranty = '<h2>Warranty & Service</h2>'
            . '<p>All products are covered by the manufacturer warranty from the date of purchase. Warranty periods vary by brand and product and are stated on your receipt or product documentation.</p>'
            . '<h3>How to make a claim</h3><p>Contact us on ' . $phone . ' or ' . $email . ' with your receipt and a description of the fault. We will guide you to the nearest authorised service centre or arrange support.</p>'
            . '<h3>What is not covered</h3><p>Physical or liquid damage, misuse, unauthorised repairs, and normal wear are not covered by warranty.</p>';

        $financing = '<h2>Financing & Payment Options</h2>'
            . '<p>We make it easier to get what you need with flexible, secure payment options.</p>'
            . '<ul><li><strong>M-Pesa:</strong> fast, secure mobile payment at checkout.</li><li><strong>Cash:</strong> pay on collection or on delivery where available.</li><li><strong>Bank transfer:</strong> ideal for large and corporate orders.</li><li><strong>Instalments:</strong> pay-in-parts options on selected items - ask our team.</li></ul>'
            . '<p>Talk to us on ' . $phone . ' to find the best option for you.</p>';

        $returns = '<h2>Returns & Refund Policy</h2>'
            . '<p>Your satisfaction is important to us. If you are not satisfied, you may return eligible items under the terms below.</p>'
            . '<h3>Return window</h3><p>Items may be returned within <strong>7 days</strong> of delivery or collection, provided they are unused, in their original packaging with all accessories, and accompanied by the receipt.</p>'
            . '<h3>How to return</h3><p>Contact us on ' . $phone . ' or ' . $email . ' to start a return. We will advise whether to bring the item to our shop or arrange collection.</p>'
            . '<h3>Refunds</h3><p>Once we receive and inspect the item, approved refunds are processed to your original payment method (M-Pesa, bank or cash) within a reasonable period, typically 3-7 business days.</p>'
            . '<h3>Faulty or wrong items</h3><p>Report dead-on-arrival, faulty or incorrect items within 48 hours of delivery for a free replacement or full refund, including any delivery cost.</p>'
            . '<h3>Non-returnable items</h3><p>For hygiene and safety, certain items may be non-returnable once opened. This does not affect your rights regarding faulty goods.</p>';

        $shipping = '<h2>Shipping Policy</h2>'
            . '<p>We ship nationwide from our Nairobi shop using trusted couriers.</p>'
            . '<h3>Costs</h3><p>Delivery within Nairobi and major towns is free on qualifying orders. For other locations and large items, the exact shipping cost is shown at checkout before you pay.</p>'
            . '<h3>Timelines</h3><p>Nairobi and major towns: often next-day. Other regions: 2-5 business days. Large or installed items may be scheduled with you directly.</p>'
            . '<h3>Order tracking</h3><p>You will receive updates by phone or SMS. For any query, contact us on ' . $phone . '.</p>'
            . '<h3>Damaged in transit</h3><p>Please inspect your item on arrival and report any transit damage immediately so we can resolve it quickly.</p>';

        $privacy = '<h2>Privacy Policy</h2>'
            . '<p>' . $name . ' respects your privacy and is committed to protecting your personal data.</p>'
            . '<h3>What we collect</h3><p>We collect only what we need to process your orders and support you - such as your name, phone number, delivery address, email and order history.</p>'
            . '<h3>How we use it</h3><p>To process and deliver orders, provide customer support, prevent fraud, and (only with your consent) send offers and updates. We do not sell your personal data.</p>'
            . '<h3>Payments & security</h3><p>Payments are handled through secure channels. Your checkout is protected by SSL encryption.</p>'
            . '<h3>Your rights</h3><p>You may request access to, correction of, or deletion of your personal data by contacting us on ' . $phone . ' or ' . $email . '.</p>';

        $terms = '<h2>Terms & Conditions</h2>'
            . '<p>By using this website and buying from ' . $name . ', you agree to these terms.</p>'
            . '<h3>Products & pricing</h3><p>Prices are in Kenyan Shillings (KES) and include applicable taxes unless stated otherwise. We make every effort to display accurate prices and stock, and reserve the right to correct errors.</p>'
            . '<h3>Orders & payment</h3><p>An order is confirmed once payment is received via M-Pesa, cash or bank transfer. We may cancel and fully refund orders affected by stock or pricing errors.</p>'
            . '<h3>Delivery</h3><p>Delivery timelines are estimates. Risk passes to you on delivery or collection.</p>'
            . '<h3>Warranty & liability</h3><p>Manufacturer warranties apply to product defects. Our liability is limited to the value of the product purchased, to the extent permitted by law.</p>'
            . '<h3>Contact</h3><p>Questions about these terms? Contact us on ' . $phone . ' or ' . $email . '.</p>';

        return array(
            'about-us'             => array( 'title' => 'About Us', 'content' => $about ),
            'contact-us'           => array( 'title' => 'Contact Us', 'content' => $contact ),
            'faqs'                 => array( 'title' => 'FAQs', 'content' => $faq ),
            'delivery-installation' => array( 'title' => 'Delivery & Installation', 'content' => $delivery ),
            'warranty-service'     => array( 'title' => 'Warranty & Service', 'content' => $warranty ),
            'financing-options'    => array( 'title' => 'Financing Options', 'content' => $financing ),
            'returns-refund'       => array( 'title' => 'Returns & Refund Policy', 'content' => $returns ),
            'shipping-policy'      => array( 'title' => 'Shipping Policy', 'content' => $shipping ),
            'privacy-policy'       => array( 'title' => 'Privacy Policy', 'content' => $privacy ),
            'terms-and-conditions' => array( 'title' => 'Terms & Conditions', 'content' => $terms ),
        );
    }
}
