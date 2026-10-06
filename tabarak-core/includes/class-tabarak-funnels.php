<?php
/**
 * Tabarak Core - Category sales funnels (landing pages).
 *
 * SAFETY: read-only for products and categories. Funnel settings are stored
 * in the single option "tabarak_funnels". No posts, pages or terms are created.
 *
 * URLs:   /lp-televisions/, /lp-refrigerators/ ... (13 funnels)
 * Admin:  Tabarak > Sales Funnels (pick products, headline, image, budgets, FAQs)
 *
 * @package Tabarak_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Tabarak_Funnels' ) ) :

final class Tabarak_Funnels {

	const OPTION     = 'tabarak_funnels';
	const RW_VERSION = '1';
	const QUERY_VAR  = 'tabarak_lp';

	/** @var string|null Current funnel key while rendering. */
	private static $current = null;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite' ) );
		add_action( 'init', array( __CLASS__, 'register_leads' ) );
		add_action( 'wp_ajax_tabarak_funnel_lead', array( __CLASS__, 'ajax_lead' ) );
		add_action( 'wp_ajax_nopriv_tabarak_funnel_lead', array( __CLASS__, 'ajax_lead' ) );
		add_action( 'add_meta_boxes_tabarak_lead', array( __CLASS__, 'lead_metabox' ) );
		add_action( 'save_post_tabarak_lead', array( __CLASS__, 'lead_save' ) );
		add_filter( 'manage_tabarak_lead_posts_columns', array( __CLASS__, 'lead_columns' ) );
		add_action( 'manage_tabarak_lead_posts_custom_column', array( __CLASS__, 'lead_column' ), 10, 2 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'detect' ), 1 );
		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 40 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'sitewide_assets' ), 41 );
		add_action( 'wp_footer', array( __CLASS__, 'sitewide_modal' ), 20 );
		add_filter( 'style_loader_tag', array( __CLASS__, 'async_style' ), 10, 2 );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'doc_title' ), 50 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'wp_head', array( __CLASS__, 'head_meta' ), 2 );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'admin_post_tabarak_save_funnel', array( __CLASS__, 'save' ) );
		foreach ( array( 'woocommerce_update_product', 'woocommerce_product_set_stock_status', 'edited_product_cat' ) as $h ) {
			add_action( $h, array( __CLASS__, 'flush_cache' ) );
		}
	}

	/* ------------------------------------------------------------------
	 * Funnel definitions (defaults). Admin settings override these.
	 * ------------------------------------------------------------------ */
	public static function definitions() {
		$d = array(
			'televisions' => array(
				'label' => 'Televisions', 'cats' => array( 'televisions', 'Televisions', 'TVs' ),
				'headline' => 'Smart TVs at the best prices in Kenya',
				'sub' => 'Samsung, LG, Hisense, TCL and Sony. Genuine, with warranty, delivered and mounted in Nairobi.',
				'bands' => "Under 25,000|0|25000\n25,000 - 50,000|25000|50000\n50,000 - 100,000|50000|100000\nAbove 100,000|100000|0",
				'faqs' => "Do you mount the TV on the wall?|Yes. We can deliver and wall-mount your TV in Nairobi. Ask our team for the mounting price when you order.\nAre these TVs original?|Yes. Every TV is genuine, brand new and comes with the manufacturer warranty.",
			),
			'refrigerators' => array(
				'label' => 'Refrigerators', 'cats' => array( 'refrigerators', 'Refrigerators', 'Fridges' ),
				'headline' => 'Fridges that keep your food fresh for less',
				'sub' => 'Single door, double door and side-by-side fridges from LG, Samsung, Hisense, Von and Mika.',
				'bands' => "Under 30,000|0|30000\n30,000 - 60,000|30000|60000\n60,000 - 120,000|60000|120000\nAbove 120,000|120000|0",
				'faqs' => "Which fridge size do I need?|For 1-2 people, 90-180 litres is enough. A family of 4-5 is best served by 250-400 litres. Tell us your family size and we will recommend one.\nDo your fridges save power?|Many models have inverter compressors that use much less electricity. Look for \"inverter\" in the product name.",
			),
			'cookers' => array(
				'label' => 'Cookers & Ovens', 'cats' => array( 'cookers-ovens', 'Cookers & Ovens', 'Cookers' ),
				'headline' => 'Cookers and ovens for every Kenyan kitchen',
				'sub' => 'Gas, electric and combination cookers, plus built-in ovens and hobs, with installation available.',
				'bands' => "Under 20,000|0|20000\n20,000 - 50,000|20000|50000\n50,000 - 100,000|50000|100000\nAbove 100,000|100000|0",
				'faqs' => "Do you install gas cookers?|Yes. We can deliver and connect your cooker in Nairobi. Ask about installation when you order.\nGas, electric or both?|A 3 gas + 1 electric cooker gives you a backup when gas runs out or power goes off. It is the most popular choice in Kenya.",
			),
			'washing' => array(
				'label' => 'Washing Machines & Dryers', 'cats' => array( 'washing-machines-dryers', 'Washing Machines & Dryers', 'Washing Machines' ),
				'headline' => 'Washing machines that do the laundry for you',
				'sub' => 'Front load, top load and twin tub washers and dryers from LG, Samsung, Beko and Hisense.',
				'bands' => "Under 30,000|0|30000\n30,000 - 60,000|30000|60000\n60,000 - 100,000|60000|100000\nAbove 100,000|100000|0",
				'faqs' => "Front load or top load?|Front loaders use less water and are gentler on clothes. Top loaders are cheaper and easier to load. Twin tubs are the most affordable.\nWhat capacity do I need?|7 kg suits 2-3 people, 8-10 kg suits a family of 4-6.",
			),
			'cookware' => array(
				'label' => 'Cookware & Bakeware', 'cats' => array( 'cookware-bakeware', 'Cookware & Bakeware', 'Cookware' ),
				'headline' => 'Quality cookware and bakeware sets',
				'sub' => 'Non-stick pans, pots and baking sets from Tefal and other trusted brands.',
				'bands' => "Under 3,000|0|3000\n3,000 - 8,000|3000|8000\n8,000 - 20,000|8000|20000\nAbove 20,000|20000|0",
				'faqs' => "Are the pans non-stick?|Most sets are non-stick. The product name and photos show the coating type.",
			),
			'blenders' => array(
				'label' => 'Blenders', 'cats' => array( 'blenders', 'Blenders' ),
				'headline' => 'Powerful blenders for juices, smoothies and more',
				'sub' => 'Kitchen blenders, personal blenders and grinders from Ramtons, Von, Kenwood and Mika.',
				'bands' => "Under 4,000|0|4000\n4,000 - 8,000|4000|8000\n8,000 - 15,000|8000|15000\nAbove 15,000|15000|0",
				'faqs' => "Can it crush ice?|Choose a blender with 500W or more for ice and frozen fruit. Ask us and we will recommend one.",
			),
			'microwaves' => array(
				'label' => 'Microwaves', 'cats' => array( 'microwaves', 'Microwaves' ),
				'headline' => 'Microwaves that heat, grill and cook in minutes',
				'sub' => 'Solo, grill and built-in microwaves from Samsung, LG, Bosch, Beko and Mika.',
				'bands' => "Under 10,000|0|10000\n10,000 - 20,000|10000|20000\n20,000 - 40,000|20000|40000\nAbove 40,000|40000|0",
				'faqs' => "What size microwave should I buy?|20 litres suits most homes. Choose 25-30 litres if you cook for a big family or want a grill.",
			),
			'irons' => array(
				'label' => 'Irons & Garment Care', 'cats' => array( 'irons-garment-care', 'Irons & Garment Care', 'Irons' ),
				'headline' => 'Irons and steamers for crisp clothes every day',
				'sub' => 'Dry irons, steam irons and garment steamers from Philips, Tefal, Ramtons and more.',
				'bands' => "Under 2,000|0|2000\n2,000 - 5,000|2000|5000\n5,000 - 10,000|5000|10000\nAbove 10,000|10000|0",
				'faqs' => "Steam iron or garment steamer?|A steam iron gives sharp creases. A garment steamer is faster for suits, dresses and curtains.",
			),
			'kettles' => array(
				'label' => 'Kettles', 'cats' => array( 'kettles', 'Kettles' ),
				'headline' => 'Electric kettles that boil fast and last',
				'sub' => 'Stainless steel, glass and cordless kettles from Ramtons, Von, Mika and more.',
				'bands' => "Under 2,000|0|2000\n2,000 - 4,000|2000|4000\n4,000 - 8,000|4000|8000\nAbove 8,000|8000|0",
				'faqs' => "Do the kettles switch off automatically?|Yes. Our electric kettles have automatic shut-off and boil-dry protection.",
			),
			'dispensers' => array(
				'label' => 'Water Dispensers', 'cats' => array( 'water-dispensers', 'Water Dispensers' ),
				'headline' => 'Hot and cold water dispensers for home and office',
				'sub' => 'Top load and bottom load dispensers with hot, cold and normal water.',
				'bands' => "Under 10,000|0|10000\n10,000 - 20,000|10000|20000\n20,000 - 35,000|20000|35000\nAbove 35,000|35000|0",
				'faqs' => "Top load or bottom load?|Bottom load dispensers hide the bottle in the cabinet, so you do not lift it. Top load models are cheaper.",
			),
			'heaters' => array(
				'label' => 'Heaters', 'cats' => array( 'heaters', 'Heaters' ),
				'headline' => 'Stay warm with efficient room heaters',
				'sub' => 'Oil-filled, fan and infrared heaters for cold Nairobi nights.',
				'bands' => "Under 4,000|0|4000\n4,000 - 8,000|4000|8000\n8,000 - 15,000|8000|15000\nAbove 15,000|15000|0",
				'faqs' => "Which heater is safest for bedrooms?|Oil-filled heaters are quiet, have no exposed element and are the best choice for bedrooms and children's rooms.",
			),
			'hoods' => array(
				'label' => 'Cooker Hoods & Extractors', 'cats' => array( 'cooker-hoods-extractors', 'Cooker Hoods & Extractors', 'Cooker Hoods' ),
				'headline' => 'Cooker hoods for a fresh, smoke-free kitchen',
				'sub' => 'Chimney, slim and island hoods that remove smoke, steam and cooking smells.',
				'bands' => "Under 15,000|0|15000\n15,000 - 30,000|15000|30000\n30,000 - 60,000|30000|60000\nAbove 60,000|60000|0",
				'faqs' => "Do you install cooker hoods?|Yes. We can deliver and install your hood in Nairobi. Ask our team for the installation price.\nWhat width do I need?|Match the hood to your cooker: a 60 cm cooker needs a 60 cm hood, a 90 cm cooker needs a 90 cm hood.",
			),
			'audio' => array(
				'label' => 'Audio & Home Theatre', 'cats' => array( 'audio-home-theatre', 'Audio & Home Theatre', 'Audio' ),
				'headline' => 'Big sound for movies, music and parties',
				'sub' => 'Soundbars, home theatres, party speakers and hi-fi systems from JBL, Sony, LG and Samsung.',
				'bands' => "Under 10,000|0|10000\n10,000 - 30,000|10000|30000\n30,000 - 70,000|30000|70000\nAbove 70,000|70000|0",
				'faqs' => "Will a soundbar work with my TV?|Most soundbars connect by HDMI, optical cable or Bluetooth and work with any modern TV.",
			),
		);
		$floors = array(
			'televisions'   => array( 8000, 'remote, mount, bracket, cable, stand, antenna, decoder' ),
			'refrigerators' => array( 10000, 'guard, stabilizer, filter' ),
			'cookers'       => array( 8000, 'regulator, hose, burner cap, knob' ),
			'washing'       => array( 10000, 'stand, cover, hose' ),
			'cookware'      => array( 500, '' ),
			'blenders'      => array( 1500, 'jar only, blade' ),
			'microwaves'    => array( 5000, 'plate, cover' ),
			'irons'         => array( 800, '' ),
			'kettles'       => array( 800, '' ),
			'dispensers'    => array( 5000, 'bottle, tap' ),
			'heaters'       => array( 1500, '' ),
			'hoods'         => array( 5000, 'filter, duct' ),
			'audio'         => array( 1500, 'cable, remote, mount' ),
		);
		foreach ( $floors as $k => $f ) {
			if ( isset( $d[ $k ] ) ) {
				$d[ $k ]['min_price'] = $f[0];
				$d[ $k ]['exclude']   = $f[1];
			}
		}
		return apply_filters( 'tabarak_funnel_definitions', $d );
	}

	/** Funnel config = defaults merged with saved settings. */
	public static function get( $key ) {
		$defs = self::definitions();
		if ( ! isset( $defs[ $key ] ) ) {
			return null;
		}
		$saved = get_option( self::OPTION, array() );
		$saved = ( is_array( $saved ) && isset( $saved[ $key ] ) && is_array( $saved[ $key ] ) ) ? $saved[ $key ] : array();
		$base  = wp_parse_args(
			$defs[ $key ],
			array( 'enabled' => 1, 'products' => array(), 'limit' => 12, 'autofill' => 1, 'image' => '', 'promo' => '', 'badge' => '', 'min_price' => 0, 'exclude' => '' )
		);
		foreach ( $saved as $k => $v ) {
			if ( '' !== $v && null !== $v ) {
				$base[ $k ] = $v;
			} elseif ( in_array( $k, array( 'image', 'promo', 'badge', 'products', 'enabled', 'autofill' ), true ) ) {
				$base[ $k ] = $v;
			}
		}
		$base['key'] = $key;
		return $base;
	}

	public static function slug( $key ) {
		return 'lp-' . sanitize_title( $key );
	}

	public static function url( $key ) {
		return home_url( '/' . self::slug( $key ) . '/' );
	}

	/* ------------------------------------------------------------------
	 * Routing
	 * ------------------------------------------------------------------ */
	public static function add_rewrite() {
		add_rewrite_rule( '^lp-([a-z0-9-]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
		if ( get_option( 'tabarak_funnels_rw' ) !== self::RW_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'tabarak_funnels_rw', self::RW_VERSION );
		}
	}

	public static function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/** Map a URL slug ("televisions") to a funnel key. */
	private static function key_from_slug( $slug ) {
		foreach ( array_keys( self::definitions() ) as $key ) {
			if ( sanitize_title( $key ) === $slug ) {
				return $key;
			}
		}
		return null;
	}

	public static function detect() {
		$slug = get_query_var( self::QUERY_VAR );
		if ( ! $slug ) {
			return;
		}
		$key = self::key_from_slug( sanitize_title( $slug ) );
		$cfg = $key ? self::get( $key ) : null;
		if ( ! $cfg || empty( $cfg['enabled'] ) || ! function_exists( 'wc_get_product' ) ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			return;
		}
		self::$current = $key;
		global $wp_query;
		$wp_query->is_404  = false;
		$wp_query->is_home = false;
		status_header( 200 );
	}

	public static function template( $template ) {
		if ( self::$current ) {
			return TABARAK_CORE_DIR . 'templates/funnel.php';
		}
		return $template;
	}

	public static function is_funnel() {
		return null !== self::$current;
	}

	public static function body_class( $classes ) {
		if ( self::$current ) {
			$classes[] = 'tabarak-funnel';
			$classes[] = 'tabarak-funnel--' . sanitize_html_class( self::$current );
		}
		return $classes;
	}

	public static function doc_title( $title ) {
		if ( ! self::$current ) {
			return $title;
		}
		$cfg = self::get( self::$current );
		return $cfg['headline'] . ' | ' . get_bloginfo( 'name' );
	}

	public static function head_meta() {
		if ( ! self::$current || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
			return;
		}
		$cfg = self::get( self::$current );
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $cfg['sub'] ) ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( self::url( self::$current ) ) . '">' . "\n";
	}

	public static function assets() {
		if ( ! self::$current ) {
			return;
		}
		wp_enqueue_style( 'tabarak-funnels', TABARAK_CORE_URL . 'assets/css/funnels.css', array(), TABARAK_CORE_VERSION );
		wp_enqueue_script( 'tabarak-funnels', TABARAK_CORE_URL . 'assets/js/funnels.js', array(), TABARAK_CORE_VERSION, true );
		$cfg = self::get( self::$current );
		wp_localize_script(
			'tabarak-funnels',
			'tabarakFunnel',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'tabarak_funnel_lead' ),
				'wa'       => preg_replace( '/[^0-9]/', '', self::biz( 'whatsapp' ) ),
				'funnel'   => self::$current,
				'label'    => $cfg['label'],
				'currency' => 'KSh',
				'pageUrl'  => self::url( self::$current ),
			)
		);
		if ( function_exists( 'WC' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
			wp_enqueue_script( 'wc-cart-fragments' );
		}
	}

	/* ------------------------------------------------------------------
	 * Site-wide WhatsApp order form (v1.13.0)
	 * Every "Order on WhatsApp" button (product page, floating button) opens
	 * the same pop-up form as the funnel pages: details first, then WhatsApp
	 * opens with a ready order. Each order is saved under Funnel Orders.
	 * ------------------------------------------------------------------ */
	private static $sitewide = false;

	public static function sitewide_assets() {
		if ( self::$current || is_admin() || ! apply_filters( 'tabarak_wa_form_enabled', true ) ) {
			return;
		}
		if ( function_exists( 'is_checkout' ) && ( is_checkout() || is_account_page() ) ) {
			return;
		}
		$wa = preg_replace( '/[^0-9]/', '', self::biz( 'whatsapp' ) );
		if ( '' === $wa ) {
			return;
		}
		self::$sitewide = true;
		wp_enqueue_style( 'tabarak-funnels', TABARAK_CORE_URL . 'assets/css/funnels.css', array(), TABARAK_CORE_VERSION );
		wp_enqueue_script( 'tabarak-funnels', TABARAK_CORE_URL . 'assets/js/funnels.js', array(), TABARAK_CORE_VERSION, true );
		wp_script_add_data( 'tabarak-funnels', 'strategy', 'defer' );
		$page = is_singular() ? get_permalink() : home_url( '/' );
		wp_localize_script(
			'tabarak-funnels',
			'tabarakFunnel',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => '',
				'wa'       => $wa,
				'funnel'   => 'site',
				'label'    => '',
				'currency' => 'KSh',
				'pageUrl'  => $page,
			)
		);
	}

	public static function sitewide_modal() {
		if ( ! self::$sitewide ) {
			return;
		}
		$phone = self::biz( 'phone' );
		$tel   = preg_replace( '/[^0-9+]/', '', (string) $phone );
		self::modal( array( 'label' => __( 'A fridge', 'tabarak-core' ) ), $phone, $tel );
	}

	/** The form is hidden until a button is tapped, so its CSS never blocks the first paint. */
	public static function async_style( $tag, $handle ) {
		if ( 'tabarak-funnels' !== $handle || self::$current ) {
			return $tag;
		}
		return preg_replace( '/media=([\'"])all\1/', 'media="print" onload="this.media=\'all\'"', $tag, 1 );
	}

	/* ------------------------------------------------------------------
	 * Data
	 * ------------------------------------------------------------------ */
	public static function term_ids( $cfg ) {
		$ids = array();
		foreach ( (array) $cfg['cats'] as $c ) {
			$t = get_term_by( 'slug', sanitize_title( $c ), 'product_cat' );
			if ( ! $t ) {
				$t = get_term_by( 'name', $c, 'product_cat' );
			}
			if ( $t && ! is_wp_error( $t ) ) {
				$ids[ (int) $t->term_id ] = (int) $t->term_id;
			}
		}
		return array_values( $ids );
	}

	/** Product IDs for a funnel: admin picks first, then best sellers / deals. */
	public static function product_ids( $cfg ) {
		$limit = max( 4, min( 24, (int) $cfg['limit'] ) );
		$ckey  = 'tabarak_funnel_ids_v2_' . $cfg['key'];
		$ids   = get_transient( $ckey );
		if ( is_array( $ids ) ) {
			return $ids;
		}
		$ids = array();
		foreach ( (array) $cfg['products'] as $pid ) {
			$pid = (int) $pid;
			if ( $pid && 'publish' === get_post_status( $pid ) && 'product' === get_post_type( $pid ) ) {
				$ids[ $pid ] = $pid;
			}
		}
		$terms = self::term_ids( $cfg );
		if ( ! empty( $cfg['autofill'] ) && count( $ids ) < $limit && ! empty( $terms ) ) {
			$q = new WP_Query(
				array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'fields'              => 'ids',
					'posts_per_page'      => $limit * 5,
					'no_found_rows'       => true,
					'ignore_sticky_posts' => true,
					'post__not_in'        => array_values( $ids ),
					'meta_key'            => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
					'orderby'             => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
					'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $terms ),
					),
					'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array( 'key' => '_stock_status', 'value' => 'outofstock', 'compare' => '!=' ),
					),
				)
			);
			$on_sale = function_exists( 'wc_get_product_ids_on_sale' ) ? array_flip( wc_get_product_ids_on_sale() ) : array();
			$pool    = array_map( 'intval', $q->posts );
			usort(
				$pool,
				function ( $a, $b ) use ( $on_sale ) {
					return (int) isset( $on_sale[ $b ] ) - (int) isset( $on_sale[ $a ] );
				}
			);
			$floor = (float) $cfg['min_price'];
			$words = array_filter( array_map( 'trim', explode( ',', strtolower( (string) $cfg['exclude'] ) ) ) );
			foreach ( $pool as $pid ) {
				if ( count( $ids ) >= $limit ) {
					break;
				}
				$price = (float) get_post_meta( $pid, '_price', true );
				if ( $floor > 0 && $price < $floor ) {
					continue;
				}
				if ( $words ) {
					$title = strtolower( get_the_title( $pid ) );
					foreach ( $words as $w ) {
						if ( '' !== $w && false !== strpos( $title, $w ) ) {
							continue 2;
						}
					}
				}
				$ids[ $pid ] = $pid;
			}
		}
		$ids = array_slice( array_values( $ids ), 0, $limit );
		set_transient( $ckey, $ids, 15 * MINUTE_IN_SECONDS );
		return $ids;
	}

	public static function flush_cache() {
		foreach ( array_keys( self::definitions() ) as $k ) {
			delete_transient( 'tabarak_funnel_ids_v2_' . $k );
		}
	}

	public static function biz( $key ) {
		$info = apply_filters( 'tabarak_business_info', array() );
		return isset( $info[ $key ] ) ? (string) $info[ $key ] : '';
	}

	public static function wa_link( $text ) {
		$wa = preg_replace( '/[^0-9]/', '', self::biz( 'whatsapp' ) );
		if ( ! $wa ) {
			return '';
		}
		return 'https://wa.me/' . $wa . '?text=' . rawurlencode( $text );
	}

	public static function plain_price( $amount ) {
		return trim( html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' ) );
	}

	/** Parse "Label|min|max" lines. */
	public static function lines( $text, $parts ) {
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$bits = array_map( 'trim', explode( '|', $line ) );
			if ( count( $bits ) >= $parts ) {
				$out[] = $bits;
			}
		}
		return $out;
	}

	/* ------------------------------------------------------------------
	 * Rendering
	 * ------------------------------------------------------------------ */
	public static function icon( $name ) {
		$p = array(
			'check'  => '<path d="M5 12l4 4 10-10"/>',
			'truck'  => '<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
			'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
			'cash'   => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>',
			'phone'  => '<path d="M6 3h3l2 5-2.5 1.5a11 11 0 005 5L16 12l5 2v3a2 2 0 01-2 2A16 16 0 013 5a2 2 0 013-2z"/>',
			'wa'     => '<path d="M4 20l1.3-4A8 8 0 1112 20a8 8 0 01-4-1z"/><path d="M9 9.5c.5 2 2.5 4 4.5 4.5l1-1.2 1.8.8c-.3 1.2-1.3 1.9-2.5 1.7-2.8-.5-5.1-2.8-5.6-5.6-.2-1.2.5-2.2 1.7-2.5l.8 1.8z"/>',
			'store'  => '<path d="M4 9l1-4h14l1 4M5 9v10h14V9"/><path d="M9 19v-5h6v5"/>',
			'tool'   => '<path d="M14 6a4 4 0 00-5 5l-6 6 2 2 6-6a4 4 0 005-5l-2 2-2-2z"/>',
			'arrow'  => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'star'   => '<path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
		);
		return isset( $p[ $name ] ) ? '<svg class="tf-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p[ $name ] . '</svg>' : '';
	}

	/** Render the funnel body (called by templates/funnel.php). */
	public static function render() {
		$cfg   = self::get( self::$current );
		$ids   = self::product_ids( $cfg );
		$terms = self::term_ids( $cfg );
		$cat   = ! empty( $terms ) ? get_term( $terms[0], 'product_cat' ) : null;
		$cat_url = ( $cat && ! is_wp_error( $cat ) ) ? get_term_link( $cat ) : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) );
		if ( is_wp_error( $cat_url ) ) {
			$cat_url = home_url( '/' );
		}
		$count    = ( $cat && ! is_wp_error( $cat ) ) ? (int) $cat->count : 0;
		$products = array();
		$min      = null;
		foreach ( $ids as $pid ) {
			$p = wc_get_product( $pid );
			if ( ! $p || ! $p->is_visible() ) {
				continue;
			}
			$products[] = $p;
			$price      = (float) $p->get_price();
			if ( $price > 0 && ( null === $min || $price < $min ) ) {
				$min = $price;
			}
		}
		$phone    = self::biz( 'phone' );
		$tel      = preg_replace( '/[^0-9+]/', '', $phone );
		$wa_main  = self::wa_link( sprintf( 'Hello Tabarak, I am interested in %s. Please help me choose.', $cfg['label'] ) );
		$btax     = function_exists( 'tabarak_child_brand_tax' ) ? tabarak_child_brand_tax() : ( taxonomy_exists( 'tabarak_brand' ) ? 'tabarak_brand' : '' );
		$label    = $cfg['label'];
		$hero_img = $cfg['image'];
		?>
		<div class="tf">
			<?php if ( ! empty( $cfg['promo'] ) ) : ?>
				<div class="tf-promo"><div class="tf-wrap"><?php echo esc_html( $cfg['promo'] ); ?></div></div>
			<?php endif; ?>

			<!-- HERO -->
			<section class="tf-hero">
				<div class="tf-wrap tf-hero__grid">
					<div class="tf-hero__copy">
						<p class="tf-eyebrow"><?php echo esc_html( $label ); ?> <span>&middot;</span> <?php esc_html_e( 'Tabarak Electronics Nairobi', 'tabarak-core' ); ?></p>
						<h1 class="tf-hero__title"><?php echo esc_html( $cfg['headline'] ); ?></h1>
						<p class="tf-hero__sub"><?php echo esc_html( $cfg['sub'] ); ?></p>
						<ul class="tf-hero__ticks">
							<li><?php echo self::icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Genuine, brand new, with warranty', 'tabarak-core' ); ?></li>
							<li><?php echo self::icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Next-day delivery in Nairobi', 'tabarak-core' ); ?></li>
							<li><?php echo self::icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Pay by M-Pesa, cash or bank', 'tabarak-core' ); ?></li>
						</ul>
						<div class="tf-hero__cta">
							<a class="tf-btn tf-btn--accent tf-btn--lg" href="#tf-picks"><?php esc_html_e( 'See prices', 'tabarak-core' ); ?> <?php echo self::icon( 'arrow' ); // phpcs:ignore ?></a>
							<?php if ( $wa_main ) : ?>
								<a class="tf-btn tf-btn--wa tf-btn--lg js-tf-order" href="<?php echo esc_url( $wa_main ); ?>" target="_blank" rel="noopener nofollow"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><?php esc_html_e( 'Order on WhatsApp', 'tabarak-core' ); ?></a>
							<?php endif; ?>
						</div>
						<?php if ( $phone ) : ?>
							<p class="tf-hero__call"><?php esc_html_e( 'Or call', 'tabarak-core' ); ?> <a href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( $phone ); ?></a> &middot; <?php echo esc_html( self::biz( 'hours' ) ); ?></p>
						<?php endif; ?>
					</div>
					<div class="tf-hero__media">
						<?php if ( null !== $min ) : ?>
							<div class="tf-from"><span><?php esc_html_e( 'Prices from', 'tabarak-core' ); ?></span><strong><?php echo esc_html( self::plain_price( $min ) ); ?></strong></div>
						<?php endif; ?>
						<?php if ( $hero_img ) : ?>
							<img class="tf-hero__img" src="<?php echo esc_url( $hero_img ); ?>" alt="<?php echo esc_attr( $label ); ?>" fetchpriority="high">
						<?php elseif ( ! empty( $products ) ) : ?>
							<?php self::hero_feature( $products[0], $btax ); ?>
						<?php endif; ?>
					</div>
				</div>
			</section>

			<!-- TRUST -->
			<section class="tf-trust">
				<div class="tf-wrap tf-trust__grid">
					<div><?php echo self::icon( 'shield' ); // phpcs:ignore ?><strong><?php esc_html_e( 'Genuine + warranty', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'Original brands only', 'tabarak-core' ); ?></span></div>
					<div><?php echo self::icon( 'truck' ); // phpcs:ignore ?><strong><?php esc_html_e( 'Fast delivery', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'Nairobi next day, countrywide 1-5 days', 'tabarak-core' ); ?></span></div>
					<div><?php echo self::icon( 'cash' ); // phpcs:ignore ?><strong><?php esc_html_e( 'Easy payment', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'M-Pesa, cash or bank transfer', 'tabarak-core' ); ?></span></div>
					<div><?php echo self::icon( 'store' ); // phpcs:ignore ?><strong><?php esc_html_e( 'Visit our shop', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'Sky Mall, Luthuli Street', 'tabarak-core' ); ?></span></div>
				</div>
			</section>

			<!-- BUDGET -->
			<?php $bands = self::lines( $cfg['bands'], 3 ); ?>
			<?php if ( ! empty( $bands ) ) : ?>
			<section class="tf-sec tf-budget">
				<div class="tf-wrap">
					<h2 class="tf-h2"><?php esc_html_e( 'What is your budget?', 'tabarak-core' ); ?></h2>
					<div class="tf-budget__grid">
						<?php foreach ( $bands as $b ) : ?>
							<?php
							$args = array();
							if ( (float) $b[1] > 0 ) {
								$args['tab_min'] = (int) $b[1];
							}
							if ( (float) $b[2] > 0 ) {
								$args['tab_max'] = (int) $b[2];
							}
							?>
							<a class="tf-budget__item" href="<?php echo esc_url( add_query_arg( $args, $cat_url ) ); ?>" data-min="<?php echo esc_attr( (float) $b[1] ); ?>" data-max="<?php echo esc_attr( (float) $b[2] ); ?>" data-label="<?php echo esc_attr( 'KSh ' . $b[0] ); ?>"><small>KSh</small><span><?php echo esc_html( $b[0] ); ?></span><?php echo self::icon( 'arrow' ); // phpcs:ignore ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<?php endif; ?>

			<!-- PICKS -->
			<section class="tf-sec tf-picks" id="tf-picks">
				<div class="tf-wrap">
					<div class="tf-sechead">
						<h2 class="tf-h2"><?php echo esc_html( sprintf( /* translators: %s category */ __( 'Top %s deals today', 'tabarak-core' ), $label ) ); ?></h2>
						<?php if ( $count ) : ?>
							<a class="tf-link" href="<?php echo esc_url( $cat_url ); ?>"><?php echo esc_html( sprintf( /* translators: %d count */ __( 'View all %d', 'tabarak-core' ), $count ) ); ?> <?php echo self::icon( 'arrow' ); // phpcs:ignore ?></a>
						<?php endif; ?>
					</div>
					<?php if ( empty( $products ) ) : ?>
						<p class="tf-empty"><?php esc_html_e( 'Products are being updated. Call or WhatsApp us for today\'s prices.', 'tabarak-core' ); ?></p>
					<?php else : ?>
					<div class="tf-filterbar" hidden><span><?php esc_html_e( 'Showing', 'tabarak-core' ); ?> <strong class="tf-filterbar__label"></strong> <em class="tf-filterbar__count"></em></span><button type="button" class="tf-filterbar__clear"><?php esc_html_e( 'Show all', 'tabarak-core' ); ?></button><a class="tf-filterbar__more" href="#"><?php esc_html_e( 'More in this budget', 'tabarak-core' ); ?></a></div>
					<ul class="tf-grid">
						<?php foreach ( $products as $i => $p ) : ?>
							<?php self::card( $p, $i, $btax ); ?>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
					<div class="tf-center"><a class="tf-btn tf-btn--dark" href="<?php echo esc_url( $cat_url ); ?>"><?php echo esc_html( sprintf( /* translators: %s category */ __( 'See all %s', 'tabarak-core' ), $label ) ); ?></a></div>
				</div>
			</section>

			<!-- BRANDS -->
			<?php
			$brands = array();
			if ( $btax ) {
				foreach ( $products as $p ) {
					$bt = get_the_terms( $p->get_id(), $btax );
					if ( $bt && ! is_wp_error( $bt ) ) {
						$brands[ $bt[0]->slug ] = $bt[0]->name;
					}
				}
			}
			?>
			<?php if ( count( $brands ) > 1 ) : ?>
			<section class="tf-sec tf-brands">
				<div class="tf-wrap">
					<h2 class="tf-h2"><?php esc_html_e( 'Shop by brand', 'tabarak-core' ); ?></h2>
					<div class="tf-chips">
						<?php foreach ( $brands as $slug => $name ) : ?>
							<a class="tf-chip" href="<?php echo esc_url( add_query_arg( 'tab_brand[]', $slug, $cat_url ) ); ?>"><?php echo esc_html( $name ); ?></a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
			<?php endif; ?>

			<!-- HOW TO ORDER -->
			<section class="tf-sec tf-steps">
				<div class="tf-wrap">
					<h2 class="tf-h2"><?php esc_html_e( 'Order in 3 easy steps', 'tabarak-core' ); ?></h2>
					<ol class="tf-steps__grid">
						<li><span>1</span><strong><?php esc_html_e( 'Choose your item', 'tabarak-core' ); ?></strong><p><?php esc_html_e( 'Pick from the deals above or ask us to recommend one.', 'tabarak-core' ); ?></p></li>
						<li><span>2</span><strong><?php esc_html_e( 'Order online or on WhatsApp', 'tabarak-core' ); ?></strong><p><?php esc_html_e( 'We call or WhatsApp you to confirm price, stock and delivery.', 'tabarak-core' ); ?></p></li>
						<li><span>3</span><strong><?php esc_html_e( 'Receive and pay', 'tabarak-core' ); ?></strong><p><?php esc_html_e( 'Pay by M-Pesa, cash or bank transfer. Installation on request.', 'tabarak-core' ); ?></p></li>
					</ol>
				</div>
			</section>

			<!-- FAQ -->
			<?php
			$faqs = array_merge(
				self::lines( $cfg['faqs'], 2 ),
				array(
					array( __( 'How fast is delivery?', 'tabarak-core' ), __( 'Same-day or next-day in Nairobi for orders before 2:00 PM. Major towns take 1-3 business days and the rest of Kenya 2-5 days.', 'tabarak-core' ) ),
					array( __( 'How do I pay?', 'tabarak-core' ), __( 'M-Pesa, cash or bank transfer. We confirm every order by phone or WhatsApp before dispatch.', 'tabarak-core' ) ),
					array( __( 'Is there a warranty?', 'tabarak-core' ), __( 'Yes. All products are genuine and covered by the manufacturer warranty. Eligible items can be returned within 7 days.', 'tabarak-core' ) ),
				)
			);
			?>
			<section class="tf-sec tf-faq">
				<div class="tf-wrap tf-faq__wrap">
					<h2 class="tf-h2"><?php esc_html_e( 'Questions customers ask', 'tabarak-core' ); ?></h2>
					<?php foreach ( $faqs as $i => $f ) : ?>
						<details class="tf-faq__item" <?php echo 0 === $i ? 'open' : ''; ?>>
							<summary><?php echo esc_html( $f[0] ); ?></summary>
							<p><?php echo esc_html( $f[1] ); ?></p>
						</details>
					<?php endforeach; ?>
				</div>
			</section>

			<!-- QUOTE / BEST PRICE -->
			<?php self::quote_section( $cfg, $phone, $tel ); ?>

			<?php if ( $wa_main ) : ?>
				<a class="tf-fab js-tf-order" href="<?php echo esc_url( $wa_main ); ?>" target="_blank" rel="noopener nofollow"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><span><?php esc_html_e( 'Ask on WhatsApp', 'tabarak-core' ); ?></span></a>
			<?php endif; ?>

			<!-- STICKY MOBILE BAR -->
			<div class="tf-sticky" aria-label="<?php esc_attr_e( 'Quick order', 'tabarak-core' ); ?>">
				<?php if ( $phone ) : ?><a class="tf-sticky__call" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo self::icon( 'phone' ); // phpcs:ignore ?><?php esc_html_e( 'Call', 'tabarak-core' ); ?></a><?php endif; ?>
				<?php if ( $wa_main ) : ?><a class="tf-sticky__wa js-tf-order" href="<?php echo esc_url( $wa_main ); ?>" target="_blank" rel="noopener nofollow"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><?php esc_html_e( 'Order on WhatsApp', 'tabarak-core' ); ?></a><?php endif; ?>
			</div>
		</div>
		<?php
		self::modal( $cfg, $phone, $tel );
		self::schema( $cfg, $products, $faqs );
	}

	/** Hero "Top pick" product spotlight. */
	private static function hero_feature( $p, $btax ) {
		$price   = (float) $p->get_price();
		$regular = (float) $p->get_regular_price();
		$save    = ( $p->is_on_sale() && $regular > $price && $price > 0 ) ? $regular - $price : 0;
		$brand   = '';
		if ( $btax ) {
			$bt = get_the_terms( $p->get_id(), $btax );
			if ( $bt && ! is_wp_error( $bt ) ) {
				$brand = $bt[0]->name;
			}
		}
		$link = get_permalink( $p->get_id() );
		?>
		<div class="tf-spot">
			<span class="tf-spot__tag"><?php echo self::icon( 'star' ); // phpcs:ignore ?><?php esc_html_e( 'Top pick this week', 'tabarak-core' ); ?></span>
			<a class="tf-spot__media" href="<?php echo esc_url( $link ); ?>">
				<?php echo $p->get_image_id() ? wp_get_attachment_image( $p->get_image_id(), 'woocommerce_single', false, array( 'alt' => esc_attr( $p->get_name() ), 'loading' => 'eager', 'fetchpriority' => 'high' ) ) : wc_placeholder_img( 'woocommerce_single' ); // phpcs:ignore ?>
			</a>
			<div class="tf-spot__body">
				<?php if ( $brand ) : ?><span class="tf-card__brand"><?php echo esc_html( $brand ); ?></span><?php endif; ?>
				<a class="tf-spot__name" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $p->get_name() ); ?></a>
				<div class="tf-spot__row">
					<div class="tf-spot__price">
						<strong><?php echo esc_html( $price > 0 ? self::plain_price( $price ) : __( 'Call for price', 'tabarak-core' ) ); ?></strong>
						<?php if ( $save > 0 ) : ?><del><?php echo esc_html( self::plain_price( $regular ) ); ?></del><span class="tf-spot__save"><?php echo esc_html( sprintf( /* translators: %s amount */ __( 'Save %s', 'tabarak-core' ), self::plain_price( $save ) ) ); ?></span><?php endif; ?>
					</div>
					<a class="tf-btn tf-btn--wa js-tf-order" href="<?php echo esc_url( self::wa_link( 'Hello Tabarak, I want to order: ' . $p->get_name() . ' ' . $link ) ); ?>" target="_blank" rel="noopener nofollow"
						data-id="<?php echo esc_attr( $p->get_id() ); ?>"
						data-name="<?php echo esc_attr( $p->get_name() ); ?>"
						data-price="<?php echo esc_attr( $price > 0 ? $price : '' ); ?>"
						data-price-label="<?php echo esc_attr( $price > 0 ? self::plain_price( $price ) : __( 'Call for price', 'tabarak-core' ) ); ?>"
						data-regular="<?php echo esc_attr( $save > 0 ? self::plain_price( $regular ) : '' ); ?>"
						data-img="<?php echo esc_url( (string) wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' ) ); ?>"
						data-brand="<?php echo esc_attr( $brand ); ?>"
						data-url="<?php echo esc_url( $link ); ?>"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><?php esc_html_e( 'Order now', 'tabarak-core' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/** One product card. */
	private static function card( $p, $i, $btax ) {
		$link    = get_permalink( $p->get_id() );
		$price   = (float) $p->get_price();
		$regular = (float) $p->get_regular_price();
		$save    = ( $p->is_on_sale() && $regular > $price && $price > 0 ) ? $regular - $price : 0;
		$brand   = '';
		if ( $btax ) {
			$bt = get_the_terms( $p->get_id(), $btax );
			if ( $bt && ! is_wp_error( $bt ) ) {
				$brand = $bt[0]->name;
			}
		}
		$msg = sprintf( "Hello Tabarak, I want to order:\n%s\nPrice: %s\n%s", $p->get_name(), $price > 0 ? self::plain_price( $price ) : '-', $link );
		$wa  = self::wa_link( $msg );
		?>
		<li class="tf-card" data-price="<?php echo esc_attr( $price ); ?>">
			<a class="tf-card__media" href="<?php echo esc_url( $link ); ?>">
				<?php
				echo $p->get_image_id() ? wp_get_attachment_image( $p->get_image_id(), 'woocommerce_thumbnail', false, array( 'alt' => esc_attr( $p->get_name() ), 'loading' => 'lazy' ) ) : wc_placeholder_img( 'woocommerce_thumbnail' ); // phpcs:ignore
				?>
				<?php if ( 0 === $i ) : ?>
					<span class="tf-card__ribbon"><?php echo self::icon( 'star' ); // phpcs:ignore ?><?php esc_html_e( 'Top pick', 'tabarak-core' ); ?></span>
				<?php elseif ( $save > 0 ) : ?>
					<span class="tf-card__ribbon tf-card__ribbon--save"><?php echo esc_html( sprintf( /* translators: %s amount */ __( 'Save %s', 'tabarak-core' ), self::plain_price( $save ) ) ); ?></span>
				<?php endif; ?>
			</a>
			<div class="tf-card__body">
				<?php if ( $brand ) : ?><span class="tf-card__brand"><?php echo esc_html( $brand ); ?></span><?php endif; ?>
				<a class="tf-card__title" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $p->get_name() ); ?></a>
				<div class="tf-card__price">
					<?php if ( $price > 0 ) : ?>
						<strong><?php echo esc_html( self::plain_price( $price ) ); ?></strong>
						<?php if ( $save > 0 ) : ?><del><?php echo esc_html( self::plain_price( $regular ) ); ?></del><?php endif; ?>
					<?php else : ?>
						<strong><?php esc_html_e( 'Call for price', 'tabarak-core' ); ?></strong>
					<?php endif; ?>
				</div>
				<?php if ( $save > 0 && 0 === $i ) : ?>
					<span class="tf-card__saving"><?php echo esc_html( sprintf( /* translators: %s amount */ __( 'You save %s', 'tabarak-core' ), self::plain_price( $save ) ) ); ?></span>
				<?php endif; ?>
				<?php if ( $p->is_in_stock() ) : ?><span class="tf-card__stock"><?php esc_html_e( 'In stock', 'tabarak-core' ); ?></span><?php endif; ?>
				<div class="tf-card__btns">
					<?php if ( $wa ) : ?>
						<a class="tf-btn tf-btn--wa tf-btn--block js-tf-order" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener nofollow"
							data-id="<?php echo esc_attr( $p->get_id() ); ?>"
							data-name="<?php echo esc_attr( $p->get_name() ); ?>"
							data-price="<?php echo esc_attr( $price > 0 ? $price : '' ); ?>"
							data-price-label="<?php echo esc_attr( $price > 0 ? self::plain_price( $price ) : __( 'Call for price', 'tabarak-core' ) ); ?>"
							data-regular="<?php echo esc_attr( $save > 0 ? self::plain_price( $regular ) : '' ); ?>"
							data-img="<?php echo esc_url( (string) wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' ) ); ?>"
							data-brand="<?php echo esc_attr( $brand ); ?>"
							data-url="<?php echo esc_url( $link ); ?>"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><?php esc_html_e( 'Order on WhatsApp', 'tabarak-core' ); ?></a>
					<?php endif; ?>
					<?php if ( $p->is_type( 'simple' ) && $p->is_purchasable() && $p->is_in_stock() ) : ?>
						<a class="tf-btn tf-btn--line tf-btn--block add_to_cart_button ajax_add_to_cart" href="<?php echo esc_url( $p->add_to_cart_url() ); ?>" data-product_id="<?php echo esc_attr( $p->get_id() ); ?>" data-quantity="1" rel="nofollow"><?php esc_html_e( 'Add to cart', 'tabarak-core' ); ?></a>
					<?php else : ?>
						<a class="tf-btn tf-btn--line tf-btn--block" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'View details', 'tabarak-core' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</li>
		<?php
	}

	private static function schema( $cfg, $products, $faqs ) {
		$items = array();
		foreach ( $products as $n => $p ) {
			$items[] = array( '@type' => 'ListItem', 'position' => $n + 1, 'url' => get_permalink( $p->get_id() ), 'name' => $p->get_name() );
		}
		$qa = array();
		foreach ( $faqs as $f ) {
			$qa[] = array( '@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $f[1] ) );
		}
		$data = array(
			array( '@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => $cfg['headline'], 'itemListElement' => $items ),
			array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $qa ),
		);
		echo '<script type="application/ld+json">' . wp_json_encode( $data ) . '</script>';
	}

	/* ------------------------------------------------------------------
	 * Order form (modal + inline best-price section)
	 * ------------------------------------------------------------------ */
	public static function locations() {
		return apply_filters(
			'tabarak_funnel_locations',
			array(
				'Nairobi CBD', 'Nairobi - Westlands / Parklands', 'Nairobi - Kilimani / Kileleshwa / Lavington', 'Nairobi - Karen / Langata', 'Nairobi - South B / C / Industrial Area', 'Nairobi - Embakasi / Utawala', 'Nairobi - Kasarani / Roysambu / Ruaka', 'Nairobi - Eastlands', 'Kiambu / Thika / Ruiru', 'Kitengela / Athi River / Syokimau', 'Ngong / Rongai', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Nyeri', 'Machakos', 'Meru', 'Other town in Kenya',
			)
		);
	}

	/** Shared contact fields. $p = unique id prefix. */
	private static function contact_fields( $p ) {
		?>
		<div class="tf-f">
			<label for="<?php echo esc_attr( $p ); ?>-name"><?php esc_html_e( 'Full name', 'tabarak-core' ); ?> <span aria-hidden="true">*</span></label>
			<input id="<?php echo esc_attr( $p ); ?>-name" name="name" type="text" autocomplete="name" required minlength="2" maxlength="80" placeholder="<?php esc_attr_e( 'e.g. Jane Wanjiku', 'tabarak-core' ); ?>">
			<span class="tf-f__err" aria-live="polite"></span>
		</div>
		<div class="tf-f">
			<label for="<?php echo esc_attr( $p ); ?>-phone"><?php esc_html_e( 'Phone / WhatsApp number', 'tabarak-core' ); ?> <span aria-hidden="true">*</span></label>
			<div class="tf-phone"><span>+254</span><input id="<?php echo esc_attr( $p ); ?>-phone" name="phone" type="tel" inputmode="tel" autocomplete="tel" required placeholder="0712 345 678"></div>
			<span class="tf-f__err" aria-live="polite"></span>
		</div>
		<div class="tf-f">
			<label for="<?php echo esc_attr( $p ); ?>-loc"><?php esc_html_e( 'Delivery location', 'tabarak-core' ); ?> <span aria-hidden="true">*</span></label>
			<select id="<?php echo esc_attr( $p ); ?>-loc" name="location" required>
				<option value=""><?php esc_html_e( 'Select your area', 'tabarak-core' ); ?></option>
				<?php foreach ( self::locations() as $loc ) : ?>
					<option value="<?php echo esc_attr( $loc ); ?>"><?php echo esc_html( $loc ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="tf-f__err" aria-live="polite"></span>
		</div>
		<div class="tf-f">
			<label for="<?php echo esc_attr( $p ); ?>-area"><?php esc_html_e( 'Estate, street or building', 'tabarak-core' ); ?></label>
			<input id="<?php echo esc_attr( $p ); ?>-area" name="area" type="text" maxlength="120" autocomplete="street-address" placeholder="<?php esc_attr_e( 'e.g. Kilimani, Argwings Kodhek Rd', 'tabarak-core' ); ?>">
		</div>
		<?php
	}

	/** Order modal (one per page, filled by JS with the chosen product). */
	private static function modal( $cfg, $phone, $tel ) {
		?>
		<div class="tf-modal" id="tf-order" hidden>
			<div class="tf-modal__backdrop" data-tf-close></div>
			<div class="tf-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tf-order-title">
				<header class="tf-modal__head">
					<div>
						<p class="tf-modal__eyebrow"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><?php esc_html_e( 'Fast WhatsApp order', 'tabarak-core' ); ?></p>
						<h2 id="tf-order-title" class="tf-modal__title"><?php esc_html_e( 'Complete your order', 'tabarak-core' ); ?></h2>
					</div>
					<button type="button" class="tf-modal__close" data-tf-close aria-label="<?php esc_attr_e( 'Close', 'tabarak-core' ); ?>">&times;</button>
				</header>
				<ol class="tf-progress" aria-hidden="true">
					<li class="is-on"><span>1</span><?php esc_html_e( 'Your details', 'tabarak-core' ); ?></li>
					<li><span>2</span><?php esc_html_e( 'Send on WhatsApp', 'tabarak-core' ); ?></li>
					<li><span>3</span><?php esc_html_e( 'We confirm & deliver', 'tabarak-core' ); ?></li>
				</ol>
				<form class="tf-form" data-tf-form="modal" novalidate>
					<div class="tf-sum" data-tf-product>
						<img class="tf-sum__img" src="data:image/gif;base64,R0lGODlhAQABAAAAACw=" alt="" width="72" height="72">
						<div class="tf-sum__info">
							<span class="tf-sum__brand"></span>
							<strong class="tf-sum__name"></strong>
							<span class="tf-sum__price"><b></b> <del></del></span>
						</div>
						<div class="tf-qty" role="group" aria-label="<?php esc_attr_e( 'Quantity', 'tabarak-core' ); ?>">
							<button type="button" data-qty="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'tabarak-core' ); ?>">&minus;</button>
							<input type="number" name="qty" value="1" min="1" max="20" inputmode="numeric" aria-label="<?php esc_attr_e( 'Quantity', 'tabarak-core' ); ?>">
							<button type="button" data-qty="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'tabarak-core' ); ?>">+</button>
						</div>
					</div>
					<div class="tf-f tf-f--full tf-need" data-tf-general hidden>
						<label for="tfm-need"><?php esc_html_e( 'What are you looking for?', 'tabarak-core' ); ?></label>
						<textarea id="tfm-need" name="need" rows="2" maxlength="400" placeholder="<?php echo esc_attr( sprintf( /* translators: %s category */ __( 'e.g. %s for a family of 4, budget around KSh 40,000', 'tabarak-core' ), $cfg['label'] ) ); ?>"></textarea>
					</div>

					<div class="tf-fields">
						<?php self::contact_fields( 'tfm' ); ?>
					</div>

					<fieldset class="tf-choice">
						<legend><?php esc_html_e( 'How do you want to receive it?', 'tabarak-core' ); ?></legend>
						<label class="tf-opt"><input type="radio" name="delivery" value="delivery" checked><span><strong><?php esc_html_e( 'Deliver to me', 'tabarak-core' ); ?></strong><small><?php esc_html_e( 'Next day in Nairobi, 1-5 days countrywide', 'tabarak-core' ); ?></small></span></label>
						<label class="tf-opt"><input type="radio" name="delivery" value="pickup"><span><strong><?php esc_html_e( 'Pick up at our shop', 'tabarak-core' ); ?></strong><small><?php esc_html_e( 'Nairobi Sky Mall, Luthuli Street', 'tabarak-core' ); ?></small></span></label>
					</fieldset>

					<fieldset class="tf-choice tf-choice--pay">
						<legend><?php esc_html_e( 'How will you pay?', 'tabarak-core' ); ?></legend>
						<label class="tf-pill"><input type="radio" name="payment" value="M-Pesa on delivery" checked><span><?php esc_html_e( 'M-Pesa on delivery', 'tabarak-core' ); ?></span></label>
						<label class="tf-pill"><input type="radio" name="payment" value="M-Pesa now"><span><?php esc_html_e( 'M-Pesa now', 'tabarak-core' ); ?></span></label>
						<label class="tf-pill"><input type="radio" name="payment" value="Cash"><span><?php esc_html_e( 'Cash', 'tabarak-core' ); ?></span></label>
						<label class="tf-pill"><input type="radio" name="payment" value="Bank transfer"><span><?php esc_html_e( 'Bank transfer', 'tabarak-core' ); ?></span></label>
					</fieldset>

					<label class="tf-check"><input type="checkbox" name="install" value="1"><span><?php esc_html_e( 'I need installation / setup', 'tabarak-core' ); ?></span></label>

					<details class="tf-more">
						<summary><?php esc_html_e( 'Add a note (optional)', 'tabarak-core' ); ?></summary>
						<textarea name="notes" rows="2" maxlength="400" aria-label="<?php esc_attr_e( 'Note', 'tabarak-core' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Please call before delivery, preferred colour, gate details', 'tabarak-core' ); ?>"></textarea>
					</details>

					<input type="text" name="company" class="tf-hp" tabindex="-1" autocomplete="off" aria-hidden="true">

					<div class="tf-total" data-tf-total hidden><span><?php esc_html_e( 'Estimated total', 'tabarak-core' ); ?></span><strong></strong></div>

					<button type="submit" class="tf-btn tf-btn--wa tf-btn--lg tf-btn--block tf-submit"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><span><?php esc_html_e( 'Send order on WhatsApp', 'tabarak-core' ); ?></span></button>
					<p class="tf-fine"><?php echo self::icon( 'shield' ); // phpcs:ignore ?><span><?php esc_html_e( 'No payment now. We confirm price, stock and delivery with you first. Your details are only used for this order.', 'tabarak-core' ); ?></span></p>
					<?php if ( $phone ) : ?>
						<p class="tf-fine tf-fine--call"><?php esc_html_e( 'Prefer to talk?', 'tabarak-core' ); ?> <a href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( $phone ); ?></a></p>
					<?php endif; ?>
				</form>

				<div class="tf-done" hidden>
					<div class="tf-done__icon"><?php echo self::icon( 'check' ); // phpcs:ignore ?></div>
					<h3><?php esc_html_e( 'Almost done! Tap "Send" in WhatsApp', 'tabarak-core' ); ?></h3>
					<p><?php esc_html_e( 'Your order reference is', 'tabarak-core' ); ?> <strong class="tf-done__ref"></strong></p>
					<p class="tf-done__text"><?php esc_html_e( 'Our team will reply on WhatsApp to confirm price, stock and delivery time. During working hours we usually reply within minutes.', 'tabarak-core' ); ?></p>
					<a class="tf-btn tf-btn--wa tf-btn--lg tf-btn--block tf-done__wa" href="#" target="_blank" rel="noopener nofollow"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><?php esc_html_e( 'WhatsApp did not open? Tap here', 'tabarak-core' ); ?></a>
					<button type="button" class="tf-btn tf-btn--line tf-btn--block" data-tf-close><?php esc_html_e( 'Continue browsing', 'tabarak-core' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/** Inline "get the best price" section near the end of the page. */
	private static function quote_section( $cfg, $phone, $tel ) {
		$bands = self::lines( $cfg['bands'], 3 );
		?>
		<section class="tf-quote" id="tf-quote">
			<div class="tf-wrap tf-quote__grid">
				<div class="tf-quote__copy">
					<p class="tf-eyebrow"><?php esc_html_e( 'Free expert advice', 'tabarak-core' ); ?></p>
					<h2><?php esc_html_e( 'Not sure which one to buy? Get the best price in minutes.', 'tabarak-core' ); ?></h2>
					<p><?php echo esc_html( sprintf( /* translators: %s category */ __( 'Tell us your budget and needs. A Tabarak specialist will recommend the right %s and send you today\'s best price on WhatsApp.', 'tabarak-core' ), strtolower( $cfg['label'] ) ) ); ?></p>
					<ul class="tf-quote__list">
						<li><?php echo self::icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Honest advice, no pressure', 'tabarak-core' ); ?></li>
						<li><?php echo self::icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Genuine brands with warranty', 'tabarak-core' ); ?></li>
						<li><?php echo self::icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Delivery and installation arranged for you', 'tabarak-core' ); ?></li>
					</ul>
					<?php if ( $phone ) : ?>
						<a class="tf-quote__call" href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo self::icon( 'phone' ); // phpcs:ignore ?><span><small><?php esc_html_e( 'Or call us now', 'tabarak-core' ); ?></small><?php echo esc_html( $phone ); ?></span></a>
					<?php endif; ?>
				</div>
				<form class="tf-form tf-card-form" data-tf-form="quote" novalidate>
					<p class="tf-card-form__title"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><?php esc_html_e( 'Get my best price', 'tabarak-core' ); ?></p>
					<div class="tf-fields">
						<?php self::contact_fields( 'tfq' ); ?>
						<?php if ( ! empty( $bands ) ) : ?>
						<div class="tf-f tf-f--full">
							<label for="tfq-budget"><?php esc_html_e( 'Your budget', 'tabarak-core' ); ?></label>
							<select id="tfq-budget" name="budget">
								<option value=""><?php esc_html_e( 'Choose a budget (optional)', 'tabarak-core' ); ?></option>
								<?php foreach ( $bands as $b ) : ?>
									<option value="<?php echo esc_attr( 'KSh ' . $b[0] ); ?>"><?php echo esc_html( 'KSh ' . $b[0] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<?php endif; ?>
						<div class="tf-f tf-f--full">
							<label for="tfq-need"><?php esc_html_e( 'What do you need?', 'tabarak-core' ); ?></label>
							<textarea id="tfq-need" name="need" rows="2" maxlength="400" placeholder="<?php esc_attr_e( 'e.g. size, brand you like, where it will be used', 'tabarak-core' ); ?>"></textarea>
						</div>
					</div>
					<input type="text" name="company" class="tf-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
					<button type="submit" class="tf-btn tf-btn--wa tf-btn--lg tf-btn--block tf-submit"><?php echo self::icon( 'wa' ); // phpcs:ignore ?><span><?php esc_html_e( 'Send on WhatsApp', 'tabarak-core' ); ?></span></button>
					<p class="tf-fine"><?php echo self::icon( 'shield' ); // phpcs:ignore ?><span><?php esc_html_e( 'Free, no obligation. Your details are only used to help you.', 'tabarak-core' ); ?></span></p>
					<div class="tf-inline-done" hidden><?php echo self::icon( 'check' ); // phpcs:ignore ?><div><strong><?php esc_html_e( 'Request ready. Tap "Send" in WhatsApp.', 'tabarak-core' ); ?></strong><span><?php esc_html_e( 'Reference', 'tabarak-core' ); ?> <b class="tf-done__ref"></b> &middot; <a class="tf-done__wa" href="#" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Open WhatsApp again', 'tabarak-core' ); ?></a></span></div></div>
				</form>
			</div>
		</section>
		<?php
	}

	/* ------------------------------------------------------------------
	 * Leads: every form submission is saved before WhatsApp opens
	 * ------------------------------------------------------------------ */
	public static function register_leads() {
		register_post_type(
			'tabarak_lead',
			array(
				'labels'          => array(
					'name'          => __( 'Funnel Orders', 'tabarak-core' ),
					'singular_name' => __( 'Funnel Order', 'tabarak-core' ),
					'menu_name'     => __( 'Funnel Orders', 'tabarak-core' ),
					'all_items'     => __( 'Funnel Orders', 'tabarak-core' ),
					'edit_item'     => __( 'Funnel order', 'tabarak-core' ),
					'search_items'  => __( 'Search orders', 'tabarak-core' ),
					'not_found'     => __( 'No funnel orders yet.', 'tabarak-core' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'tabarak-core',
				'show_in_rest'    => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
				'map_meta_cap'    => true,
			)
		);
	}

	/** Normalise a Kenyan phone number to 2547XXXXXXXX / 2541XXXXXXXX, or ''. */
	public static function normalise_phone( $raw ) {
		$d = preg_replace( '/\D/', '', (string) $raw );
		if ( 10 === strlen( $d ) && '0' === $d[0] ) {
			$d = '254' . substr( $d, 1 );
		} elseif ( 9 === strlen( $d ) && in_array( $d[0], array( '7', '1' ), true ) ) {
			$d = '254' . $d;
		}
		return preg_match( '/^254[17]\d{8}$/', $d ) ? $d : '';
	}

	private static function lead_fields() {
		return array(
			'ref'      => __( 'Reference', 'tabarak-core' ),
			'name'     => __( 'Name', 'tabarak-core' ),
			'phone'    => __( 'Phone', 'tabarak-core' ),
			'product'  => __( 'Product', 'tabarak-core' ),
			'price'    => __( 'Price', 'tabarak-core' ),
			'qty'      => __( 'Quantity', 'tabarak-core' ),
			'location' => __( 'Location', 'tabarak-core' ),
			'area'     => __( 'Estate / street', 'tabarak-core' ),
			'delivery' => __( 'Delivery', 'tabarak-core' ),
			'payment'  => __( 'Payment', 'tabarak-core' ),
			'install'  => __( 'Installation', 'tabarak-core' ),
			'budget'   => __( 'Budget', 'tabarak-core' ),
			'need'     => __( 'Looking for', 'tabarak-core' ),
			'notes'    => __( 'Notes', 'tabarak-core' ),
			'funnel'   => __( 'Funnel', 'tabarak-core' ),
			'source'   => __( 'Form', 'tabarak-core' ),
		);
	}

	public static function ajax_lead() {
		// Public form on cached pages: a stale nonce must not block real customers,
		// so spam is stopped with a honeypot, a fill-time trap and per-IP limits instead.
		$in = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised per field below.
		if ( ! empty( $in['company'] ) || ( isset( $in['tt'] ) && (int) $in['tt'] < 2500 ) ) {
			wp_send_json_success( array( 'ok' => 1 ) ); // Bot: quietly ignore.
		}
		foreach ( array( 'need', 'notes' ) as $long ) {
			if ( isset( $in[ $long ] ) && preg_match_all( '#https?://#i', (string) $in[ $long ] ) > 1 ) {
				wp_send_json_success( array( 'ok' => 1 ) ); // Link spam: quietly ignore.
			}
		}
		$ip  = class_exists( 'Tabarak_Hardening' ) ? Tabarak_Hardening::visitor_ip() : ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '' );
		$rk  = 'tabarak_lead_rl_' . md5( $ip );
		$cnt = (int) get_transient( $rk );
		if ( $cnt >= 15 ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please call us.', 'tabarak-core' ) ), 429 );
		}
		set_transient( $rk, $cnt + 1, HOUR_IN_SECONDS );

		$name  = isset( $in['name'] ) ? mb_substr( sanitize_text_field( $in['name'] ), 0, 80 ) : '';
		$phone = self::normalise_phone( isset( $in['phone'] ) ? $in['phone'] : '' );
		$ref   = isset( $in['ref'] ) ? strtoupper( sanitize_text_field( $in['ref'] ) ) : '';
		if ( strlen( $name ) < 2 || '' === $phone || ! preg_match( '/^TBK-\d{6}-[A-Z0-9]{4}$/', $ref ) ) {
			wp_send_json_error( array( 'message' => __( 'Please check your name and phone number.', 'tabarak-core' ) ), 400 );
		}
		$pid     = isset( $in['product_id'] ) ? absint( $in['product_id'] ) : 0;
		$product = ( $pid && 'product' === get_post_type( $pid ) ) ? get_the_title( $pid ) : '';
		$funnel  = isset( $in['funnel'] ) ? sanitize_key( $in['funnel'] ) : '';
		$defs    = self::definitions();
		$flabel  = isset( $defs[ $funnel ] ) ? $defs[ $funnel ]['label'] : ( 'site' === $funnel ? __( 'Website (product page / WhatsApp button)', 'tabarak-core' ) : '' );
		$data    = array(
			'ref'      => $ref,
			'name'     => $name,
			'phone'    => '+' . $phone,
			'product'  => $product,
			'price'    => isset( $in['price'] ) ? sanitize_text_field( $in['price'] ) : '',
			'qty'      => (string) max( 1, min( 20, isset( $in['qty'] ) ? absint( $in['qty'] ) : 1 ) ),
			'location' => isset( $in['location'] ) ? sanitize_text_field( $in['location'] ) : '',
			'area'     => isset( $in['area'] ) ? sanitize_text_field( $in['area'] ) : '',
			'delivery' => ( isset( $in['delivery'] ) && 'pickup' === $in['delivery'] ) ? __( 'Pick up at shop', 'tabarak-core' ) : __( 'Deliver to customer', 'tabarak-core' ),
			'payment'  => isset( $in['payment'] ) ? sanitize_text_field( $in['payment'] ) : '',
			'install'  => ! empty( $in['install'] ) ? __( 'Yes', 'tabarak-core' ) : __( 'No', 'tabarak-core' ),
			'budget'   => isset( $in['budget'] ) ? sanitize_text_field( $in['budget'] ) : '',
			'need'     => isset( $in['need'] ) ? mb_substr( sanitize_textarea_field( $in['need'] ), 0, 400 ) : '',
			'notes'    => isset( $in['notes'] ) ? mb_substr( sanitize_textarea_field( $in['notes'] ), 0, 400 ) : '',
			'funnel'   => $flabel,
			'source'   => ( isset( $in['source'] ) && 'quote' === $in['source'] ) ? __( 'Best price request', 'tabarak-core' ) : __( 'Order form', 'tabarak-core' ),
		);
		$title   = $ref . ' - ' . $name . ( $product ? ' - ' . $product : ( $flabel ? ' - ' . $flabel : '' ) );
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'tabarak_lead',
				'post_status' => 'publish',
				'post_title'  => wp_strip_all_tags( $title ),
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not save. WhatsApp still works.', 'tabarak-core' ) ), 500 );
		}
		foreach ( $data as $k => $v ) {
			update_post_meta( $post_id, '_tl_' . $k, $v );
		}
		update_post_meta( $post_id, '_tl_status', 'new' );
		update_post_meta( $post_id, '_tl_product_id', $pid );

		$to = self::biz( 'email' );
		if ( $to && is_email( $to ) ) {
			$lines = array();
			foreach ( self::lead_fields() as $k => $label ) {
				if ( '' !== $data[ $k ] ) {
					$lines[] = $label . ': ' . $data[ $k ];
				}
			}
			$lines[] = '';
			$lines[] = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
			wp_mail( $to, sprintf( 'New funnel order %s - %s', $ref, $name ), implode( "\n", $lines ) );
		}
		wp_send_json_success( array( 'ref' => $ref ) );
	}

	public static function lead_statuses() {
		return array(
			'new'       => __( 'New', 'tabarak-core' ),
			'contacted' => __( 'Contacted', 'tabarak-core' ),
			'won'       => __( 'Sold', 'tabarak-core' ),
			'lost'      => __( 'Not sold', 'tabarak-core' ),
		);
	}

	public static function lead_metabox() {
		add_meta_box( 'tabarak_lead_details', __( 'Order details', 'tabarak-core' ), array( __CLASS__, 'lead_metabox_html' ), 'tabarak_lead', 'normal', 'high' );
	}

	public static function lead_metabox_html( $post ) {
		wp_nonce_field( 'tabarak_lead_status', 'tabarak_lead_nonce' );
		$status = get_post_meta( $post->ID, '_tl_status', true );
		echo '<table class="widefat striped"><tbody>';
		foreach ( self::lead_fields() as $k => $label ) {
			$v = (string) get_post_meta( $post->ID, '_tl_' . $k, true );
			if ( '' === $v ) {
				continue;
			}
			if ( 'phone' === $k ) {
				$digits = preg_replace( '/\D/', '', $v );
				$v      = '<a href="tel:+' . esc_attr( $digits ) . '">' . esc_html( $v ) . '</a> &nbsp; <a class="button button-small" target="_blank" rel="noopener" href="' . esc_url( 'https://wa.me/' . $digits ) . '">WhatsApp</a>';
			} else {
				$v = nl2br( esc_html( $v ) );
			}
			echo '<tr><th style="width:180px">' . esc_html( $label ) . '</th><td>' . $v . '</td></tr>'; // phpcs:ignore -- escaped above.
		}
		echo '</tbody></table><p><label><strong>' . esc_html__( 'Status', 'tabarak-core' ) . '</strong> <select name="tabarak_lead_status">';
		foreach ( self::lead_statuses() as $k => $label ) {
			echo '<option value="' . esc_attr( $k ) . '" ' . selected( $status, $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label></p>';
	}

	public static function lead_save( $post_id ) {
		if ( ! isset( $_POST['tabarak_lead_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tabarak_lead_nonce'] ) ), 'tabarak_lead_status' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$s = isset( $_POST['tabarak_lead_status'] ) ? sanitize_key( wp_unslash( $_POST['tabarak_lead_status'] ) ) : 'new';
		if ( isset( self::lead_statuses()[ $s ] ) ) {
			update_post_meta( $post_id, '_tl_status', $s );
		}
	}

	public static function lead_columns( $cols ) {
		return array(
			'cb'        => isset( $cols['cb'] ) ? $cols['cb'] : '',
			'title'     => __( 'Order', 'tabarak-core' ),
			'tl_phone'  => __( 'Phone', 'tabarak-core' ),
			'tl_where'  => __( 'Location', 'tabarak-core' ),
			'tl_status' => __( 'Status', 'tabarak-core' ),
			'date'      => __( 'Date', 'tabarak-core' ),
		);
	}

	public static function lead_column( $col, $post_id ) {
		if ( 'tl_phone' === $col ) {
			$v = (string) get_post_meta( $post_id, '_tl_phone', true );
			$d = preg_replace( '/\D/', '', $v );
			echo '<a href="tel:+' . esc_attr( $d ) . '">' . esc_html( $v ) . '</a> &middot; <a target="_blank" rel="noopener" href="' . esc_url( 'https://wa.me/' . $d ) . '">WhatsApp</a>';
		} elseif ( 'tl_where' === $col ) {
			echo esc_html( trim( get_post_meta( $post_id, '_tl_location', true ) . ' ' . get_post_meta( $post_id, '_tl_area', true ) ) );
		} elseif ( 'tl_status' === $col ) {
			$s  = (string) get_post_meta( $post_id, '_tl_status', true );
			$st = self::lead_statuses();
			echo esc_html( isset( $st[ $s ] ) ? $st[ $s ] : $s );
		}
	}

	/* ------------------------------------------------------------------
	 * Admin: Tabarak > Sales Funnels
	 * ------------------------------------------------------------------ */
	public static function admin_menu() {
		add_submenu_page( 'tabarak-core', __( 'Sales Funnels', 'tabarak-core' ), __( 'Sales Funnels', 'tabarak-core' ), 'manage_woocommerce', 'tabarak-funnels', array( __CLASS__, 'admin_page' ) );
	}

	public static function admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'tabarak-funnels' ) ) {
			return;
		}
		wp_enqueue_media();
		if ( function_exists( 'WC' ) ) {
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
		wp_enqueue_script( 'tabarak-funnels-admin', TABARAK_CORE_URL . 'assets/js/funnels-admin.js', array( 'jquery' ), TABARAK_CORE_VERSION, true );
		wp_add_inline_style( 'woocommerce_admin_styles', '.tf-admin-table td,.tf-admin-table th{vertical-align:middle}.tf-admin-img{max-width:240px;display:block;margin:8px 0;border-radius:8px}.tf-pill{display:inline-block;padding:2px 10px;border-radius:99px;font-size:12px;font-weight:600}.tf-pill--on{background:#e8f8ee;color:#137a3a}.tf-pill--off{background:#f1f1f1;color:#777}' );
	}

	public static function admin_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$key  = isset( $_GET['funnel'] ) ? sanitize_key( wp_unslash( $_GET['funnel'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$defs = self::definitions();
		echo '<div class="wrap">';
		if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Funnel saved.', 'tabarak-core' ) . '</p></div>';
		}
		if ( $key && isset( $defs[ $key ] ) ) {
			self::admin_edit( $key );
		} else {
			self::admin_list( $defs );
		}
		echo '</div>';
	}

	private static function admin_list( $defs ) {
		echo '<h1>' . esc_html__( 'Sales Funnels', 'tabarak-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Landing pages for your biggest categories. Click Edit to choose which products appear, change the headline or add a hero image. Empty slots are filled automatically with best sellers and deals.', 'tabarak-core' ) . '</p>';
		echo '<table class="widefat striped tf-admin-table"><thead><tr><th>' . esc_html__( 'Funnel', 'tabarak-core' ) . '</th><th>' . esc_html__( 'Page', 'tabarak-core' ) . '</th><th>' . esc_html__( 'Products picked', 'tabarak-core' ) . '</th><th>' . esc_html__( 'Status', 'tabarak-core' ) . '</th><th></th></tr></thead><tbody>';
		foreach ( array_keys( $defs ) as $k ) {
			$c    = self::get( $k );
			$edit = add_query_arg( array( 'page' => 'tabarak-funnels', 'funnel' => $k ), admin_url( 'admin.php' ) );
			echo '<tr><td><strong><a href="' . esc_url( $edit ) . '">' . esc_html( $c['label'] ) . '</a></strong><br><span class="description">' . esc_html( $c['headline'] ) . '</span></td>';
			echo '<td><a href="' . esc_url( self::url( $k ) ) . '" target="_blank">/' . esc_html( self::slug( $k ) ) . '/</a></td>';
			echo '<td>' . (int) count( (array) $c['products'] ) . ( ! empty( $c['autofill'] ) ? ' <span class="description">' . esc_html__( '+ auto', 'tabarak-core' ) . '</span>' : '' ) . '</td>';
			echo '<td>' . ( ! empty( $c['enabled'] ) ? '<span class="tf-pill tf-pill--on">' . esc_html__( 'Live', 'tabarak-core' ) . '</span>' : '<span class="tf-pill tf-pill--off">' . esc_html__( 'Off', 'tabarak-core' ) . '</span>' ) . '</td>';
			echo '<td><a class="button" href="' . esc_url( $edit ) . '">' . esc_html__( 'Edit', 'tabarak-core' ) . '</a> <a class="button" href="' . esc_url( self::url( $k ) ) . '" target="_blank">' . esc_html__( 'View', 'tabarak-core' ) . '</a></td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function admin_edit( $key ) {
		$c = self::get( $key );
		echo '<h1>' . esc_html( sprintf( /* translators: %s label */ __( 'Edit funnel: %s', 'tabarak-core' ), $c['label'] ) ) . ' <a class="page-title-action" href="' . esc_url( self::url( $key ) ) . '" target="_blank">' . esc_html__( 'View page', 'tabarak-core' ) . '</a></h1>';
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=tabarak-funnels' ) ) . '">&larr; ' . esc_html__( 'All funnels', 'tabarak-core' ) . '</a></p>';
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="tabarak_save_funnel">
			<input type="hidden" name="funnel" value="<?php echo esc_attr( $key ); ?>">
			<?php wp_nonce_field( 'tabarak_save_funnel_' . $key ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php esc_html_e( 'Status', 'tabarak-core' ); ?></th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $c['enabled'] ) ); ?>> <?php esc_html_e( 'Funnel page is live', 'tabarak-core' ); ?></label></td></tr>
				<tr><th scope="row"><label for="tf-products"><?php esc_html_e( 'Products to show', 'tabarak-core' ); ?></label></th><td>
					<select id="tf-products" class="wc-product-search" multiple="multiple" style="width:100%;max-width:720px" name="products[]" data-placeholder="<?php esc_attr_e( 'Search for a product by name or SKU', 'tabarak-core' ); ?>" data-action="woocommerce_json_search_products">
						<?php
						foreach ( (array) $c['products'] as $pid ) {
							$p = wc_get_product( (int) $pid );
							if ( $p ) {
								echo '<option value="' . esc_attr( $p->get_id() ) . '" selected>' . esc_html( wp_strip_all_tags( $p->get_formatted_name() ) ) . '</option>';
							}
						}
						?>
					</select>
					<p class="description"><?php esc_html_e( 'Products appear in the order you add them. The first one gets the "Top pick" ribbon.', 'tabarak-core' ); ?></p>
					<p><label><input type="checkbox" name="autofill" value="1" <?php checked( ! empty( $c['autofill'] ) ); ?>> <?php esc_html_e( 'Fill empty slots with best sellers and deals from this category', 'tabarak-core' ); ?></label></p>
					<p><label><?php esc_html_e( 'Number of products', 'tabarak-core' ); ?> <input type="number" min="4" max="24" name="limit" value="<?php echo esc_attr( (int) $c['limit'] ); ?>" class="small-text"></label></p>
					<p><label><?php esc_html_e( 'Auto-fill: skip products cheaper than KSh', 'tabarak-core' ); ?> <input type="number" min="0" step="100" name="min_price" value="<?php echo esc_attr( (float) $c['min_price'] ); ?>" class="small-text" style="width:110px"></label></p>
					<p><label><?php esc_html_e( 'Auto-fill: skip products whose name contains', 'tabarak-core' ); ?><br><input type="text" class="regular-text" name="exclude" value="<?php echo esc_attr( $c['exclude'] ); ?>" placeholder="remote, mount, cable"></label><br><span class="description"><?php esc_html_e( 'Comma separated. Keeps accessories like remotes and wall mounts out of the funnel. Products you pick yourself are always shown.', 'tabarak-core' ); ?></span></p>
				</td></tr>
				<tr><th scope="row"><label for="tf-headline"><?php esc_html_e( 'Headline', 'tabarak-core' ); ?></label></th><td><input id="tf-headline" type="text" class="large-text" name="headline" value="<?php echo esc_attr( $c['headline'] ); ?>"></td></tr>
				<tr><th scope="row"><label for="tf-sub"><?php esc_html_e( 'Sub-headline', 'tabarak-core' ); ?></label></th><td><textarea id="tf-sub" class="large-text" rows="2" name="sub"><?php echo esc_textarea( $c['sub'] ); ?></textarea></td></tr>
				<tr><th scope="row"><label for="tf-promo"><?php esc_html_e( 'Promo bar (optional)', 'tabarak-core' ); ?></label></th><td><input id="tf-promo" type="text" class="large-text" name="promo" value="<?php echo esc_attr( $c['promo'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. This week only: free delivery within Nairobi CBD', 'tabarak-core' ); ?>"></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Hero image (optional)', 'tabarak-core' ); ?></th><td>
					<input type="url" class="large-text tf-image-url" name="image" value="<?php echo esc_attr( $c['image'] ); ?>" placeholder="https://">
					<p><button type="button" class="button tf-image-pick"><?php esc_html_e( 'Choose image', 'tabarak-core' ); ?></button> <button type="button" class="button-link tf-image-clear"><?php esc_html_e( 'Remove', 'tabarak-core' ); ?></button></p>
					<?php if ( $c['image'] ) : ?><img class="tf-admin-img" src="<?php echo esc_url( $c['image'] ); ?>" alt=""><?php endif; ?>
					<p class="description"><?php esc_html_e( 'Leave empty to show the first three product photos as a collage. Recommended: 1200 x 900 px, product on a clean background.', 'tabarak-core' ); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="tf-bands"><?php esc_html_e( 'Budget buttons', 'tabarak-core' ); ?></label></th><td><textarea id="tf-bands" class="large-text code" rows="5" name="bands"><?php echo esc_textarea( $c['bands'] ); ?></textarea><p class="description"><?php esc_html_e( 'One per line: Label | min price | max price. Use 0 for no limit.', 'tabarak-core' ); ?></p></td></tr>
				<tr><th scope="row"><label for="tf-faqs"><?php esc_html_e( 'Extra FAQs', 'tabarak-core' ); ?></label></th><td><textarea id="tf-faqs" class="large-text" rows="5" name="faqs"><?php echo esc_textarea( $c['faqs'] ); ?></textarea><p class="description"><?php esc_html_e( 'One per line: Question | Answer. Delivery, payment and warranty FAQs are added automatically.', 'tabarak-core' ); ?></p></td></tr>
			</table>
			<?php submit_button( __( 'Save funnel', 'tabarak-core' ) ); ?>
		</form>
		<?php
	}

	public static function save() {
		$key = isset( $_POST['funnel'] ) ? sanitize_key( wp_unslash( $_POST['funnel'] ) ) : '';
		if ( ! $key || ! current_user_can( 'manage_woocommerce' ) || ! isset( self::definitions()[ $key ] ) ) {
			wp_die( esc_html__( 'Not allowed.', 'tabarak-core' ) );
		}
		check_admin_referer( 'tabarak_save_funnel_' . $key );
		$all = get_option( self::OPTION, array() );
		if ( ! is_array( $all ) ) {
			$all = array();
		}
		$products = isset( $_POST['products'] ) ? array_values( array_unique( array_filter( array_map( 'absint', (array) wp_unslash( $_POST['products'] ) ) ) ) ) : array();
		$all[ $key ] = array(
			'enabled'  => empty( $_POST['enabled'] ) ? 0 : 1,
			'autofill' => empty( $_POST['autofill'] ) ? 0 : 1,
			'products' => $products,
			'limit'    => isset( $_POST['limit'] ) ? max( 4, min( 24, absint( $_POST['limit'] ) ) ) : 12,
			'min_price' => isset( $_POST['min_price'] ) ? (string) absint( $_POST['min_price'] ) : '',
			'exclude'  => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '',
			'headline' => isset( $_POST['headline'] ) ? sanitize_text_field( wp_unslash( $_POST['headline'] ) ) : '',
			'sub'      => isset( $_POST['sub'] ) ? sanitize_textarea_field( wp_unslash( $_POST['sub'] ) ) : '',
			'promo'    => isset( $_POST['promo'] ) ? sanitize_text_field( wp_unslash( $_POST['promo'] ) ) : '',
			'image'    => isset( $_POST['image'] ) ? esc_url_raw( wp_unslash( $_POST['image'] ) ) : '',
			'bands'    => isset( $_POST['bands'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bands'] ) ) : '',
			'faqs'     => isset( $_POST['faqs'] ) ? sanitize_textarea_field( wp_unslash( $_POST['faqs'] ) ) : '',
		);
		update_option( self::OPTION, $all, false );
		delete_transient( 'tabarak_funnel_ids_v2_' . $key );
		wp_safe_redirect( add_query_arg( array( 'page' => 'tabarak-funnels', 'funnel' => $key, 'saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
}

Tabarak_Funnels::init();

endif;
