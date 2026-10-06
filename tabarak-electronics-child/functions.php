<?php
/**
 * SAFETY: This code is strictly non-destructive to shop data. It NEVER creates,
 * renames, deletes, or reassigns product categories (product_cat) or products.
 * All category/product access is read-only (get_terms/get_term/get_term_by/WP_Query).
 * The only content it can create is standard WordPress PAGES, and only when you
 * explicitly run the Setup Wizard (and it skips pages that already exist).
 */
/**
 * Tabarak Electronics Child theme functions.
 *
 * @package Tabarak_Electronics_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tabarak_child_enqueue' ) ) {
    /**
     * Load the child stylesheet after the parent's main stylesheet.
     * The parent registers its main stylesheet with the handle "tabarak-main".
     */
    function tabarak_child_enqueue() {
        wp_enqueue_style(
            'tabarak-child',
            get_stylesheet_uri(),
            array( 'tabarak-main' ),
            wp_get_theme()->get( 'Version' )
        );
        wp_add_inline_style( 'tabarak-child', tabarak_child_mobile_overrides() );
    }
}
add_action( 'wp_enqueue_scripts', 'tabarak_child_enqueue', 20 );


/**
 * Render a single product card. Uses WP data directly so it is resilient to
 * stale WooCommerce lookup tables after a bulk import.
 *
 * @param int $product_id Product post ID.
 */
if ( ! function_exists( 'tabarak_child_product_card' ) ) {
    function tabarak_child_product_card( $product_id ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return;
        }
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return;
        }
        $link  = get_permalink( $product_id );
        $thumb = get_the_post_thumbnail(
            $product_id,
            'woocommerce_thumbnail',
            array( 'loading' => 'lazy', 'alt' => esc_attr( $product->get_name() ) )
        );
        if ( ! $thumb ) {
            $tabarak_ph = get_stylesheet_directory_uri() . '/assets/images/no-image.png';
            $thumb = '<img class="tabarak-pcard__phimg" src="' . esc_url( $tabarak_ph ) . '" alt="' . esc_attr( $product->get_name() ) . '" loading="lazy" width="400" height="400" />';
        }
        echo '<li class="tabarak-pcard">';
        echo '<a class="tabarak-pcard__media" href="' . esc_url( $link ) . '">' . $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $badge = tabarak_child_sale_badge( $product );
        if ( $badge ) {
            echo '<span class="tabarak-pcard__badge">' . esc_html( $badge ) . '</span>';
        }
        echo '</a>';
        echo '<div class="tabarak-pcard__body">';
        echo '<a class="tabarak-pcard__title" href="' . esc_url( $link ) . '">' . esc_html( $product->get_name() ) . '</a>';
        echo '<div class="tabarak-pcard__price">' . wp_kses_post( $product->get_price_html() ) . '</div>';
        echo '<a href="' . esc_url( $product->add_to_cart_url() ) . '" class="tabarak-pcard__btn button add_to_cart_button ajax_add_to_cart" data-product_id="' . esc_attr( $product_id ) . '" data-quantity="1" rel="nofollow">' . esc_html( $product->add_to_cart_text() ) . '</a>';
        echo '</div>';
        echo '</li>';
    }
}

/**
 * Render a titled horizontal product carousel from a WP_Query args array.
 * Returns true when at least one product was output.
 *
 * @param array  $args         WP_Query arguments.
 * @param string $title        Section title.
 * @param string $view_all_url Optional "view all" link.
 * @return bool
 */
if ( ! function_exists( 'tabarak_child_carousel' ) ) {
    function tabarak_child_carousel( $args, $title, $view_all_url = '' ) {
        $base = array(
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => 10,
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        );
        $query = new WP_Query( array_merge( $base, $args ) );
        if ( ! $query->have_posts() ) {
            wp_reset_postdata();
            return false;
        }
        echo '<section class="tabarak-section tabarak-row"><div class="tabarak-container">';
        echo '<header class="tabarak-section__head"><h2 class="tabarak-section__title">' . esc_html( $title ) . '</h2>';
        if ( $view_all_url ) {
            echo '<a class="tabarak-section__link" href="' . esc_url( $view_all_url ) . '">' . esc_html__( 'View all', 'tabarak-electronics-child' ) . '</a>';
        }
        echo '</header>';
        echo '<ul class="tabarak-carousel">';
        while ( $query->have_posts() ) {
            $query->the_post();
            tabarak_child_product_card( get_the_ID() );
        }
        echo '</ul></div></section>';
        wp_reset_postdata();
        return true;
    }
}


/* ============================================================
 * Advanced shop UX additions
 * ============================================================ */

/**
 * Enqueue the shop interactions script and expose data to JS.
 */
if ( ! function_exists( 'tabarak_child_shop_assets' ) ) {
    function tabarak_child_shop_assets() {
        wp_enqueue_script(
            'tabarak-shop',
            get_stylesheet_directory_uri() . '/assets/js/shop.js',
            array(),
            wp_get_theme()->get( 'Version' ),
            true
        );
        wp_localize_script(
            'tabarak-shop',
            'tabarakShop',
            array(
                'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
                'searchNonce' => wp_create_nonce( 'tabarak_search' ),
                'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
                'placeholder' => get_stylesheet_directory_uri() . '/assets/images/no-image.png',
                'recent'      => tabarak_child_current_product_payload(),
                'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '',
                'i18n'        => array(
                    'added'     => __( 'Added to your cart', 'tabarak-electronics-child' ),
                    'checkout'  => __( 'Checkout', 'tabarak-electronics-child' ),
                    'continue'  => __( 'Continue shopping', 'tabarak-electronics-child' ),
                    'searching' => __( 'Searching...', 'tabarak-electronics-child' ),
                    'noResults' => __( 'No products found', 'tabarak-electronics-child' ),
                ),
            )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'tabarak_child_shop_assets', 20 );

/**
 * Return the four hero slides, merging Customizer values over bundled defaults.
 *
 * @return array
 */
if ( ! function_exists( 'tabarak_child_hero_slides' ) ) {
    function tabarak_child_hero_slides() {
        $uri  = get_stylesheet_directory_uri() . '/assets/images/';
        $shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
        $defaults = array(
            array( 'img' => $uri . 'hero-1.jpg', 'title' => __( 'Big screens, bigger savings', 'tabarak-electronics-child' ), 'text' => __( 'Premium 4K & QLED TVs with genuine warranty and fast delivery.', 'tabarak-electronics-child' ), 'link' => $shop ),
            array( 'img' => $uri . 'hero-2.jpg', 'title' => __( 'Keep it cool, keep it fresh', 'tabarak-electronics-child' ), 'text' => __( 'Energy-smart refrigerators and freezers from the brands you trust.', 'tabarak-electronics-child' ), 'link' => $shop ),
            array( 'img' => $uri . 'hero-3.jpg', 'title' => __( 'Laundry made easy', 'tabarak-electronics-child' ), 'text' => __( 'Washing machines and dryers built for busy Kenyan homes.', 'tabarak-electronics-child' ), 'link' => $shop ),
            array( 'img' => $uri . 'hero-4.jpg', 'title' => __( 'Cook and blend in style', 'tabarak-electronics-child' ), 'text' => __( 'Microwaves, blenders and kitchen essentials at great prices.', 'tabarak-electronics-child' ), 'link' => $shop ),
        );
        $slides = array();
        for ( $i = 1; $i <= 4; $i++ ) {
            $d   = $defaults[ $i - 1 ];
            $img = get_theme_mod( 'tabarak_hero' . $i . '_image', '' );
            $slides[] = array(
                'img'   => $img ? $img : $d['img'],
                'title' => get_theme_mod( 'tabarak_hero' . $i . '_title', $d['title'] ),
                'text'  => get_theme_mod( 'tabarak_hero' . $i . '_text', $d['text'] ),
                'link'  => get_theme_mod( 'tabarak_hero' . $i . '_link', $d['link'] ),
            );
        }
        return $slides;
    }
}

/**
 * Register Customizer controls for the four hero slides.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
if ( ! function_exists( 'tabarak_child_customize' ) ) {
    function tabarak_child_customize( $wp_customize ) {
        $wp_customize->add_section( 'tabarak_announce', array( 'title' => __( 'Tabarak: Announcement bar', 'tabarak-electronics-child' ), 'priority' => 30 ) );
        $wp_customize->add_setting( 'tabarak_topbar_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( 'tabarak_topbar_text', array( 'label' => __( 'Top announcement text', 'tabarak-electronics-child' ), 'section' => 'tabarak_announce', 'type' => 'text' ) );
        $wp_customize->add_section(
            'tabarak_hero',
            array(
                'title'    => __( 'Tabarak: Homepage Hero Slides', 'tabarak-electronics-child' ),
                'priority' => 31,
            )
        );
        for ( $i = 1; $i <= 4; $i++ ) {
            $wp_customize->add_setting( 'tabarak_hero' . $i . '_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
            $wp_customize->add_control(
                new WP_Customize_Image_Control(
                    $wp_customize,
                    'tabarak_hero' . $i . '_image',
                    array(
                        /* translators: %d: slide number. */
                        'label'   => sprintf( __( 'Slide %d image', 'tabarak-electronics-child' ), $i ),
                        'section' => 'tabarak_hero',
                    )
                )
            );
            $wp_customize->add_setting( 'tabarak_hero' . $i . '_title', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
            $wp_customize->add_control( 'tabarak_hero' . $i . '_title', array(
                /* translators: %d: slide number. */
                'label' => sprintf( __( 'Slide %d title', 'tabarak-electronics-child' ), $i ), 'section' => 'tabarak_hero', 'type' => 'text' ) );
            $wp_customize->add_setting( 'tabarak_hero' . $i . '_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
            $wp_customize->add_control( 'tabarak_hero' . $i . '_text', array(
                /* translators: %d: slide number. */
                'label' => sprintf( __( 'Slide %d subtitle', 'tabarak-electronics-child' ), $i ), 'section' => 'tabarak_hero', 'type' => 'text' ) );
            $wp_customize->add_setting( 'tabarak_hero' . $i . '_link', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
            $wp_customize->add_control( 'tabarak_hero' . $i . '_link', array(
                /* translators: %d: slide number. */
                'label' => sprintf( __( 'Slide %d button link', 'tabarak-electronics-child' ), $i ), 'section' => 'tabarak_hero', 'type' => 'url' ) );
        }
    }
}
add_action( 'customize_register', 'tabarak_child_customize' );

/**
 * Product category groups for the homepage rows (Simon's exact list).
 * Each row resolves by category NAME first, then slug, so it works
 * regardless of the exact taxonomy slugs on the live site.
 *
 * @return array label => array of candidate category names.
 */
if ( ! function_exists( 'tabarak_child_category_groups' ) ) {
    function tabarak_child_category_groups() {
        return array(
            __( 'Televisions', 'tabarak-electronics-child' )                => array( 'Televisions', 'Television', 'TVs', 'TV', 'Smart TVs', 'LED TVs', 'QLED TVs', 'OLED TVs' ),
            __( 'Cookers & Ovens', 'tabarak-electronics-child' )            => array( 'Cookers & Ovens', 'Cookers and Ovens', 'Cookers', 'Cooker', 'Ovens', 'Oven', 'Gas Cookers', 'Electric Cookers', 'Built-in Ovens' ),
            __( 'Audio & Home Theatre', 'tabarak-electronics-child' )       => array( 'Audio & Home Theatre', 'Audio & Home Theater', 'Audio and Home Theatre', 'Home Theatre', 'Home Theater', 'Audio', 'Sound Systems', 'Soundbars', 'Speakers', 'Hi-Fi', 'Radios' ),
            __( 'Refrigerators', 'tabarak-electronics-child' )              => array( 'Refrigerators', 'Refrigerator', 'Fridges', 'Fridge', 'Fridges & Freezers', 'Freezers', 'Chillers & Coolers' ),
            __( 'Washing Machines & Dryers', 'tabarak-electronics-child' )  => array( 'Washing Machines & Dryers', 'Washing Machines and Dryers', 'Washing Machines', 'Washing Machine', 'Washers & Dryers', 'Washers', 'Dryers' ),
            __( 'Microwaves & Blenders', 'tabarak-electronics-child' )      => array( 'Microwaves & Blenders', 'Microwaves and Blenders', 'Microwaves', 'Microwave', 'Blenders', 'Blender', 'Mixers', 'Juicers', 'Food Processors' ),
            __( 'Irons & Garment Care', 'tabarak-electronics-child' )       => array( 'Irons & Garment Care', 'Irons and Garment Care', 'Irons', 'Iron', 'Garment Care', 'Steamers', 'Garment Steamers' ),
            __( 'Cookware, Kettles & Home', 'tabarak-electronics-child' )   => array( 'Cookware & Bakeware', 'Cookware and Bakeware', 'Cookware', 'Bakeware', 'Kettles', 'Kettle', 'Water Dispensers', 'Water Dispenser', 'Cooker Hoods & Extractors', 'Cooker Hoods', 'Extractors', 'Hoods', 'Pressure & Multi Cookers', 'Coffee Makers' ),
        );
    }
}

/**
 * Resolve a list of candidate category names to unique product_cat term IDs.
 *
 * @param array $names Candidate category names.
 * @return int[] Unique term IDs (empty if none match).
 */
if ( ! function_exists( 'tabarak_child_resolve_term_ids' ) ) {
    function tabarak_child_resolve_term_ids( $names ) {
        $ids = array();
        foreach ( (array) $names as $name ) {
            $term = get_term_by( 'name', $name, 'product_cat' );
            if ( ! $term || is_wp_error( $term ) ) {
                $term = get_term_by( 'slug', sanitize_title( $name ), 'product_cat' );
            }
            if ( $term && ! is_wp_error( $term ) ) {
                $ids[ (int) $term->term_id ] = (int) $term->term_id;
            }
        }
        return array_values( $ids );
    }
}

/**
 * Render a product carousel for a group of candidate category names.
 * Skips rendering entirely when the group has no matching products.
 *
 * @param string $title Section title.
 * @param array  $names Candidate category names.
 * @param int    $limit Max products (the set size to scroll through).
 * @return bool
 */
if ( ! function_exists( 'tabarak_child_group_carousel' ) ) {
    function tabarak_child_group_carousel( $title, $names, $limit = 12 ) {
        $ids = tabarak_child_resolve_term_ids( $names );
        if ( empty( $ids ) ) {
            return false;
        }
        $view_all = '';
        $first    = get_term( $ids[0], 'product_cat' );
        if ( $first && ! is_wp_error( $first ) ) {
            $view_all = get_term_link( $first );
        }
        $pick = function_exists( 'tabarak_child_random_product_ids' ) ? tabarak_child_random_product_ids( $ids, array(), (int) $limit, ( function_exists( 'tabarak_child_brand_tax' ) ? tabarak_child_brand_tax() : '' ) ) : array();
        if ( count( $pick ) === 0 ) {
            return false;
        }
        return tabarak_child_carousel(
            array(
                'posts_per_page' => (int) $limit,
                'post__in'       => $pick,
                'orderby'        => 'post__in',
            ),
            $title,
            $view_all
        );
    }
}

/**
 * Fast AJAX product search (title + content), returns compact JSON.
 */
if ( ! function_exists( 'tabarak_ajax_search' ) ) {
    function tabarak_ajax_search() {
        // Read-only public search: no nonce (cached pages would serve expired nonces
        // and break search after 24h). Input is length-capped and results cached.
        $term  = isset( $_GET['q'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_GET['q'] ) ), 0, 60 ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $ckey  = 'tabarak_sr_' . md5( strtolower( $term ) );
        $hit   = strlen( $term ) >= 2 ? get_transient( $ckey ) : false;
        if ( is_array( $hit ) ) {
            wp_send_json_success( $hit );
        }
        $items = array();
        if ( strlen( $term ) < 2 || ! function_exists( 'wc_get_product' ) ) {
            wp_send_json_success( array( 'items' => $items, 'more' => '' ) );
        }
        $byname = new WP_Query( array(
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => 10,
            's'                   => $term,
            'fields'              => 'ids',
            'no_found_rows'       => true,
            'ignore_sticky_posts' => true,
        ) );
        $ids = $byname->posts;
        if ( count( $ids ) < 10 ) {
            $bysku = new WP_Query( array(
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'posts_per_page'      => 10,
                'fields'              => 'ids',
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
                'meta_query'          => array(
                    array( 'key' => '_sku', 'value' => $term, 'compare' => 'LIKE' ),
                ),
            ) );
            $ids = array_unique( array_merge( $ids, $bysku->posts ) );
        }
        $ids = array_slice( $ids, 0, 8 );
        foreach ( $ids as $pid ) {
            $product = wc_get_product( $pid );
            if ( ! $product ) {
                continue;
            }
            $terms = get_the_terms( $pid, 'product_cat' );
            $cat   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
            $thumb = get_the_post_thumbnail_url( $pid, 'thumbnail' );
            $items[] = array(
                'title' => $product->get_name(),
                'url'   => get_permalink( $pid ),
                'price' => wp_strip_all_tags( $product->get_price_html() ),
                'img'   => $thumb ? $thumb : '',
                'cat'   => $cat,
            );
        }
        $more = add_query_arg( array( 's' => $term, 'post_type' => 'product' ), home_url( '/' ) );
        set_transient( $ckey, array( 'items' => $items, 'more' => $more ), 10 * MINUTE_IN_SECONDS );
        wp_send_json_success( array( 'items' => $items, 'more' => $more ) );
    }
}
add_action( 'wp_ajax_tabarak_search', 'tabarak_ajax_search' );
add_action( 'wp_ajax_nopriv_tabarak_search', 'tabarak_ajax_search' );

/**
 * Professional trust badges with icons on the single product summary.
 */
if ( ! function_exists( 'tabarak_child_single_trust' ) ) {
    function tabarak_child_single_trust() {
        $items = array(
            array( 'label' => __( 'Fast next-day delivery in Nairobi & major towns', 'tabarak-electronics-child' ), 'icon' => 'truck' ),
            array( 'label' => __( 'Genuine product with warranty', 'tabarak-electronics-child' ), 'icon' => 'shield' ),
            array( 'label' => __( 'Secure checkout', 'tabarak-electronics-child' ), 'icon' => 'lock' ),
            array( 'label' => __( '7-day easy returns', 'tabarak-electronics-child' ), 'icon' => 'return' ),
        );
        $svgs = tabarak_child_trust_icons();
        echo '<ul class="tabarak-trust">';
        foreach ( $items as $it ) {
            $svg = isset( $svgs[ $it['icon'] ] ) ? $svgs[ $it['icon'] ] : '';
            echo '<li class="tabarak-trust__item"><span class="tabarak-trust__icon" aria-hidden="true">' . $svg . '</span><span class="tabarak-trust__text">' . esc_html( $it['label'] ) . '</span></li>';
        }
        echo '</ul>';
    }
}
add_action( 'woocommerce_single_product_summary', 'tabarak_child_single_trust', 35 );

/**
 * Inline SVG icon set for the trust badges (no external requests).
 */
if ( ! function_exists( 'tabarak_child_trust_icons' ) ) {
    function tabarak_child_trust_icons() {
        return array(
            'truck'  => '<svg viewBox="0 0 24 24" fill="none"><path d="M2 6h11v9H2zM13 9h4l3 3v3h-7z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><circle cx="7" cy="17" r="1.7" stroke="currentColor" stroke-width="1.7"/><circle cx="17" cy="17" r="1.7" stroke="currentColor" stroke-width="1.7"/></svg>',
            'shield' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3l7 3v5c0 5-3.2 8.6-7 11-3.8-2.4-7-6-7-11V6z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M9 12l2 2 4-4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'lock'   => '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="10" width="16" height="10" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V8a4 4 0 018 0v2" stroke="currentColor" stroke-width="1.7"/></svg>',
            'return' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 9h10a5 5 0 010 10H9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 6L4 9l3 3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        );
    }
}


/**
 * Output a footer link to a page by slug, only if the page exists.
 *
 * @param string $slug  Page slug.
 * @param string $label Link label.
 */
if ( ! function_exists( 'tabarak_child_footer_link' ) ) {
    function tabarak_child_footer_link( $slug, $label ) {
        $page = get_page_by_path( $slug );
        if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
            printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $page ) ), esc_html( $label ) );
        } elseif ( 'privacy-policy' === $slug && function_exists( 'get_privacy_policy_url' ) && get_privacy_policy_url() ) {
            printf( '<li><a href="%s">%s</a></li>', esc_url( get_privacy_policy_url() ), esc_html( $label ) );
        }
    }
}

/* ============================================================
 * Tabarak round 5: sale badges, full-width shop, related grid, WhatsApp
 * ============================================================ */

/**
 * Build a short sale badge like "27% OFF"; falls back to "Sale".
 */
if ( ! function_exists( 'tabarak_child_sale_badge' ) ) {
    function tabarak_child_sale_badge( $product ) {
        if ( ! is_object( $product ) || ! method_exists( $product, 'is_on_sale' ) || ! $product->is_on_sale() ) {
            return '';
        }
        $regular = (float) $product->get_regular_price();
        $sale    = (float) $product->get_sale_price();
        if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
            $pct = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
            if ( $pct > 0 ) {
                /* translators: %d: discount percent. */
                return sprintf( __( '%d%% OFF', 'tabarak-electronics-child' ), $pct );
            }
        }
        return __( 'Sale', 'tabarak-electronics-child' );
    }
}

/**
 * "NN% OFF" flash on shop/category/single product images.
 */
if ( ! function_exists( 'tabarak_child_sale_flash' ) ) {
    function tabarak_child_sale_flash( $html, $post, $product ) {
        $badge = function_exists( 'tabarak_child_sale_badge' ) ? tabarak_child_sale_badge( $product ) : '';
        if ( ! $badge ) {
            return $html;
        }
        return '<span class="onsale tabarak-onsale">' . esc_html( $badge ) . '</span>';
    }
}
add_filter( 'woocommerce_sale_flash', 'tabarak_child_sale_flash', 10, 3 );

/**
 * Full-width shop: remove the default WooCommerce sidebar (also removes the
 * stray Search / Recent Posts / Recent Comments widgets on shop pages).
 */
if ( ! function_exists( 'tabarak_child_remove_wc_sidebar' ) ) {
    function tabarak_child_remove_wc_sidebar() {
        remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
    }
}
add_action( 'init', 'tabarak_child_remove_wc_sidebar', 99 );

/**
 * Evenly-arranged related / up-sell products (4 across, no empty slot).
 */
if ( ! function_exists( 'tabarak_child_related_args' ) ) {
    function tabarak_child_related_args( $args ) {
        $args['posts_per_page'] = 4;
        $args['columns']        = 4;
        return $args;
    }
}
add_filter( 'woocommerce_output_related_products_args', 'tabarak_child_related_args' );
add_filter( 'woocommerce_upsell_display_args', 'tabarak_child_related_args' );

/**
 * Inline "Order on WhatsApp" button next to Add to cart (number from settings).
 */
if ( ! function_exists( 'tabarak_child_wa_after_cart' ) ) {
    function tabarak_child_wa_after_cart() {
        if ( ! function_exists( 'tabarak_get_business' ) ) {
            return;
        }
        $wa = tabarak_get_business( 'whatsapp' );
        if ( ! $wa ) {
            return;
        }
        global $product;
        $name = is_object( $product ) && method_exists( $product, 'get_name' ) ? $product->get_name() : '';
        $msg  = rawurlencode( sprintf( "Hello Tabarak, I would like to order: %s\n%s", $name, get_permalink() ) );
        echo '<a class="tabarak-wa-btn" href="https://wa.me/' . esc_attr( $wa ) . '?text=' . $msg . '" target="_blank" rel="noopener nofollow"><span class="tabarak-wa-btn__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="#fff"><path d="M12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2zm0 2a8 8 0 11-4.1 14.9l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 0112 4zm4.3 10.2c-.2-.1-1.3-.7-1.5-.8s-.4-.1-.5.1-.6.8-.8 1-.3.2-.5.1a6.5 6.5 0 01-1.9-1.2 7.2 7.2 0 01-1.3-1.7c-.1-.2 0-.4.1-.5l.4-.4.2-.4v-.4l-.7-1.7c-.2-.5-.4-.4-.5-.4h-.5a.9.9 0 00-.7.3A2.8 2.8 0 006 8.6c0 1.6 1.2 3.2 1.3 3.4s2.3 3.6 5.7 5c.8.3 1.4.5 1.9.7.8.2 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.3.2-1.5z"/></svg></span>' . esc_html__( 'Order on WhatsApp', 'tabarak-electronics-child' ) . '</a>';
    }
}
add_action( 'woocommerce_after_add_to_cart_button', 'tabarak_child_wa_after_cart' );


/* ============================================================
 * Tabarak round 6: filters, category hero, faster search, FAQ
 * ============================================================ */

if ( ! function_exists( 'tabarak_child_brand_tax' ) ) {
    function tabarak_child_brand_tax() {
        foreach ( array( 'product_brand', 'tabarak_brand', 'pwb-brand', 'yith_product_brand' ) as $t ) {
            if ( taxonomy_exists( $t ) ) {
                return $t;
            }
        }
        return '';
    }
}

/* (v1.9.6) Brand terms limited to a category (with in-category counts). */
if ( ! function_exists( 'tabarak_child_category_brand_terms' ) ) {
    function tabarak_child_category_brand_terms( $cat_id, $btax ) {
        $cat_id = (int) $cat_id;
        if ( $cat_id <= 0 || ! $btax ) {
            return array();
        }
        $key    = 'tabbrandcat_' . $cat_id . '_' . md5( (string) $btax );
        $cached = get_transient( $key );
        if ( is_array( $cached ) ) {
            return $cached;
        }
        $ids      = array( $cat_id );
        $children = get_term_children( $cat_id, 'product_cat' );
        if ( ! is_wp_error( $children ) && ! empty( $children ) ) {
            foreach ( $children as $c ) {
                $ids[] = (int) $c;
            }
        }
        $ids = array_map( 'intval', array_unique( $ids ) );
        $in  = implode( ',', $ids );
        global $wpdb;
        $sql = "SELECT t.term_id, t.name, t.slug, COUNT(DISTINCT p.ID) AS cnt
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} trc ON p.ID = trc.object_id
            INNER JOIN {$wpdb->term_taxonomy} ttc ON trc.term_taxonomy_id = ttc.term_taxonomy_id
            INNER JOIN {$wpdb->term_relationships} trb ON p.ID = trb.object_id
            INNER JOIN {$wpdb->term_taxonomy} ttb ON trb.term_taxonomy_id = ttb.term_taxonomy_id
            INNER JOIN {$wpdb->terms} t ON ttb.term_id = t.term_id
            WHERE p.post_type = 'product' AND p.post_status = 'publish'
              AND ttc.taxonomy = 'product_cat' AND ttc.term_id IN ($in)
              AND ttb.taxonomy = %s
            GROUP BY t.term_id, t.name, t.slug
            ORDER BY cnt DESC
            LIMIT 40";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $btax ) );
        $out  = array();
        if ( $rows ) {
            foreach ( $rows as $r ) {
                $o          = new stdClass();
                $o->term_id = (int) $r->term_id;
                $o->name    = $r->name;
                $o->slug    = $r->slug;
                $o->count   = (int) $r->cnt;
                $out[]      = $o;
            }
        }
        set_transient( $key, $out, HOUR_IN_SECONDS );
        return $out;
    }
}

/* Apply shop/category filters to the main query. */
if ( ! function_exists( 'tabarak_child_filter_query' ) ) {
    function tabarak_child_filter_query( $q ) {
        if ( is_admin() || ! $q->is_main_query() ) {
            return;
        }
        $ctx = ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );
        if ( ! $ctx ) {
            return;
        }
        $meta = (array) $q->get( 'meta_query' );
        $tax  = (array) $q->get( 'tax_query' );
        if ( isset( $_GET['tab_instock'] ) ) {
            $meta[] = array( 'key' => '_stock_status', 'value' => 'instock' );
        }
        $min = isset( $_GET['tab_min'] ) ? (float) $_GET['tab_min'] : 0;
        $max = isset( $_GET['tab_max'] ) ? (float) $_GET['tab_max'] : 0;
        if ( $min > 0 || $max > 0 ) {
            $price = array( 'key' => '_price', 'type' => 'NUMERIC' );
            if ( $min > 0 && $max > 0 ) {
                $price['value']   = array( $min, $max );
                $price['compare'] = 'BETWEEN';
            } elseif ( $min > 0 ) {
                $price['value']   = $min;
                $price['compare'] = '>=';
            } else {
                $price['value']   = $max;
                $price['compare'] = '<=';
            }
            $meta[] = $price;
        }
        if ( ! empty( $_GET['tab_brand'] ) ) {
            $btax = tabarak_child_brand_tax();
            if ( $btax ) {
                $brands = array_map( 'sanitize_title', (array) wp_unslash( $_GET['tab_brand'] ) );
                $tax[]  = array( 'taxonomy' => $btax, 'field' => 'slug', 'terms' => $brands );
            }
        }
        if ( ! empty( $_GET['tab_cat'] ) ) {
            $cats  = array_map( 'sanitize_title', (array) wp_unslash( $_GET['tab_cat'] ) );
            $tax[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $cats );
        }
        if ( $meta ) {
            $q->set( 'meta_query', $meta );
        }
        if ( $tax ) {
            $q->set( 'tax_query', $tax );
        }
    }
}
add_action( 'pre_get_posts', 'tabarak_child_filter_query' );

/* Filter toolbar + panel above the product loop. */
if ( ! function_exists( 'tabarak_child_filter_ui' ) ) {
    function tabarak_child_filter_ui() {
        $ctx = ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );
        if ( ! $ctx ) {
            return;
        }
        $btax   = tabarak_child_brand_tax();
        $brands = array();
        if ( $btax ) {
            $cur_cat = 0;
            if ( function_exists( 'is_product_category' ) && is_product_category() ) {
                $qo = get_queried_object();
                if ( $qo && ! is_wp_error( $qo ) ) {
                    $cur_cat = (int) $qo->term_id;
                }
            }
            if ( $cur_cat > 0 ) {
                $brands = tabarak_child_category_brand_terms( $cur_cat, $btax );
            } else {
                $brands = get_terms( array( 'taxonomy' => $btax, 'hide_empty' => true, 'number' => 40, 'orderby' => 'count', 'order' => 'DESC' ) );
            }
            if ( is_wp_error( $brands ) ) {
                $brands = array();
            }
        }
        $sel_brands = isset( $_GET['tab_brand'] ) ? array_map( 'sanitize_title', (array) wp_unslash( $_GET['tab_brand'] ) ) : array();
        $min     = isset( $_GET['tab_min'] ) ? (float) $_GET['tab_min'] : '';
        $max     = isset( $_GET['tab_max'] ) ? (float) $_GET['tab_max'] : '';
        $instock = isset( $_GET['tab_instock'] );
        $action  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
        if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
            $qo = get_queried_object();
            if ( $qo && ! is_wp_error( $qo ) ) {
                $action = get_term_link( $qo );
            }
        }
        ?>
        <div class="tabarak-filters" id="tabarak-filters">
            <button type="button" class="tabarak-filters__toggle" aria-expanded="false" aria-controls="tabarak-filters-panel">
                <span aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M3 5h18M6 12h12M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                <?php esc_html_e( 'Filters', 'tabarak-electronics-child' ); ?>
            </button>
            <form class="tabarak-filters__panel" id="tabarak-filters-panel" method="get" action="<?php echo esc_url( $action ); ?>" data-autosubmit="1">
                <?php if ( ! empty( $_GET['orderby'] ) ) : ?><input type="hidden" name="orderby" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) ); ?>" /><?php endif; ?>
                <div class="tabarak-filters__group">
                    <h4><?php esc_html_e( 'Availability', 'tabarak-electronics-child' ); ?></h4>
                    <label class="tabarak-filters__check"><input type="checkbox" name="tab_instock" value="1" <?php checked( $instock ); ?> /> <span><?php esc_html_e( 'In stock only', 'tabarak-electronics-child' ); ?></span></label>
                </div>
                <div class="tabarak-filters__group">
                    <h4><?php esc_html_e( 'Price (KSh)', 'tabarak-electronics-child' ); ?></h4>
                    <div class="tabarak-filters__price">
                        <input type="number" name="tab_min" inputmode="numeric" min="0" aria-label="Minimum price" placeholder="<?php esc_attr_e( 'Min', 'tabarak-electronics-child' ); ?>" value="<?php echo esc_attr( $min ); ?>" />
                        <span>&ndash;</span>
                        <input type="number" name="tab_max" inputmode="numeric" min="0" aria-label="Maximum price" placeholder="<?php esc_attr_e( 'Max', 'tabarak-electronics-child' ); ?>" value="<?php echo esc_attr( $max ); ?>" />
                    </div>
                </div>
                <?php if ( ! empty( $brands ) ) : ?>
                    <div class="tabarak-filters__group">
                        <h4><?php esc_html_e( 'Brand', 'tabarak-electronics-child' ); ?></h4>
                        <div class="tabarak-filters__scroll">
                            <?php foreach ( $brands as $b ) : ?>
                                <label class="tabarak-filters__check"><input type="checkbox" name="tab_brand[]" value="<?php echo esc_attr( $b->slug ); ?>" <?php checked( in_array( $b->slug, $sel_brands, true ) ); ?> /> <span><?php echo esc_html( $b->name ); ?></span> <span class="tabarak-filters__count"><?php echo esc_html( $b->count ); ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="tabarak-filters__actions">
                    <button type="submit" class="tabarak-btn tabarak-btn--primary"><?php esc_html_e( 'Apply', 'tabarak-electronics-child' ); ?></button>
                    <a class="tabarak-filters__clear" href="<?php echo esc_url( $action ); ?>"><?php esc_html_e( 'Clear', 'tabarak-electronics-child' ); ?></a>
                </div>
            </form>
            <script>
            (function(){var f=document.getElementById('tabarak-filters-panel');if(f&&f.getAttribute('data-autosubmit')){if(f.dataset.tabAutobound==='1'){return;}f.dataset.tabAutobound='1';var cs=f.querySelectorAll('input[type="checkbox"]');Array.prototype.forEach.call(cs,function(c){c.addEventListener('change',function(){f.classList.add('is-loading');if(typeof f.requestSubmit==='function'){f.requestSubmit();}else{f.submit();}});});}})();
            </script>
        </div>
        <?php
    }
}
add_action( 'woocommerce_before_shop_loop', 'tabarak_child_filter_ui', 5 );

/* Category / shop hero banner. */
if ( ! function_exists( 'tabarak_child_category_hero' ) ) {
    function tabarak_child_category_hero() {
        $ctx = ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );
        if ( ! $ctx ) {
            return;
        }
        $title = function_exists( 'woocommerce_page_title' ) ? woocommerce_page_title( false ) : get_the_title();
        $desc  = '';
        $count = 0;
        $bg    = '';
        if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
            $term = get_queried_object();
            if ( $term && ! is_wp_error( $term ) ) {
                $desc  = term_description( $term );
                $count = (int) $term->count;
                $tid   = get_term_meta( $term->term_id, 'thumbnail_id', true );
                if ( $tid ) {
                    $bg = wp_get_attachment_image_url( $tid, 'large' );
                }
            }
        } else {
            $counts = wp_count_posts( 'product' );
            $count  = isset( $counts->publish ) ? (int) $counts->publish : 0;
        }
        $style = $bg ? ' style="background-image:linear-gradient(90deg,rgba(14,17,22,.94),rgba(14,17,22,.6) 55%,rgba(14,17,22,.25)),url(' . "'" . esc_url( $bg ) . "'" . ');"' : '';
        echo '<section class="tabarak-cathero' . ( $bg ? ' has-bg' : '' ) . '"' . $style . '>';
        echo '<div class="tabarak-cathero__inner">';
        echo '<h1 class="tabarak-cathero__title">' . esc_html( $title ) . '</h1>';
        if ( $desc ) {
            echo '<div class="tabarak-cathero__desc">' . wp_kses_post( wpautop( $desc ) ) . '</div>';
        }
        echo '<ul class="tabarak-cathero__stats">';
        /* translators: %s: product count. */
        echo '<li><strong>' . esc_html( number_format_i18n( $count ) ) . '</strong> ' . esc_html( _n( 'product', 'products', $count, 'tabarak-electronics-child' ) ) . '</li>';
        echo '<li>' . esc_html__( 'Genuine & warranty-backed', 'tabarak-electronics-child' ) . '</li>';
        echo '<li>' . esc_html__( 'Nationwide delivery', 'tabarak-electronics-child' ) . '</li>';
        echo '</ul>';
        echo '</div></section>';
    }
}
add_action( 'woocommerce_before_main_content', 'tabarak_child_category_hero', 25 );

/* Hide the default WooCommerce archive title + description (hero shows them). */
add_filter( 'woocommerce_show_page_title', '__return_false' );
if ( ! function_exists( 'tabarak_child_strip_archive_desc' ) ) {
    function tabarak_child_strip_archive_desc() {
        remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
        remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
    }
}
add_action( 'init', 'tabarak_child_strip_archive_desc', 99 );

/* FAQ page body class so JS can build accordions. */
if ( ! function_exists( 'tabarak_child_body_class' ) ) {
    function tabarak_child_body_class( $classes ) {
        if ( is_page() ) {
            $slug = get_post_field( 'post_name', get_queried_object_id() );
            if ( in_array( $slug, array( 'faqs', 'faq', 'frequently-asked-questions' ), true ) ) {
                $classes[] = 'tabarak-faq-page';
            }
        }
        return $classes;
    }
}
add_filter( 'body_class', 'tabarak_child_body_class' );


/* ============================================================
 * Tabarak round 7 (v1.5.0): New-arrival badge, brand strip
 * ============================================================ */

/* A product counts as "new" when published within the last 30 days. */
if ( ! function_exists( 'tabarak_child_is_new' ) ) {
    function tabarak_child_is_new( $product_id ) {
        $published = get_post_time( 'U', true, $product_id );
        if ( ! $published ) {
            return false;
        }
        $age_days = ( time() - (int) $published ) / DAY_IN_SECONDS;
        return $age_days >= 0 && $age_days <= 30;
    }
}

/* Resolve a brand term's image id (our meta first, then native WooCommerce Brands). */
if ( ! function_exists( 'tabarak_child_brand_image_id' ) ) {
    function tabarak_child_brand_image_id( $term_id ) {
        $id = (int) get_term_meta( $term_id, 'tabarak_brand_image_id', true );
        if ( ! $id ) {
            $id = (int) get_term_meta( $term_id, 'thumbnail_id', true );
        }
        return $id;
    }
}

/* Homepage "Shop by brand" strip: logos scroll horizontally (6 desktop / 2 mobile). */
if ( ! function_exists( 'tabarak_child_brands_strip' ) ) {
    function tabarak_child_brands_strip( $limit = 24 ) {
        $btax = function_exists( 'tabarak_child_brand_tax' ) ? tabarak_child_brand_tax() : '';
        if ( ! $btax ) {
            return false;
        }
        $brands = function_exists( 'tabarak_child_ordered_brands' ) ? tabarak_child_ordered_brands( $btax, (int) $limit ) : array();
        if ( count( $brands ) === 0 ) {
            return false;
        }
        echo '<section class="tabarak-section tabarak-brands">';
        echo '<div class="tabarak-container">';
        echo '<header class="tabarak-section__head"><h2 class="tabarak-section__title">' . esc_html__( 'Shop by brand', 'tabarak-electronics-child' ) . '</h2></header>';
        echo '<div class="tabarak-brands__wrap">';
        echo '<button type="button" class="tabarak-brands__nav tabarak-brands__nav--prev" aria-label="' . esc_attr__( 'Scroll left', 'tabarak-electronics-child' ) . '">&lsaquo;</button>';
        echo '<ul class="tabarak-brands__track">';
        foreach ( $brands as $b ) {
            $img_id = tabarak_child_brand_image_id( $b->term_id );
            $link   = get_term_link( $b );
            if ( is_wp_error( $link ) ) {
                $link = '#';
            }
            echo '<li class="tabarak-brand">';
            echo '<a class="tabarak-brand__link" href="' . esc_url( $link ) . '" title="' . esc_attr( $b->name ) . '">';
            if ( $img_id ) {
                echo wp_get_attachment_image( $img_id, 'medium', false, array( 'loading' => 'lazy', 'alt' => esc_attr( $b->name ), 'class' => 'tabarak-brand__img' ) );
            } else {
                echo '<span class="tabarak-brand__name">' . esc_html( $b->name ) . '</span>';
            }
            echo '</a></li>';
        }
        echo '</ul>';
        echo '<button type="button" class="tabarak-brands__nav tabarak-brands__nav--next" aria-label="' . esc_attr__( 'Scroll right', 'tabarak-electronics-child' ) . '">&rsaquo;</button>';
        echo '</div></div></section>';
        return true;
    }
}


/* Branded fallback image for products/categories without a photo. */
if ( ! function_exists( 'tabarak_child_placeholder_img' ) ) {
    function tabarak_child_placeholder_img( $src ) {
        return get_stylesheet_directory_uri() . '/assets/images/no-image.png';
    }
}
add_filter( 'woocommerce_placeholder_img_src', 'tabarak_child_placeholder_img' );


/* (v1.9.6) Inner category-nav rail removed per request. */


/* ============================================================
 * Tabarak round 10 (v1.8.0): Recently viewed products
 * Client-side (localStorage) - no DB writes, fully cache-safe.
 * ============================================================ */
if ( ! function_exists( 'tabarak_child_current_product_payload' ) ) {
    function tabarak_child_current_product_payload() {
        if ( ! function_exists( 'is_product' ) || ! is_product() ) {
            return null;
        }
        global $product;
        $p = $product;
        if ( ! ( $p instanceof WC_Product ) ) {
            $p = wc_get_product( get_the_ID() );
        }
        if ( ! ( $p instanceof WC_Product ) ) {
            return null;
        }
        $img = wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' );
        if ( ! $img ) {
            $img = get_stylesheet_directory_uri() . '/assets/images/no-image.png';
        }
        return array(
            'id'    => (int) $p->get_id(),
            'title' => wp_strip_all_tags( $p->get_name() ),
            'url'   => get_permalink( $p->get_id() ),
            'img'   => $img,
            'price'   => wp_strip_all_tags( wc_price( (float) $p->get_price() ) ),
            'regular' => $p->is_on_sale() ? wp_strip_all_tags( wc_price( (float) $p->get_regular_price() ) ) : '',
        );
    }
}
if ( ! function_exists( 'tabarak_child_recent_container' ) ) {
    function tabarak_child_recent_container( $heading = '' ) {
        $heading = $heading ? $heading : __( 'Recently viewed', 'tabarak-electronics-child' );
        echo '<section class="tabarak-section tabarak-recent" id="tabarak-recent" hidden aria-label="' . esc_attr( $heading ) . '">';
        echo '<div class="tabarak-container">';
        echo '<header class="tabarak-section__head"><h2 class="tabarak-section__title">' . esc_html( $heading ) . '</h2></header>';
        echo '<ul class="tabarak-carousel tabarak-recent__track" id="tabarak-recent-track"></ul>';
        echo '</div></section>';
    }
}
add_action( 'woocommerce_after_single_product', 'tabarak_child_recent_container', 15 );


if ( ! function_exists( 'tabarak_child_mobile_overrides' ) ) {
    /**
     * Critical mobile CSS printed inline in the page head so it is delivered
     * even when the external stylesheet is served from a stale CDN/browser cache.
     * Uses !important to win over WooCommerce small-screen defaults.
     */
    function tabarak_child_mobile_overrides() {
        return '/* Tabarak v1.10.1 critical mobile overrides + cache-proof above-the-fold layout skeleton */.tabarak-topbar__inner{display:flex;justify-content:space-between;align-items:center;gap:10px 18px;min-height:38px;flex-wrap:wrap;}.site-header__inner{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:18px;padding-block:12px;}.tabarak-hero2__grid{display:grid;grid-template-columns:262px minmax(0,1fr);gap:18px;align-items:stretch;}.tabarak-slider{position:relative;overflow:hidden;background:#0e1116;}.tabarak-slider__viewport{position:relative;width:100%;aspect-ratio:16/7;}.tabarak-slider__track{position:absolute;inset:0;}.tabarak-slide{position:absolute;inset:0;}.tabarak-slide__bg{position:absolute;inset:0;background-size:cover;background-position:center;}.tabarak-services__grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;padding-block:22px;}.tabarak-category-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:14px;}.tabarak-category-card__media{width:100%;aspect-ratio:1/1;}.tabarak-pcard__media{position:relative;display:block;aspect-ratio:1/1;}@media (max-width:991px){.site-header__inner{grid-template-columns:auto 1fr auto;grid-template-areas:"brand brand actions" "search search search";gap:12px;}.tabarak-hero2__grid{grid-template-columns:1fr;}.tabarak-slider__viewport{aspect-ratio:3/2;}.tabarak-services__grid{grid-template-columns:repeat(2,1fr);gap:16px;}}@media (max-width:767px){.tabarak-slider__viewport{aspect-ratio:4/3;}.tabarak-services__grid{grid-template-columns:1fr;gap:12px;padding-block:16px;}.tabarak-category-grid{grid-template-columns:repeat(3,1fr);gap:10px;}.tabarak-carousel{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;overflow:visible;padding:2px;}}@media (max-width:420px){.tabarak-category-grid{grid-template-columns:repeat(2,1fr);}}@media (max-width:767px){.header-action--account{display:inline-flex !important;}.header-action--cart{display:none !important;}.woocommerce ul.products,.woocommerce-page ul.products{display:grid !important;grid-template-columns:repeat(2,minmax(0,1fr)) !important;gap:12px !important;}.woocommerce ul.products li.product,.woocommerce-page ul.products li.product{width:auto !important;max-width:100% !important;min-width:0 !important;margin:0 !important;float:none !important;display:flex !important;flex-direction:column !important;}.woocommerce ul.products li.product a img{width:100% !important;height:auto !important;aspect-ratio:1/1 !important;object-fit:contain !important;}.woocommerce ul.products li.product .woocommerce-loop-product__title,.tabarak-pcard__title{display:-webkit-box !important;-webkit-line-clamp:2 !important;-webkit-box-orient:vertical !important;overflow:hidden !important;line-height:1.4 !important;min-height:2.9em !important;margin:0 0 8px !important;padding-bottom:3px !important;word-break:break-word !important;}.woocommerce ul.products li.product .price,.tabarak-pcard__price{margin-top:auto !important;}}';
    }
}


/* =====================================================================
 * Tabarak v1.10.1 - performance trim + PHP-only security hardening
 * (Security response HEADERS and long-term asset caching live in the
 *  security/root-htaccess-block.txt file - applied at server level -
 *  so they are intentionally NOT duplicated here.)
 * ===================================================================== */
if ( function_exists( 'tabarak_child_perf_security' ) === false ) {

    /* --- PERF 1: drop Contact Form 7 + Google reCAPTCHA on pages with no form --- */
    add_action( 'wp_enqueue_scripts', function () {
        $strip = is_front_page();
        if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product() ) ) {
            $strip = true;
        }
        if ( $strip === true ) {
            foreach ( array( 'google-recaptcha', 'wpcf7-recaptcha', 'wpcf7-recaptcha-controls', 'contact-form-7', 'swv' ) as $h ) {
                wp_dequeue_script( $h );
                wp_deregister_script( $h );
            }
            wp_dequeue_style( 'contact-form-7' );
        }
    }, 100 );

    /* --- PERF 2: remove legacy jquery-migrate on the front end --- */
    add_action( 'wp_default_scripts', function ( $scripts ) {
        if ( is_admin() ) { return; }
        if ( isset( $scripts->registered['jquery'] ) ) {
            $deps = $scripts->registered['jquery']->deps;
            if ( is_array( $deps ) ) {
                $scripts->registered['jquery']->deps = array_diff( $deps, array( 'jquery-migrate' ) );
            }
        }
    } );

    /* --- PERF 3: disable the emoji detection script/styles site-wide --- */
    add_action( 'init', function () {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
        remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
        remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    } );

    /* --- PERF 4: drop unused Gutenberg + WooCommerce Blocks CSS on non-block templates --- */
    add_action( 'wp_enqueue_scripts', function () {
        $strip = is_front_page();
        if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product() ) ) {
            $strip = true;
        }
        if ( $strip === true ) {
            foreach ( array( 'wp-block-library', 'wp-block-library-theme', 'wc-blocks-style', 'wc-blocks-packages-style', 'classic-theme-styles', 'global-styles' ) as $h ) {
                wp_dequeue_style( $h );
            }
        }
    }, 100 );

    /* --- PERF 5: preload the first hero slide image so the LCP is discoverable immediately --- */
    add_action( 'wp_head', function () {
        if ( is_front_page() === false ) { return; }
        if ( function_exists( 'tabarak_child_hero_slides' ) === false ) { return; }
        $slides = tabarak_child_hero_slides();
        if ( empty( $slides ) || empty( $slides[0]['img'] ) ) { return; }
        echo '<link rel="preload" as="image" fetchpriority="high" href="' . esc_url( $slides[0]['img'] ) . '">';
    }, 1 );

    /* --- SEC 1: stop leaking WordPress version / editor discovery links --- */
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
    add_filter( 'the_generator', '__return_empty_string' );

    /* --- SEC 2: disable XML-RPC and pingbacks (brute-force / DDoS vector) --- */
    add_filter( 'xmlrpc_enabled', '__return_false' );
    add_filter( 'wp_headers', function ( $headers ) {
        if ( isset( $headers['X-Pingback'] ) ) { unset( $headers['X-Pingback'] ); }
        return $headers;
    } );
    add_filter( 'xmlrpc_methods', function ( $methods ) {
        unset( $methods['pingback.ping'] );
        unset( $methods['pingback.extensions.getPingbacks'] );
        return $methods;
    } );

    /* --- SEC 3: block REST API username enumeration for anonymous visitors --- */
    add_filter( 'rest_endpoints', function ( $endpoints ) {
        if ( is_user_logged_in() === false ) {
            foreach ( $endpoints as $route => $handler ) {
                if ( strpos( $route, '/wp/v2/users' ) === 0 ) {
                    unset( $endpoints[ $route ] );
                }
            }
        }
        return $endpoints;
    } );

    /* --- SEC 4: turn off the built-in theme/plugin file editor in wp-admin --- */
    if ( defined( 'DISALLOW_FILE_EDIT' ) === false ) {
        define( 'DISALLOW_FILE_EDIT', true );
    }

    /* marker function so this block only ever registers once */
    function tabarak_child_perf_security() { return true; }
}


/* ============================================================
 * Tabarak v1.9.9 - origin CPU reducers
 * Cuts admin-ajax load that pegs CPU on uncached shop/product pages:
 *  - WooCommerce cart fragments only where a live cart total is needed
 *  - WP Heartbeat off on the front end, slowed in admin
 * ============================================================ */
if ( function_exists( 'tabarak_child_perf_cpu' ) === false ) {
    function tabarak_child_perf_cpu() {
        if ( function_exists( 'is_cart' ) === false ) {
            return;
        }
        if ( is_cart() === false && is_checkout() === false ) {
            wp_dequeue_script( 'wc-cart-fragments' );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'tabarak_child_perf_cpu', 20 );

if ( function_exists( 'tabarak_child_frontend_heartbeat' ) === false ) {
    function tabarak_child_frontend_heartbeat() {
        if ( is_admin() === false ) {
            wp_deregister_script( 'heartbeat' );
        }
    }
}
add_action( 'init', 'tabarak_child_frontend_heartbeat', 1 );

if ( function_exists( 'tabarak_child_heartbeat_interval' ) === false ) {
    function tabarak_child_heartbeat_interval( $settings ) {
        $settings['interval'] = 60;
        return $settings;
    }
}
add_filter( 'heartbeat_settings', 'tabarak_child_heartbeat_interval' );


/* ============================================================
 * Tabarak v1.10.0 - admin-controlled homepage (Customizer)
 *  - Left category rail + Shop-by-category tiles
 *  - Homepage product rows: category + preferred brands, random
 *  - Shop-by-brand featured-first order
 * Randomization uses a cached ID pool + PHP shuffle (no ORDER BY RAND).
 * ============================================================ */

/* Split a textarea / comma list into clean tokens. */
if ( function_exists( 'tabarak_child_tokens' ) === false ) {
    function tabarak_child_tokens( $raw ) {
        $raw = (string) $raw;
        $raw = str_replace( array( "\r\n", "\r", "\n" ), ',', $raw );
        $out = array();
        foreach ( explode( ',', $raw ) as $t ) {
            $t = trim( $t );
            if ( strlen( $t ) > 0 ) {
                $out[] = $t;
            }
        }
        return $out;
    }
}

/* Resolve category tokens (name OR slug) to ordered, de-duped term objects. */
if ( function_exists( 'tabarak_child_cat_terms_from' ) === false ) {
    function tabarak_child_cat_terms_from( $raw ) {
        $terms = array();
        $seen  = array();
        foreach ( tabarak_child_tokens( $raw ) as $tok ) {
            $term = get_term_by( 'slug', sanitize_title( $tok ), 'product_cat' );
            if ( ( $term instanceof WP_Term ) === false ) {
                $term = get_term_by( 'name', $tok, 'product_cat' );
            }
            if ( $term instanceof WP_Term && isset( $seen[ $term->term_id ] ) === false ) {
                $seen[ $term->term_id ] = 1;
                $terms[]                = $term;
            }
        }
        return $terms;
    }
}

/* Resolve brand tokens (name OR slug) to ordered brand slugs. */
if ( function_exists( 'tabarak_child_brand_slugs_from' ) === false ) {
    function tabarak_child_brand_slugs_from( $raw, $btax ) {
        $slugs = array();
        if ( strlen( (string) $btax ) === 0 ) {
            return $slugs;
        }
        foreach ( tabarak_child_tokens( $raw ) as $tok ) {
            $term = get_term_by( 'slug', sanitize_title( $tok ), $btax );
            if ( ( $term instanceof WP_Term ) === false ) {
                $term = get_term_by( 'name', $tok, $btax );
            }
            if ( $term instanceof WP_Term ) {
                $slugs[ $term->slug ] = $term->slug;
            }
        }
        return array_values( $slugs );
    }
}

/* Efficient random product IDs for a category (+ optional brand bias).
 * Caches candidate ID pools for 15 min and shuffles in PHP. No ORDER BY RAND. */
if ( function_exists( 'tabarak_child_random_product_ids' ) === false ) {
    function tabarak_child_random_product_ids( $cat_ids, $brand_slugs, $limit, $btax ) {
        $limit   = (int) $limit;
        $cat_tax = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => array_map( 'intval', (array) $cat_ids ),
            'include_children' => true,
        );
        $key  = 'tabarak_pool_' . md5( wp_json_encode( array( $cat_ids, $brand_slugs, $btax ) ) );
        $pool = get_transient( $key );
        if ( is_array( $pool ) === false ) {
            $pref = array();
            if ( count( (array) $brand_slugs ) > 0 && strlen( (string) $btax ) > 0 ) {
                $qp   = new WP_Query( array(
                    'post_type'           => 'product',
                    'post_status'         => 'publish',
                    'fields'              => 'ids',
                    'posts_per_page'      => 80,
                    'no_found_rows'       => true,
                    'ignore_sticky_posts' => true,
                    'tax_query'           => array(
                        'relation' => 'AND',
                        $cat_tax,
                        array( 'taxonomy' => $btax, 'field' => 'slug', 'terms' => $brand_slugs ),
                    ),
                ) );
                $pref = array_map( 'intval', $qp->posts );
            }
            $qr   = new WP_Query( array(
                'post_type'           => 'product',
                'post_status'         => 'publish',
                'fields'              => 'ids',
                'posts_per_page'      => 80,
                'no_found_rows'       => true,
                'ignore_sticky_posts' => true,
                'tax_query'           => array( $cat_tax ),
            ) );
            $rest = array_map( 'intval', $qr->posts );
            $pool = array( 'pref' => $pref, 'rest' => $rest );
            set_transient( $key, $pool, 15 * MINUTE_IN_SECONDS );
        }
        $pref = isset( $pool['pref'] ) ? $pool['pref'] : array();
        $rest = isset( $pool['rest'] ) ? $pool['rest'] : array();
        shuffle( $pref );
        shuffle( $rest );
        $picked = array();
        foreach ( array_merge( $pref, $rest ) as $id ) {
            $picked[ $id ] = (int) $id;
            if ( count( $picked ) >= $limit ) {
                break;
            }
        }
        return array_values( $picked );
    }
}

/* Left hero category rail terms: admin list, else all categories by popularity. */
if ( function_exists( 'tabarak_child_rail_terms' ) === false ) {
    function tabarak_child_rail_terms() {
        $terms = tabarak_child_cat_terms_from( get_theme_mod( 'tabarak_home_rail_cats', '' ) );
        if ( count( $terms ) > 0 ) {
            return $terms;
        }
        $all = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'count',
            'order'      => 'DESC',
            'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
        ) );
        return is_wp_error( $all ) ? array() : $all;
    }
}

/* Shop-by-category tile terms: admin list, else top of the fallback by popularity. */
if ( function_exists( 'tabarak_child_tile_terms' ) === false ) {
    function tabarak_child_tile_terms( $fallback ) {
        $terms = tabarak_child_cat_terms_from( get_theme_mod( 'tabarak_home_tile_cats', '' ) );
        if ( count( $terms ) > 0 ) {
            return array_slice( $terms, 0, 12 );
        }
        return array_slice( (array) $fallback, 0, 12 );
    }
}

/* Ordered brand terms for Shop-by-brand: admin featured first, then rest by popularity. */
if ( function_exists( 'tabarak_child_ordered_brands' ) === false ) {
    function tabarak_child_ordered_brands( $btax, $limit ) {
        $ordered = array();
        $seen    = array();
        foreach ( tabarak_child_tokens( get_theme_mod( 'tabarak_home_featured_brands', '' ) ) as $tok ) {
            $term = get_term_by( 'slug', sanitize_title( $tok ), $btax );
            if ( ( $term instanceof WP_Term ) === false ) {
                $term = get_term_by( 'name', $tok, $btax );
            }
            if ( $term instanceof WP_Term && isset( $seen[ $term->term_id ] ) === false ) {
                $seen[ $term->term_id ] = 1;
                $ordered[]              = $term;
            }
        }
        $rest = get_terms( array(
            'taxonomy'   => $btax,
            'hide_empty' => true,
            'number'     => (int) $limit,
            'orderby'    => 'count',
            'order'      => 'DESC',
        ) );
        if ( is_wp_error( $rest ) === false ) {
            foreach ( (array) $rest as $b ) {
                if ( isset( $seen[ $b->term_id ] ) === false ) {
                    $seen[ $b->term_id ] = 1;
                    $ordered[]           = $b;
                }
            }
        }
        return array_slice( $ordered, 0, (int) $limit );
    }
}

/* Cached wrapper: serve the homepage rows from a short-lived transient so the
 * build collapses to one read between refresh windows (big speed win on cache-miss). */
if ( function_exists( 'tabarak_child_home_rows' ) === false ) {
    function tabarak_child_home_rows() {
        $can_cache = ( is_user_logged_in() === false );
        $ckey      = 'tabarak_home_rows_html';
        if ( $can_cache ) {
            $cached = get_transient( $ckey );
            if ( is_string( $cached ) && strlen( $cached ) > 0 ) {
                echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                return;
            }
        }
        ob_start();
        tabarak_child_render_home_rows();
        $html = ob_get_clean();
        if ( $can_cache ) {
            set_transient( $ckey, $html, 10 * MINUTE_IN_SECONDS );
        }
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

/* Build homepage product rows from the Customizer config, else the default set. */
if ( function_exists( 'tabarak_child_render_home_rows' ) === false ) {
    function tabarak_child_render_home_rows() {
        $btax = function_exists( 'tabarak_child_brand_tax' ) ? tabarak_child_brand_tax() : '';
        $conf = trim( (string) get_theme_mod( 'tabarak_home_sections', '' ) );
        if ( strlen( $conf ) === 0 ) {
            if ( function_exists( 'tabarak_child_category_groups' ) ) {
                foreach ( tabarak_child_category_groups() as $label => $names ) {
                    tabarak_child_group_carousel( $label, $names, 12 );
                }
            }
            return;
        }
        $lines = preg_split( '/\r\n|\r|\n/', $conf );
        foreach ( (array) $lines as $line ) {
            $line = trim( $line );
            if ( strlen( $line ) === 0 ) {
                continue;
            }
            $parts     = explode( '|', $line );
            $cat_terms = tabarak_child_cat_terms_from( $parts[0] );
            if ( count( $cat_terms ) === 0 ) {
                continue;
            }
            $term      = $cat_terms[0];
            $brand_raw = isset( $parts[1] ) ? $parts[1] : '';
            $brands    = tabarak_child_brand_slugs_from( $brand_raw, $btax );
            $ids       = tabarak_child_random_product_ids( array( $term->term_id ), $brands, 12, $btax );
            if ( count( $ids ) === 0 ) {
                continue;
            }
            $view_all = get_term_link( $term );
            if ( is_wp_error( $view_all ) ) {
                $view_all = '';
            }
            tabarak_child_carousel(
                array(
                    'post__in'       => $ids,
                    'orderby'        => 'post__in',
                    'posts_per_page' => 12,
                ),
                $term->name,
                $view_all
            );
        }
    }
}

/* Customizer section: homepage layout controls. */
if ( function_exists( 'tabarak_child_home_customize' ) === false ) {
    function tabarak_child_home_customize( $wp_customize ) {
        $wp_customize->add_section( 'tabarak_home', array(
            'title'    => __( 'Tabarak: Homepage sections', 'tabarak-electronics-child' ),
            'priority' => 32,
        ) );
        $fields = array(
            'tabarak_home_rail_cats'       => __( 'Left category rail - categories (one per line, name or slug). Blank = all by popularity.', 'tabarak-electronics-child' ),
            'tabarak_home_tile_cats'       => __( 'Shop by category tiles - categories (one per line). Blank = top by popularity.', 'tabarak-electronics-child' ),
            'tabarak_home_sections'        => __( 'Homepage product rows - one per line: Category | brand, brand. Products pull at random; add brands after the vertical bar to feature them. Blank = default set.', 'tabarak-electronics-child' ),
            'tabarak_home_featured_brands' => __( 'Shop by brand - featured first (one per line, name or slug). The rest follow by popularity.', 'tabarak-electronics-child' ),
        );
        foreach ( $fields as $id => $label ) {
            $wp_customize->add_setting( $id, array(
                'default'           => '',
                'sanitize_callback' => 'sanitize_textarea_field',
            ) );
            $wp_customize->add_control( $id, array(
                'label'   => $label,
                'section' => 'tabarak_home',
                'type'    => 'textarea',
            ) );
        }
    }
}
add_action( 'customize_register', 'tabarak_child_home_customize' );


/* Clear the cached homepage rows the moment homepage settings are saved. */
if ( function_exists( 'tabarak_child_flush_home_rows' ) === false ) {
    function tabarak_child_flush_home_rows() {
        delete_transient( 'tabarak_home_rows_html' );
    }
}
add_action( 'customize_save_after', 'tabarak_child_flush_home_rows' );

/* v1.11.0 UX layer: mega navigation, page templates, product page, cart and account polish. */
require_once get_stylesheet_directory() . '/inc/ux.php';
