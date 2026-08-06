<?php
/**
 * Frontend archive, filters and single-property presentation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Volks_Propstack_Frontend {
	const SOLD_QUERY_VAR = 'volks_sold_gallery';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_routes' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 120 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_archive_query' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_sold_single' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_classes' ) );
		add_filter( 'volks_main_nav_items', array( __CLASS__, 'add_navigation_item' ) );
		add_filter( 'redirect_canonical', array( __CLASS__, 'redirect_canonical' ) );
		add_filter( 'document_title_parts', array( __CLASS__, 'document_title_parts' ) );
		add_action( 'wp_head', array( __CLASS__, 'sold_gallery_head' ), 2 );
		add_filter( 'wpseo_title', array( __CLASS__, 'seo_title' ) );
		add_filter( 'wpseo_metadesc', array( __CLASS__, 'seo_description' ) );
		add_filter( 'wpseo_canonical', array( __CLASS__, 'seo_canonical' ) );
		add_filter( 'wpseo_opengraph_image', array( __CLASS__, 'seo_image' ) );
		add_filter( 'wpseo_twitter_image', array( __CLASS__, 'seo_image' ) );
	}

	public static function register_routes() {
		add_rewrite_rule( '^verkauft/?$', 'index.php?' . self::SOLD_QUERY_VAR . '=1', 'top' );
	}

	public static function query_vars( $vars ) {
		$vars[] = self::SOLD_QUERY_VAR;
		return $vars;
	}

	public static function is_sold_gallery() {
		return '1' === (string) get_query_var( self::SOLD_QUERY_VAR );
	}

	public static function sold_gallery_url() {
		return home_url( '/verkauft/' );
	}

	public static function redirect_canonical( $redirect_url ) {
		return self::is_sold_gallery() ? false : $redirect_url;
	}

	public static function document_title_parts( $parts ) {
		if ( self::is_sold_gallery() ) {
			$parts['title'] = 'Verkaufte Immobilien';
		}
		return $parts;
	}

	public static function sold_gallery_head() {
		if ( ! self::is_sold_gallery() ) {
			return;
		}
		echo '<link rel="canonical" href="' . esc_url( self::sold_gallery_url() ) . '">' . "\n";
	}

	public static function enqueue() {
		if ( ! is_post_type_archive( Volks_Propstack_Post_Type::POST_TYPE ) && ! is_singular( Volks_Propstack_Post_Type::POST_TYPE ) && ! self::is_sold_gallery() ) {
			return;
		}

		$style_path = VOLKS_PROPSTACK_PATH . 'assets/css/properties.css';
		$script_path = VOLKS_PROPSTACK_PATH . 'assets/js/properties.js';
		wp_enqueue_style(
			'volks-propstack-properties',
			VOLKS_PROPSTACK_URL . 'assets/css/properties.css',
			array( 'volks-main' ),
			is_readable( $style_path ) ? (string) filemtime( $style_path ) : VOLKS_PROPSTACK_VERSION
		);
		wp_enqueue_script(
			'volks-propstack-properties',
			VOLKS_PROPSTACK_URL . 'assets/js/properties.js',
			array(),
			is_readable( $script_path ) ? (string) filemtime( $script_path ) : VOLKS_PROPSTACK_VERSION,
			true
		);
	}

	public static function template_include( $template ) {
		if ( self::is_sold_gallery() ) {
			$candidate = VOLKS_PROPSTACK_PATH . 'templates/sold-gallery.php';
			return is_readable( $candidate ) ? $candidate : $template;
		}
		if ( is_post_type_archive( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			$candidate = VOLKS_PROPSTACK_PATH . 'templates/archive-volks_property.php';
			return is_readable( $candidate ) ? $candidate : $template;
		}
		if ( is_singular( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			$candidate = VOLKS_PROPSTACK_PATH . 'templates/single-volks_property.php';
			return is_readable( $candidate ) ? $candidate : $template;
		}
		return $template;
	}

	public static function body_classes( $classes ) {
		if ( self::is_sold_gallery() ) {
			$classes[] = 'volks-sold-gallery';
		}
		if ( is_post_type_archive( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			$classes[] = 'volks-property-archive';
		}
		if ( is_singular( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			$classes[] = 'volks-property-single';
		}
		return $classes;
	}

	/**
	 * Place Immobilien after Kaufen in the existing theme navigation.
	 */
	public static function add_navigation_item( $items ) {
		$item = array(
			'label'     => 'Immobilien',
			'url'       => get_post_type_archive_link( Volks_Propstack_Post_Type::POST_TYPE ),
			'post_type' => Volks_Propstack_Post_Type::POST_TYPE,
		);
		array_splice( $items, 2, 0, array( $item ) );
		array_splice(
			$items,
			4,
			0,
			array(
				array(
					'label'  => 'Verkauft',
					'url'    => self::sold_gallery_url(),
					'active' => self::is_sold_gallery(),
				),
			)
		);
		return $items;
	}

	public static function redirect_sold_single() {
		if ( ! is_singular( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( 'sold' !== self::meta( $post_id, 'inventory_state' ) ) {
			return;
		}
		$remote_id = self::meta( $post_id, 'id', $post_id );
		wp_safe_redirect( self::sold_gallery_url() . '#verkauft-' . sanitize_html_class( (string) $remote_id ), 302 );
		exit;
	}

	public static function filter_archive_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			return;
		}

		$query->set( 'posts_per_page', 12 );
		$query->set( 'post_status', 'publish' );
		$query->set( 'ignore_sticky_posts', true );

		$meta_query = array(
			array(
				'key'     => '_vps_inventory_state',
				'value'   => 'active',
				'compare' => '=',
			),
		);
		$marketing  = self::filter_value( 'vermarktung' );
		if ( in_array( $marketing, array( 'BUY', 'RENT' ), true ) ) {
			$meta_query[] = array( 'key' => '_vps_marketing_type', 'value' => $marketing );
		}

		$numeric_filters = array(
			'preis_von'    => array( '_vps_price', '>=' ),
			'preis_bis'    => array( '_vps_price', '<=' ),
			'flaeche_von'  => array( '_vps_living_space', '>=' ),
			'zimmer_von'   => array( '_vps_rooms', '>=' ),
		);
		foreach ( $numeric_filters as $parameter => $definition ) {
			$value = self::filter_number( $parameter );
			if ( null !== $value ) {
				$meta_query[] = array(
					'key'     => $definition[0],
					'value'   => $value,
					'compare' => $definition[1],
					'type'    => 'NUMERIC',
				);
			}
		}
		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}

		$tax_query = array();
		$type_slug = sanitize_title( self::filter_value( 'typ' ) );
		if ( '' !== $type_slug ) {
			$tax_query[] = array( 'taxonomy' => Volks_Propstack_Post_Type::TAX_TYPE, 'field' => 'slug', 'terms' => $type_slug );
		}
		$location_slug = sanitize_title( self::filter_value( 'ort' ) );
		if ( '' !== $location_slug ) {
			$tax_query[] = array( 'taxonomy' => Volks_Propstack_Post_Type::TAX_LOCATION, 'field' => 'slug', 'terms' => $location_slug );
		}
		if ( ! empty( $tax_query ) ) {
			$query->set( 'tax_query', $tax_query );
		}

		$sort = self::filter_value( 'sortierung' );
		switch ( $sort ) {
			case 'preis_asc':
				$query->set( 'meta_key', '_vps_price' );
				$query->set( 'orderby', array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ) );
				break;
			case 'preis_desc':
				$query->set( 'meta_key', '_vps_price' );
				$query->set( 'orderby', array( 'meta_value_num' => 'DESC', 'title' => 'ASC' ) );
				break;
			case 'flaeche_desc':
				$query->set( 'meta_key', '_vps_living_space' );
				$query->set( 'orderby', array( 'meta_value_num' => 'DESC', 'title' => 'ASC' ) );
				break;
			default:
				// WordPress modification dates depend on when each environment
				// imported the object. Propstack's timestamp is identical across
				// local/live and therefore keeps pagination and card order stable.
				$query->set( 'meta_key', '_vps_updated_at' );
				$query->set( 'orderby', array( 'meta_value' => 'DESC', 'title' => 'ASC' ) );
		}
	}

	public static function filter_value( $key ) {
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public static function filter_number( $key ) {
		$value = self::filter_value( $key );
		if ( '' === $value ) {
			return null;
		}
		$value = str_replace( ',', '.', preg_replace( '/\s+/', '', $value ) );
		return is_numeric( $value ) && (float) $value >= 0 ? (float) $value : null;
	}

	public static function meta( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, '_vps_' . sanitize_key( $key ), true );
		return '' === $value ? $default : $value;
	}

	public static function format_price( $post_id ) {
		$price     = self::meta( $post_id, 'price', null );
		$marketing = self::meta( $post_id, 'marketing_type' );
		if ( null === $price || '' === $price || ! is_numeric( $price ) || (float) $price <= 0 ) {
			return 'Preis auf Anfrage';
		}
		$suffix = 'RENT' === $marketing ? ' € / Monat' : ' €';
		return number_format_i18n( (float) $price, 0 ) . $suffix;
	}

	public static function marketing_label( $post_id ) {
		return 'RENT' === self::meta( $post_id, 'marketing_type' ) ? 'Miete' : 'Kauf';
	}

	public static function images( $post_id ) {
		$images = self::meta( $post_id, 'images', array() );
		return is_array( $images ) ? $images : array();
	}

	public static function render_card( $post_id ) {
		$images = self::images( $post_id );
		$image  = $images[0]['url'] ?? '';
		$status = self::meta( $post_id, 'status' );
		$type   = self::meta( $post_id, 'type_label', 'Immobilie' );
		$city   = self::meta( $post_id, 'city' );
		$area   = self::meta( $post_id, 'living_space', null );
		$rooms  = self::meta( $post_id, 'rooms', null );
		?>
		<article class="vps-card">
			<a class="vps-card__link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" aria-label="<?php echo esc_attr( get_the_title( $post_id ) ); ?> ansehen">
				<div class="vps-card__media">
					<?php if ( $image ) : ?>
						<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( get_the_title( $post_id ) ); ?>" loading="lazy" decoding="async">
					<?php else : ?>
						<div class="vps-card__placeholder" aria-hidden="true"><span>volksimmobilien</span></div>
					<?php endif; ?>
					<?php if ( $status ) : ?><span class="vps-badge"><?php echo esc_html( $status ); ?></span><?php endif; ?>
				</div>
				<div class="vps-card__body">
					<p class="vps-card__type"><?php echo esc_html( $type . ' · ' . self::marketing_label( $post_id ) ); ?></p>
					<h2 class="vps-card__title"><?php echo esc_html( get_the_title( $post_id ) ); ?></h2>
					<?php if ( $city ) : ?><p class="vps-card__location"><?php echo esc_html( $city ); ?></p><?php endif; ?>
					<div class="vps-card__facts">
						<?php if ( is_numeric( $area ) && (float) $area > 0 ) : ?><span><?php echo esc_html( number_format_i18n( (float) $area, 0 ) ); ?> m²</span><?php endif; ?>
						<?php if ( is_numeric( $rooms ) && (float) $rooms > 0 ) : ?><span><?php echo esc_html( number_format_i18n( (float) $rooms, 1 ) ); ?> Zimmer</span><?php endif; ?>
					</div>
					<p class="vps-card__price"><?php echo esc_html( self::format_price( $post_id ) ); ?></p>
					<span class="vps-card__cta">Details ansehen <span aria-hidden="true">→</span></span>
				</div>
			</a>
		</article>
		<?php
	}

	public static function seo_title( $title ) {
		if ( self::is_sold_gallery() ) {
			return 'Verkaufte Immobilien | volksimmobilien';
		}
		if ( is_post_type_archive( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			return 'Aktuelle Immobilienangebote | volksimmobilien';
		}
		if ( is_singular( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			return get_the_title() . ' | volksimmobilien';
		}
		return $title;
	}

	public static function seo_description( $description ) {
		if ( self::is_sold_gallery() ) {
			return 'Ausgewählte erfolgreich verkaufte Immobilien von volksimmobilien – mit Bildergalerien unserer vermittelten Objekte.';
		}
		if ( is_post_type_archive( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			return 'Aktuelle Immobilienangebote von volksimmobilien entdecken und nach Ort, Preis, Fläche, Zimmern und Immobilientyp filtern.';
		}
		if ( is_singular( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			$excerpt = get_the_excerpt();
			return $excerpt ? wp_strip_all_tags( $excerpt ) : $description;
		}
		return $description;
	}

	public static function seo_canonical( $canonical ) {
		return self::is_sold_gallery() ? self::sold_gallery_url() : $canonical;
	}

	public static function seo_image( $image ) {
		if ( is_singular( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			$images = self::images( get_queried_object_id() );
			return $images[0]['url'] ?? $image;
		}
		return $image;
	}
}
