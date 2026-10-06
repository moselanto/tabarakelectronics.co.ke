<?php
/**
 * Tabarak Electronics Child - UX layer (v1.11.0).
 *
 * SAFETY: read-only for products and categories. Nothing here creates,
 * edits or deletes shop data.
 *
 * Contents:
 *  1. Assets (ux.css, ux.js)
 *  2. Primary mega navigation + mobile drawer extras
 *  3. Content pages: hero header, broken table/code wrapper fix, duplicate title fix
 *  4. Hot deals filter (?tab_sale=1)
 *  5. Single product: info chips, help box, description fallback, sticky bar data
 *  6. Empty cart and 404 helpers
 *  7. Cache flushing
 *
 * @package Tabarak_Electronics_Child
 */

if ( \! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * 1. Assets
 * ------------------------------------------------------------------ */
if ( \! function_exists( 'tabarak_ux_assets' ) ) {
	function tabarak_ux_assets() {
		$ver = wp_get_theme()->get( 'Version' );
		wp_enqueue_style( 'tabarak-ux', get_stylesheet_directory_uri() . '/assets/css/ux.css', array( 'tabarak-child' ), $ver );
		wp_enqueue_script( 'tabarak-ux', get_stylesheet_directory_uri() . '/assets/js/ux.js', array(), $ver, true );
		wp_localize_script(
			'tabarak-ux',
			'tabarakUx',
			array(
				'i18n' => array(
					'increase' => __( 'Increase quantity', 'tabarak-electronics-child' ),
					'decrease' => __( 'Decrease quantity', 'tabarak-electronics-child' ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'tabarak_ux_assets', 30 );

/* ------------------------------------------------------------------
 * Small helpers
 * ------------------------------------------------------------------ */

/** Permalink of a published page by slug, or ''. */
if ( \! function_exists( 'tabarak_ux_page_url' ) ) {
	function tabarak_ux_page_url( $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			return get_permalink( $page );
		}
		return '';
	}
}

/** First matching product category link for a list of candidate names. */
if ( \! function_exists( 'tabarak_ux_cat_url' ) ) {
	function tabarak_ux_cat_url( $names ) {
		if ( \! taxonomy_exists( 'product_cat' ) || \! function_exists( 'tabarak_child_resolve_term_ids' ) ) {
			return '';
		}
		$ids = tabarak_child_resolve_term_ids( (array) $names );
		if ( empty( $ids ) ) {
			return '';
		}
		$link = get_term_link( (int) $ids[0], 'product_cat' );
		return is_wp_error( $link ) ? '' : $link;
	}
}

/** Shop URL. */
if ( \! function_exists( 'tabarak_ux_shop_url' ) ) {
	function tabarak_ux_shop_url() {
		return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	}
}

/** Inline SVG icons used by the UX layer. */
if ( \! function_exists( 'tabarak_ux_icon' ) ) {
	function tabarak_ux_icon( $name ) {
		$p = array(
			'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
			'chev'     => '<path d="M6 9l6 6 6-6"/>',
			'fire'     => '<path d="M12 3c1 3 4 5 4 9a4 4 0 01-8 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-5 0-8z"/>',
			'tag'      => '<path d="M3 12V4a1 1 0 011-1h8l9 9-9 9-9-9z"/><circle cx="8" cy="8" r="1.5"/>',
			'truck'    => '<path d="M3 6h11v9H3zM14 9h4l3 3v3h-7"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
			'shield'   => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
			'phone'    => '<path d="M6 3h3l2 5-2.5 1.5a11 11 0 005 5L16 12l5 2v3a2 2 0 01-2 2A16 16 0 013 5a2 2 0 013-2z"/>',
			'chat'     => '<path d="M4 5h16v11H8l-4 4z"/>',
			'help'     => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 015 0c0 2-2.5 2-2.5 4"/><circle cx="12" cy="17" r=".6"/>',
			'pin'      => '<path d="M12 21s-7-6-7-11a7 7 0 0114 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
			'check'    => '<path d="M5 12l4 4 10-10"/>',
			'box'      => '<path d="M3 7l9-4 9 4v10l-9 4-9-4z"/><path d="M3 7l9 4 9-4M12 11v10"/>',
			'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		);
		if ( \! isset( $p[ $name ] ) ) {
			return '';
		}
		return '<svg class="tux-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p[ $name ] . '</svg>';
	}
}

/* ------------------------------------------------------------------
 * 2. Primary mega navigation
 * ------------------------------------------------------------------ */

/** Quick category links shown directly in the bar. */
if ( \! function_exists( 'tabarak_ux_quick_cats' ) ) {
	function tabarak_ux_quick_cats() {
		return apply_filters(
			'tabarak_ux_quick_cats',
			array(
				__( 'TVs', 'tabarak-electronics-child' )           => array( 'Televisions', 'Television', 'TVs' ),
				__( 'Fridges', 'tabarak-electronics-child' )       => array( 'Refrigerators', 'Fridges' ),
				__( 'Cookers', 'tabarak-electronics-child' )       => array( 'Cookers & Ovens', 'Cookers' ),
				__( 'Washing', 'tabarak-electronics-child' )       => array( 'Washing Machines & Dryers', 'Washing Machines' ),
				__( 'Audio', 'tabarak-electronics-child' )         => array( 'Audio & Home Theatre', 'Audio' ),
				__( 'Small Kitchen', 'tabarak-electronics-child' ) => array( 'Blenders', 'Microwaves', 'Kettles' ),
			)
		);
	}
}

/** Build (and cache) the mega navigation HTML. */
if ( \! function_exists( 'tabarak_ux_mega_nav_html' ) ) {
	function tabarak_ux_mega_nav_html() {
		$key  = 'tabarak_ux_mega_nav_v1';
		$html = get_transient( $key );
		if ( is_string( $html ) && '' \!== $html ) {
			return $html;
		}

		$shop  = tabarak_ux_shop_url();
		$deals = add_query_arg( 'tab_sale', '1', $shop );
		$cats  = array();
		if ( taxonomy_exists( 'product_cat' ) ) {
			$cats = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'orderby'    => 'count',
					'order'      => 'DESC',
					'number'     => 30,
					'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
				)
			);
			if ( is_wp_error( $cats ) ) {
				$cats = array();
			}
		}
		$btax   = function_exists( 'tabarak_child_brand_tax' ) ? tabarak_child_brand_tax() : '';
		$brands = ( $btax && function_exists( 'tabarak_child_ordered_brands' ) ) ? tabarak_child_ordered_brands( $btax, 24 ) : array();

		ob_start();
		?>
		<ul class="tux-nav__list">
			<?php if ( \! empty( $cats ) ) : ?>
			<li class="tux-nav__item tux-nav__item--mega">
				<button type="button" class="tux-nav__trigger tux-nav__trigger--all" aria-expanded="false" aria-controls="tux-mega-cats">
					<?php echo tabarak_ux_icon( 'grid' ); // phpcs:ignore ?>
					<span><?php esc_html_e( 'All Categories', 'tabarak-electronics-child' ); ?></span>
					<?php echo tabarak_ux_icon( 'chev' ); // phpcs:ignore ?>
				</button>
				<div class="tux-mega" id="tux-mega-cats" hidden>
					<div class="tux-mega__inner">
						<div class="tux-mega__cols">
							<p class="tux-mega__label"><?php esc_html_e( 'Shop by category', 'tabarak-electronics-child' ); ?></p>
							<ul class="tux-mega__grid">
								<?php foreach ( $cats as $c ) : ?>
									<?php $l = get_term_link( $c ); if ( is_wp_error( $l ) ) { continue; } ?>
									<li><a href="<?php echo esc_url( $l ); ?>"><span><?php echo esc_html( $c->name ); ?></span><span class="tux-count"><?php echo esc_html( number_format_i18n( $c->count ) ); ?></span></a></li>
								<?php endforeach; ?>
							</ul>
							<a class="tux-mega__all" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'Browse all products', 'tabarak-electronics-child' ); ?> <?php echo tabarak_ux_icon( 'arrow' ); // phpcs:ignore ?></a>
						</div>
						<aside class="tux-mega__promo">
							<p class="tux-mega__eyebrow"><?php esc_html_e( 'Hot deals', 'tabarak-electronics-child' ); ?></p>
							<p class="tux-mega__title"><?php esc_html_e( 'Save on TVs, fridges and kitchen appliances', 'tabarak-electronics-child' ); ?></p>
							<p class="tux-mega__text"><?php esc_html_e( 'Genuine brands with warranty, delivered countrywide.', 'tabarak-electronics-child' ); ?></p>
							<a class="tux-btn tux-btn--accent" href="<?php echo esc_url( $deals ); ?>"><?php esc_html_e( 'Shop deals', 'tabarak-electronics-child' ); ?></a>
						</aside>
					</div>
				</div>
			</li>
			<?php endif; ?>

			<?php if ( \! empty( $brands ) ) : ?>
			<li class="tux-nav__item tux-nav__item--mega">
				<button type="button" class="tux-nav__trigger" aria-expanded="false" aria-controls="tux-mega-brands">
					<span><?php esc_html_e( 'Brands', 'tabarak-electronics-child' ); ?></span>
					<?php echo tabarak_ux_icon( 'chev' ); // phpcs:ignore ?>
				</button>
				<div class="tux-mega tux-mega--brands" id="tux-mega-brands" hidden>
					<div class="tux-mega__inner">
						<div class="tux-mega__cols">
							<p class="tux-mega__label"><?php esc_html_e( 'Top brands', 'tabarak-electronics-child' ); ?></p>
							<ul class="tux-brandgrid">
								<?php foreach ( $brands as $b ) : ?>
									<?php
									$l = get_term_link( $b );
									if ( is_wp_error( $l ) ) {
										continue;
									}
									$img = function_exists( 'tabarak_child_brand_image_id' ) ? tabarak_child_brand_image_id( $b->term_id ) : 0;
									?>
									<li><a href="<?php echo esc_url( $l ); ?>" title="<?php echo esc_attr( $b->name ); ?>">
										<?php
										if ( $img ) {
											echo wp_get_attachment_image( $img, 'thumbnail', false, array( 'loading' => 'lazy', 'alt' => esc_attr( $b->name ) ) );
										} else {
											echo '<span>' . esc_html( $b->name ) . '</span>';
										}
										?>
									</a></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
			</li>
			<?php endif; ?>

			<li class="tux-nav__item"><a class="tux-nav__link tux-nav__link--deal" href="<?php echo esc_url( $deals ); ?>"><?php echo tabarak_ux_icon( 'fire' ); // phpcs:ignore ?><span><?php esc_html_e( 'Hot Deals', 'tabarak-electronics-child' ); ?></span></a></li>

			<?php
			foreach ( tabarak_ux_quick_cats() as $label => $names ) {
				$u = tabarak_ux_cat_url( $names );
				if ( $u ) {
					echo '<li class="tux-nav__item tux-nav__item--quick"><a class="tux-nav__link" href="' . esc_url( $u ) . '">' . esc_html( $label ) . '</a></li>';
				}
			}
			?>
		</ul>
		<?php
		$html = (string) ob_get_clean();
		set_transient( $key, $html, HOUR_IN_SECONDS );
		return $html;
	}
}

/** Right-hand helper links (not cached: cheap and page aware). */
if ( \! function_exists( 'tabarak_ux_nav_help' ) ) {
	function tabarak_ux_nav_help() {
		$links = array(
			'delivery-installation' => array( __( 'Delivery', 'tabarak-electronics-child' ), 'truck' ),
			'warranty-service'      => array( __( 'Warranty', 'tabarak-electronics-child' ), 'shield' ),
			'contact-us'            => array( __( 'Contact', 'tabarak-electronics-child' ), 'chat' ),
		);
		echo '<ul class="tux-nav__help">';
		foreach ( $links as $slug => $info ) {
			$u = tabarak_ux_page_url( $slug );
			if ( $u ) {
				echo '<li><a href="' . esc_url( $u ) . '">' . tabarak_ux_icon( $info[1] ) . '<span>' . esc_html( $info[0] ) . '</span></a></li>'; // phpcs:ignore
			}
		}
		$phone = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'phone' ) : '';
		if ( $phone ) {
			echo '<li class="tux-nav__call"><a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . tabarak_ux_icon( 'phone' ) . '<span><small>' . esc_html__( 'Call to order', 'tabarak-electronics-child' ) . '</small>' . esc_html( $phone ) . '</span></a></li>'; // phpcs:ignore
		}
		echo '</ul>';
	}
}

/** Full primary navigation (desktop). Called from header.php. */
if ( \! function_exists( 'tabarak_child_mega_nav' ) ) {
	function tabarak_child_mega_nav() {
		?>
		<nav id="site-navigation" class="main-navigation tux-nav" aria-label="<?php esc_attr_e( 'Primary', 'tabarak-electronics-child' ); ?>">
			<div class="tabarak-container tux-nav__inner">
				<?php echo tabarak_ux_mega_nav_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?>
				<?php tabarak_ux_nav_help(); ?>
			</div>
		</nav>
		<?php
	}
}

/** Extra links in the mobile drawer (deals, help pages, contact). */
if ( \! function_exists( 'tabarak_child_drawer_extras' ) ) {
	function tabarak_child_drawer_extras() {
		$shop  = tabarak_ux_shop_url();
		$items = array(
			array( add_query_arg( 'tab_sale', '1', $shop ), __( 'Hot Deals', 'tabarak-electronics-child' ), 'fire' ),
		);
		foreach ( array(
			'delivery-installation' => array( __( 'Delivery & Installation', 'tabarak-electronics-child' ), 'truck' ),
			'warranty-service'      => array( __( 'Warranty & Service', 'tabarak-electronics-child' ), 'shield' ),
			'faqs'                  => array( __( 'FAQs', 'tabarak-electronics-child' ), 'help' ),
			'contact-us'            => array( __( 'Contact us', 'tabarak-electronics-child' ), 'chat' ),
		) as $slug => $info ) {
			$u = tabarak_ux_page_url( $slug );
			if ( $u ) {
				$items[] = array( $u, $info[0], $info[1] );
			}
		}
		echo '<ul class="tux-drawer-links">';
		foreach ( $items as $it ) {
			echo '<li><a href="' . esc_url( $it[0] ) . '">' . tabarak_ux_icon( $it[2] ) . '<span>' . esc_html( $it[1] ) . '</span></a></li>'; // phpcs:ignore
		}
		echo '</ul>';
		$phone = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'phone' ) : '';
		$wa    = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'whatsapp' ) : '';
		if ( $phone || $wa ) {
			echo '<div class="tux-drawer-help"><p>' . esc_html__( 'Need help choosing?', 'tabarak-electronics-child' ) . '</p><div class="tux-drawer-help__btns">';
			if ( $phone ) {
				echo '<a class="tux-btn tux-btn--ghost" href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . tabarak_ux_icon( 'phone' ) . esc_html__( 'Call', 'tabarak-electronics-child' ) . '</a>'; // phpcs:ignore
			}
			if ( $wa ) {
				echo '<a class="tux-btn tux-btn--wa" href="' . esc_url( 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $wa ) ) . '" target="_blank" rel="noopener nofollow">' . tabarak_ux_icon( 'chat' ) . esc_html__( 'WhatsApp', 'tabarak-electronics-child' ) . '</a>'; // phpcs:ignore
			}
			echo '</div></div>';
		}
	}
}

/* ------------------------------------------------------------------
 * 3. Content pages
 * ------------------------------------------------------------------ */

/** Short intro line under the page title, per slug. Filterable. */
if ( \! function_exists( 'tabarak_ux_page_intro' ) ) {
	function tabarak_ux_page_intro( $post ) {
		$map = array(
			'about-us'              => __( 'Genuine, warranty-backed electronics and home appliances for homes and businesses across Kenya.', 'tabarak-electronics-child' ),
			'contact-us'            => __( 'Call, WhatsApp, email or visit us at Nairobi Sky Mall. We reply fast during working hours.', 'tabarak-electronics-child' ),
			'faqs'                  => __( 'Quick answers about orders, payments, delivery, installation and returns.', 'tabarak-electronics-child' ),
			'delivery-installation' => __( 'Fast delivery in Nairobi and countrywide, with professional installation on request.', 'tabarak-electronics-child' ),
			'warranty-service'      => __( 'Every product is covered by the manufacturer warranty. Here is how to make a claim.', 'tabarak-electronics-child' ),
			'returns-refund-policy' => __( 'Simple 7-day returns, exchanges and refunds.', 'tabarak-electronics-child' ),
			'shipping-policy'       => __( 'Delivery areas, timelines and charges.', 'tabarak-electronics-child' ),
			'terms-and-conditions'  => __( 'The terms that apply when you shop with Tabarak Electronics.', 'tabarak-electronics-child' ),
			'privacy-policy'        => __( 'How we collect, use and protect your information.', 'tabarak-electronics-child' ),
		);
		$map  = apply_filters( 'tabarak_ux_page_intros', $map );
		$slug = $post instanceof WP_Post ? $post->post_name : '';
		if ( has_excerpt( $post ) ) {
			return get_the_excerpt( $post );
		}
		return isset( $map[ $slug ] ) ? $map[ $slug ] : '';
	}
}

/** Is this one of the WooCommerce app pages (cart, checkout, account)? */
if ( \! function_exists( 'tabarak_ux_is_wc_page' ) ) {
	function tabarak_ux_is_wc_page() {
		return ( function_exists( 'is_cart' ) && is_cart() ) || ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'is_account_page' ) && is_account_page() );
	}
}

/**
 * Clean up page content:
 *  - Unwrap shortcode HTML that was pasted inside a table cell + <code> block
 *    (this made About and Contact render in a monospace font inside a box).
 *  - Remove a leading heading that repeats the page title (Warranty page).
 */
if ( \! function_exists( 'tabarak_ux_clean_content' ) ) {
	function tabarak_ux_clean_content( $content ) {
		if ( \! is_singular( 'page' ) || \! in_the_loop() || \! is_main_query() ) {
			return $content;
		}
		$content = preg_replace_callback(
			'#<figure class="wp-block-table[^"]*">\s*<table[^>]*>\s*<tbody>\s*<tr>\s*<td>\s*<code>(.*?)</code>\s*</td>\s*</tr>\s*</tbody>\s*</table>\s*</figure>#s',
			function ( $m ) {
				if ( false === strpos( $m[1], 'tabarak-' ) ) {
					return $m[0];
				}
				$inner = preg_replace( '#<br\s*/?>\s*(?=<)#', '', $m[1] );
				$inner = preg_replace( '#(?<=>)\s*<br\s*/?>#', '', $inner );
				return '<div class="tux-unwrapped">' . $inner . '</div>';
			},
			$content
		);
		$title = trim( wp_strip_all_tags( get_the_title() ) );
		if ( '' \!== $title ) {
			$content = preg_replace_callback(
				'#^\s*<h[12][^>]*>(.*?)</h[12]>#s',
				function ( $m ) use ( $title ) {
					return ( 0 === strcasecmp( trim( wp_strip_all_tags( $m[1] ) ), $title ) ) ? '' : $m[0];
				},
				$content,
				1
			);
		}
		return $content;
	}
}
add_filter( 'the_content', 'tabarak_ux_clean_content', 20 );

/** Page hero header used by page.php. */
if ( \! function_exists( 'tabarak_ux_page_hero' ) ) {
	function tabarak_ux_page_hero() {
		$post  = get_post();
		$intro = tabarak_ux_page_intro( $post );
		?>
		<header class="tux-hero">
			<div class="tabarak-container">
				<nav class="tux-crumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'tabarak-electronics-child' ); ?>">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'tabarak-electronics-child' ); ?></a>
					<span aria-hidden="true">/</span>
					<span aria-current="page"><?php echo esc_html( get_the_title() ); ?></span>
				</nav>
				<h1 class="tux-hero__title"><?php echo esc_html( get_the_title() ); ?></h1>
				<?php if ( $intro ) : ?>
					<p class="tux-hero__intro"><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>
			</div>
		</header>
		<?php
	}
}

/** Help strip shown under content pages. */
if ( \! function_exists( 'tabarak_ux_help_strip' ) ) {
	function tabarak_ux_help_strip() {
		$phone = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'phone' ) : '';
		$wa    = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'whatsapp' ) : '';
		?>
		<section class="tux-helpstrip">
			<div class="tux-helpstrip__txt">
				<p class="tux-helpstrip__title"><?php esc_html_e( 'Still have a question?', 'tabarak-electronics-child' ); ?></p>
				<p><?php esc_html_e( 'Our team helps you choose, order and arrange delivery.', 'tabarak-electronics-child' ); ?></p>
			</div>
			<div class="tux-helpstrip__btns">
				<?php if ( $phone ) : ?>
					<a class="tux-btn tux-btn--ghost-light" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo tabarak_ux_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( $phone ); ?></a>
				<?php endif; ?>
				<?php if ( $wa ) : ?>
					<a class="tux-btn tux-btn--wa" href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $wa ) ); ?>" target="_blank" rel="noopener nofollow"><?php echo tabarak_ux_icon( 'chat' ); // phpcs:ignore ?><?php esc_html_e( 'WhatsApp us', 'tabarak-electronics-child' ); ?></a>
				<?php endif; ?>
				<a class="tux-btn tux-btn--accent" href="<?php echo esc_url( tabarak_ux_shop_url() ); ?>"><?php esc_html_e( 'Continue shopping', 'tabarak-electronics-child' ); ?></a>
			</div>
		</section>
		<?php
	}
}

/* ------------------------------------------------------------------
 * 4. Hot deals filter: ?tab_sale=1 on the shop and category pages
 * ------------------------------------------------------------------ */
if ( \! function_exists( 'tabarak_ux_sale_filter' ) ) {
	function tabarak_ux_sale_filter( $q ) {
		if ( is_admin() || \! $q->is_main_query() || empty( $_GET['tab_sale'] ) || \! function_exists( 'wc_get_product_ids_on_sale' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$is_ctx = ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );
		if ( \! $is_ctx ) {
			return;
		}
		$ids = array_map( 'intval', (array) wc_get_product_ids_on_sale() );
		$q->set( 'post__in', empty( $ids ) ? array( 0 ) : $ids );
	}
}
add_action( 'pre_get_posts', 'tabarak_ux_sale_filter', 20 );

/** Keep tab_sale when the filter form submits, and label the shop hero. */
if ( \! function_exists( 'tabarak_ux_sale_hidden_field' ) ) {
	function tabarak_ux_sale_hidden_field() {
		if ( \! empty( $_GET['tab_sale'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="tux-chipbar"><span class="tux-chip tux-chip--deal">' . tabarak_ux_icon( 'fire' ) . esc_html__( 'Showing hot deals only', 'tabarak-electronics-child' ) . '</span> <a class="tux-chip tux-chip--clear" href="' . esc_url( remove_query_arg( 'tab_sale' ) ) . '">' . esc_html__( 'Show all products', 'tabarak-electronics-child' ) . '</a></div>'; // phpcs:ignore
		}
	}
}
add_action( 'woocommerce_before_shop_loop', 'tabarak_ux_sale_hidden_field', 4 );

/* ------------------------------------------------------------------
 * 5. Single product
 * ------------------------------------------------------------------ */

/** Brand, SKU and stock chips under the product title. */
if ( \! function_exists( 'tabarak_ux_product_chips' ) ) {
	function tabarak_ux_product_chips() {
		global $product;
		if ( \! is_object( $product ) ) {
			return;
		}
		$chips = array();
		$btax  = function_exists( 'tabarak_child_brand_tax' ) ? tabarak_child_brand_tax() : '';
		if ( $btax ) {
			$terms = get_the_terms( $product->get_id(), $btax );
			if ( $terms && \! is_wp_error( $terms ) ) {
				$l = get_term_link( $terms[0] );
				$chips[] = '<a class="tux-chip tux-chip--brand" href="' . esc_url( is_wp_error( $l ) ? '#' : $l ) . '">' . esc_html( $terms[0]->name ) . '</a>';
			}
		}
		if ( $product->is_in_stock() ) {
			$chips[] = '<span class="tux-chip tux-chip--ok">' . tabarak_ux_icon( 'check' ) . esc_html__( 'In stock', 'tabarak-electronics-child' ) . '</span>'; // phpcs:ignore
		} else {
			$chips[] = '<span class="tux-chip tux-chip--out">' . esc_html__( 'Out of stock', 'tabarak-electronics-child' ) . '</span>';
		}
		if ( $product->get_sku() ) {
			$chips[] = '<span class="tux-chip">' . esc_html__( 'SKU', 'tabarak-electronics-child' ) . ': ' . esc_html( $product->get_sku() ) . '</span>';
		}
		echo '<div class="tux-chips">' . implode( '', $chips ) . '</div>'; // phpcs:ignore
	}
}
add_action( 'woocommerce_single_product_summary', 'tabarak_ux_product_chips', 6 );

/** "Need help?" box at the end of the summary. */
if ( \! function_exists( 'tabarak_ux_product_help' ) ) {
	function tabarak_ux_product_help() {
		$phone = function_exists( 'tabarak_get_business' ) ? tabarak_get_business( 'phone' ) : '';
		if ( \! $phone ) {
			return;
		}
		echo '<div class="tux-pdp-help">' . tabarak_ux_icon( 'help' ) . '<div><strong>' . esc_html__( 'Need advice before you buy?', 'tabarak-electronics-child' ) . '</strong><span>' . esc_html__( 'Call our specialists on', 'tabarak-electronics-child' ) . ' <a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a></span></div></div>'; // phpcs:ignore
	}
}
add_action( 'woocommerce_single_product_summary', 'tabarak_ux_product_help', 45 );

/**
 * Description fallback: many imported products only repeat their name.
 * Show a structured overview instead (category, brand, attributes, service).
 */
if ( \! function_exists( 'tabarak_ux_description_is_thin' ) ) {
	function tabarak_ux_description_is_thin( $product ) {
		$desc = trim( wp_strip_all_tags( (string) $product->get_description() ) );
		$name = trim( wp_strip_all_tags( (string) $product->get_name() ) );
		return '' === $desc || 0 === strcasecmp( $desc, $name ) || strlen( $desc ) < 60;
	}
}

if ( \! function_exists( 'tabarak_ux_description_fallback' ) ) {
	function tabarak_ux_description_fallback() {
		global $product;
		if ( \! is_object( $product ) ) {
			return;
		}
		$desc = trim( (string) $product->get_description() );
		echo '<h2>' . esc_html__( 'Overview', 'tabarak-electronics-child' ) . '</h2>';
		if ( '' \!== $desc && 0 \!== strcasecmp( trim( wp_strip_all_tags( $desc ) ), trim( $product->get_name() ) ) ) {
			echo wp_kses_post( wpautop( $desc ) );
		}
		$rows = array();
		$cats = get_the_terms( $product->get_id(), 'product_cat' );
		if ( $cats && \! is_wp_error( $cats ) ) {
			$rows[ __( 'Category', 'tabarak-electronics-child' ) ] = esc_html( $cats[0]->name );
		}
		$btax = function_exists( 'tabarak_child_brand_tax' ) ? tabarak_child_brand_tax() : '';
		if ( $btax ) {
			$b = get_the_terms( $product->get_id(), $btax );
			if ( $b && \! is_wp_error( $b ) ) {
				$rows[ __( 'Brand', 'tabarak-electronics-child' ) ] = esc_html( $b[0]->name );
			}
		}
		if ( $product->get_sku() ) {
			$rows[ __( 'Model / SKU', 'tabarak-electronics-child' ) ] = esc_html( $product->get_sku() );
		}
		foreach ( $product->get_attributes() as $attr ) {
			if ( \! $attr->get_visible() ) {
				continue;
			}
			$label = wc_attribute_label( $attr->get_name() );
			$vals  = $attr->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options();
			$rows[ $label ] = esc_html( implode( ', ', (array) $vals ) );
		}
		$rows[ __( 'Warranty', 'tabarak-electronics-child' ) ]  = esc_html__( 'Manufacturer warranty', 'tabarak-electronics-child' );
		$rows[ __( 'Delivery', 'tabarak-electronics-child' ) ]  = esc_html__( 'Next-day in Nairobi, 1-5 days countrywide', 'tabarak-electronics-child' );
		$rows[ __( 'Payment', 'tabarak-electronics-child' ) ]   = esc_html__( 'M-Pesa, bank transfer or cash', 'tabarak-electronics-child' );

		echo '<p>' . sprintf(
			/* translators: %s: product name. */
			esc_html__( 'The %s is a genuine, brand-new product supplied by Tabarak Electronics with full manufacturer warranty. Order online, on WhatsApp or by phone, and we will confirm availability and arrange delivery.', 'tabarak-electronics-child' ),
			'<strong>' . esc_html( $product->get_name() ) . '</strong>'
		) . '</p>';
		echo '<table class="tux-spec"><tbody>';
		foreach ( $rows as $k => $v ) {
			echo '<tr><th scope="row">' . esc_html( $k ) . '</th><td>' . $v . '</td></tr>'; // phpcs:ignore -- values escaped above.
		}
		echo '</tbody></table>';
	}
}

if ( \! function_exists( 'tabarak_ux_product_tabs' ) ) {
	function tabarak_ux_product_tabs( $tabs ) {
		global $product;
		if ( \! is_object( $product ) ) {
			return $tabs;
		}
		if ( tabarak_ux_description_is_thin( $product ) ) {
			$tabs['description'] = array(
				'title'    => __( 'Overview', 'tabarak-electronics-child' ),
				'priority' => 10,
				'callback' => 'tabarak_ux_description_fallback',
			);
		}
		$dpage = tabarak_ux_page_url( 'delivery-installation' );
		$tabs['tux_delivery'] = array(
			'title'    => __( 'Delivery & Warranty', 'tabarak-electronics-child' ),
			'priority' => 40,
			'callback' => function () use ( $dpage ) {
				echo '<h2>' . esc_html__( 'Delivery & Warranty', 'tabarak-electronics-child' ) . '</h2>';
				echo '<ul class="tux-ticks">';
				echo '<li>' . esc_html__( 'Same-day or next-day delivery in Nairobi on orders placed before 2:00 PM.', 'tabarak-electronics-child' ) . '</li>';
				echo '<li>' . esc_html__( 'Countrywide delivery through trusted couriers, usually within 1 to 5 business days.', 'tabarak-electronics-child' ) . '</li>';
				echo '<li>' . esc_html__( 'Installation available for TVs, cookers, hoods and washing machines.', 'tabarak-electronics-child' ) . '</li>';
				echo '<li>' . esc_html__( 'Manufacturer warranty on every genuine product, plus 7-day returns on eligible items.', 'tabarak-electronics-child' ) . '</li>';
				echo '</ul>';
				if ( $dpage ) {
					echo '<p><a class="tux-link" href="' . esc_url( $dpage ) . '">' . esc_html__( 'Read the full delivery and installation guide', 'tabarak-electronics-child' ) . '</a></p>';
				}
			},
		);
		return $tabs;
	}
}
add_filter( 'woocommerce_product_tabs', 'tabarak_ux_product_tabs', 98 );

/** Sticky add-to-cart bar (mobile and desktop on scroll). */
if ( \! function_exists( 'tabarak_ux_sticky_bar' ) ) {
	function tabarak_ux_sticky_bar() {
		global $product;
		if ( \! is_object( $product ) || \! $product->is_purchasable() || \! $product->is_in_stock() ) {
			return;
		}
		$img = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
		?>
		<div class="tux-sticky" hidden>
			<div class="tabarak-container tux-sticky__inner">
				<?php if ( $img ) : ?><img class="tux-sticky__img" src="<?php echo esc_url( $img ); ?>" alt="" width="48" height="48" loading="lazy"><?php endif; ?>
				<div class="tux-sticky__info">
					<span class="tux-sticky__name"><?php echo esc_html( $product->get_name() ); ?></span>
					<span class="tux-sticky__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				</div>
				<button type="button" class="tux-btn tux-btn--accent tux-sticky__btn"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
			</div>
		</div>
		<?php
	}
}
add_action( 'woocommerce_after_single_product', 'tabarak_ux_sticky_bar', 5 );

/* ------------------------------------------------------------------
 * 6. Empty cart helpers
 * ------------------------------------------------------------------ */
if ( \! function_exists( 'tabarak_ux_empty_cart_cats' ) ) {
	function tabarak_ux_empty_cart_cats() {
		if ( \! taxonomy_exists( 'product_cat' ) ) {
			return;
		}
		$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 8, 'exclude' => array( (int) get_option( 'default_product_cat' ) ) ) );
		if ( is_wp_error( $cats ) || empty( $cats ) ) {
			return;
		}
		echo '<div class="tux-emptycats"><p>' . esc_html__( 'Popular categories', 'tabarak-electronics-child' ) . '</p><ul>';
		foreach ( $cats as $c ) {
			$l = get_term_link( $c );
			if ( \! is_wp_error( $l ) ) {
				echo '<li><a href="' . esc_url( $l ) . '">' . esc_html( $c->name ) . '</a></li>';
			}
		}
		echo '</ul></div>';
	}
}
add_action( 'woocommerce_cart_is_empty', 'tabarak_ux_empty_cart_cats', 20 );

/* ------------------------------------------------------------------
 * 7. Cache flushing
 * ------------------------------------------------------------------ */
if ( \! function_exists( 'tabarak_ux_flush_caches' ) ) {
	function tabarak_ux_flush_caches() {
		delete_transient( 'tabarak_ux_mega_nav_v1' );
		delete_transient( 'tabarak_home_rows_html' );
	}
}
foreach ( array( 'customize_save_after', 'created_product_cat', 'edited_product_cat', 'delete_product_cat', 'woocommerce_update_product', 'woocommerce_product_set_stock_status' ) as $tabarak_ux_hook ) {
	add_action( $tabarak_ux_hook, 'tabarak_ux_flush_caches' );
}
unset( $tabarak_ux_hook );

/* Make the reCAPTCHA badge sit above the mobile bottom bar instead of covering "Cart". */
/* (handled in ux.css) */
