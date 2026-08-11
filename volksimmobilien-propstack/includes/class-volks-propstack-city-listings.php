<?php
/**
 * Dynamic city-filtered listing cards for local landing pages.
 *
 * @package Volks_Propstack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replaces listing grids marked with data-volks-city-listings.
 */
final class Volks_Propstack_City_Listings {

	/**
	 * Bootstrap hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'volks_offers_showcase_html', array( __CLASS__, 'maybe_replace_city_listings' ), 5 );
	}

	/**
	 * Replace grids that declare a city filter.
	 *
	 * @param string $html Section HTML.
	 * @return string
	 */
	public static function maybe_replace_city_listings( $html ) {
		$html = (string) $html;
		if ( '' === $html || false === stripos( $html, 'data-volks-city-listings' ) || ! class_exists( 'DOMDocument' ) ) {
			return $html;
		}

		libxml_use_internal_errors( true );
		$document = new DOMDocument( '1.0', 'UTF-8' );
		$loaded   = $document->loadHTML(
			'<?xml encoding="utf-8" ?><div id="vps-city-root">' . $html . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		if ( ! $loaded ) {
			return $html;
		}

		$xpath = new DOMXPath( $document );
		$grids = $xpath->query( '//*[@id="vps-city-root"]//*[@data-volks-city-listings]' );
		if ( ! $grids || 0 === $grids->length ) {
			return $html;
		}

		foreach ( $grids as $grid ) {
			if ( ! $grid instanceof DOMElement ) {
				continue;
			}
			$city  = trim( (string) $grid->getAttribute( 'data-volks-city-listings' ) );
			$limit = absint( $grid->getAttribute( 'data-volks-city-limit' ) );
			if ( '' === $city ) {
				continue;
			}
			if ( $limit < 1 ) {
				$limit = 6;
			}
			if ( $limit > 12 ) {
				$limit = 12;
			}

			while ( $grid->firstChild ) {
				$grid->removeChild( $grid->firstChild );
			}

			$ids = self::query_city_property_ids( $city, $limit );
			if ( empty( $ids ) ) {
				$empty = self::create_empty_state_node( $document, $city );
				$grid->appendChild( $empty );
				continue;
			}

			foreach ( $ids as $index => $post_id ) {
				$card = self::create_listing_card( $document, (int) $post_id, $index );
				if ( $card ) {
					$grid->appendChild( $card );
				}
			}
		}

		$root = $document->getElementById( 'vps-city-root' );
		if ( ! $root instanceof DOMElement ) {
			return $html;
		}

		$output = '';
		foreach ( $root->childNodes as $child ) {
			$output .= $document->saveHTML( $child );
		}

		return '' !== $output ? $output : $html;
	}

	/**
	 * Active published properties for one city (and optional aliases).
	 *
	 * @param string $city  City name, e.g. Durmersheim.
	 * @param int    $limit Max results.
	 * @return int[]
	 */
	public static function query_city_property_ids( $city, $limit = 6 ) {
		$city  = sanitize_text_field( (string) $city );
		$limit = max( 1, min( 12, absint( $limit ) ) );
		if ( '' === $city || ! post_type_exists( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			return array();
		}

		$slugs = self::city_term_slugs( $city );
		if ( empty( $slugs ) ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'              => Volks_Propstack_Post_Type::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => $limit,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => Volks_Propstack_Post_Type::TAX_LOCATION,
						'field'    => 'slug',
						'terms'    => $slugs,
					),
				),
				'meta_query'             => self::active_meta_query(),
			)
		);

		return array_values( array_map( 'absint', $posts ) );
	}

	/**
	 * Nearby active properties excluding an exact city (for optional regional strip).
	 *
	 * @param string   $exclude_city City to exclude.
	 * @param string[] $region_cities Nearby city names.
	 * @param int      $limit        Max results.
	 * @return int[]
	 */
	public static function query_region_property_ids( $exclude_city, array $region_cities, $limit = 3 ) {
		$limit = max( 1, min( 6, absint( $limit ) ) );
		$slugs = array();
		foreach ( $region_cities as $name ) {
			$name = sanitize_text_field( (string) $name );
			if ( '' === $name || strcasecmp( $name, (string) $exclude_city ) === 0 ) {
				continue;
			}
			$slugs = array_merge( $slugs, self::city_term_slugs( $name ) );
		}
		$slugs = array_values( array_unique( array_filter( $slugs ) ) );
		if ( empty( $slugs ) || ! post_type_exists( Volks_Propstack_Post_Type::POST_TYPE ) ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => Volks_Propstack_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => Volks_Propstack_Post_Type::TAX_LOCATION,
						'field'    => 'slug',
						'terms'    => $slugs,
					),
				),
				'meta_query'     => self::active_meta_query(),
			)
		);

		return array_values( array_map( 'absint', $posts ) );
	}

	/**
	 * Resolve taxonomy slugs for a city label.
	 *
	 * @param string $city City name.
	 * @return string[]
	 */
	private static function city_term_slugs( $city ) {
		$aliases = array( $city );
		if ( 0 === strcasecmp( $city, 'Durmersheim' ) ) {
			$aliases[] = 'Würmersheim';
			$aliases[] = 'Wuermersheim';
		}

		$slugs = array();
		foreach ( $aliases as $alias ) {
			$slug = sanitize_title( $alias );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
			$term = get_term_by( 'name', $alias, Volks_Propstack_Post_Type::TAX_LOCATION );
			if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
				$slugs[] = $term->slug;
			}
		}

		return array_values( array_unique( array_filter( $slugs ) ) );
	}

	/**
	 * Active inventory meta query (same rules as showcase).
	 *
	 * @return array<int|string,mixed>
	 */
	private static function active_meta_query() {
		return array(
			'relation' => 'OR',
			array(
				'key'     => '_vps_inventory_state',
				'value'   => 'active',
				'compare' => '=',
			),
			array(
				'key'     => '_vps_inventory_state',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	/**
	 * Empty state when no Durmersheim listings exist.
	 *
	 * @param DOMDocument $document Document.
	 * @param string      $city     City label.
	 * @return DOMElement
	 */
	private static function create_empty_state_node( DOMDocument $document, $city ) {
		$wrap = $document->createElement( 'div' );
		$wrap->setAttribute( 'class', 'listings-empty-state' );

		$title = $document->createElement( 'p' );
		$title->setAttribute( 'class', 'listings-empty-state__title' );
		$title->appendChild( $document->createTextNode( sprintf( 'Aktuell ist kein weiteres Objekt in %s öffentlich verfügbar.', $city ) ) );
		$wrap->appendChild( $title );

		$text = $document->createElement( 'p' );
		$text->setAttribute( 'class', 'listings-empty-state__text' );
		$text->appendChild( $document->createTextNode( 'Hinterlege einen Suchwunsch – wir melden uns persönlich, sobald etwas Passendes verfügbar wird.' ) );
		$wrap->appendChild( $text );

		$actions = $document->createElement( 'div' );
		$actions->setAttribute( 'class', 'section-cta-row' );

		$search = $document->createElement( 'a' );
		$search->setAttribute( 'class', 'btn btn-primary btn-lg' );
		$search_url = function_exists( 'volks_home_section_url' )
			? volks_home_section_url( 'kontakt-formular' )
			: home_url( '/#kontakt-formular' );
		$search->setAttribute( 'href', $search_url );
		$search->appendChild( $document->createTextNode( 'Suchwunsch hinterlegen' ) );
		$actions->appendChild( $search );

		$all = $document->createElement( 'a' );
		$all->setAttribute( 'class', 'btn btn-outline btn-lg' );
		$archive = post_type_exists( Volks_Propstack_Post_Type::POST_TYPE )
			? get_post_type_archive_link( Volks_Propstack_Post_Type::POST_TYPE )
			: home_url( '/immobilien/' );
		$all->setAttribute( 'href', is_string( $archive ) && '' !== $archive ? $archive : home_url( '/immobilien/' ) );
		$all->appendChild( $document->createTextNode( 'Alle Immobilien ansehen' ) );
		$actions->appendChild( $all );

		$wrap->appendChild( $actions );

		return $wrap;
	}

	/**
	 * Build one listing-card matching the Kaufen showcase markup.
	 *
	 * @param DOMDocument $document Document.
	 * @param int         $post_id  Property ID.
	 * @param int         $slot     Slot index.
	 * @return DOMElement|null
	 */
	private static function create_listing_card( DOMDocument $document, $post_id, $slot ) {
		$post_id = absint( $post_id );
		if ( $post_id <= 0 || ! class_exists( 'Volks_Propstack_Frontend' ) ) {
			return null;
		}

		$title  = html_entity_decode( get_the_title( $post_id ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$images = Volks_Propstack_Frontend::images( $post_id );
		$image  = (string) ( $images[0]['url'] ?? '' );
		$status = (string) Volks_Propstack_Frontend::meta( $post_id, 'status' );
		$type   = (string) Volks_Propstack_Frontend::meta( $post_id, 'type_label', 'Immobilie' );
		$zip    = (string) Volks_Propstack_Frontend::meta( $post_id, 'zip_code' );
		$city   = (string) Volks_Propstack_Frontend::meta( $post_id, 'city' );
		$area   = Volks_Propstack_Frontend::meta( $post_id, 'living_space', null );
		$rooms  = Volks_Propstack_Frontend::meta( $post_id, 'rooms', null );
		$plot   = Volks_Propstack_Frontend::meta( $post_id, 'plot_area', null );
		$teaser = get_the_excerpt( $post_id );
		$teaser = $teaser
			? wp_trim_words( html_entity_decode( wp_strip_all_tags( $teaser ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 20, ' …' )
			: 'Aktuelles Immobilienangebot aus Durmersheim.';

		$card = $document->createElement( 'a' );
		$card->setAttribute( 'href', get_permalink( $post_id ) );
		$card->setAttribute( 'class', 'listing-card listing-card--dynamic' );
		$card->setAttribute( 'data-property-id', (string) $post_id );
		$card->setAttribute( 'data-city-slot', (string) $slot );

		$media = $document->createElement( 'div' );
		$media->setAttribute( 'class', 'listing-card-image' );
		if ( $image ) {
			$img = $document->createElement( 'img' );
			$img->setAttribute( 'src', $image );
			$img->setAttribute( 'alt', $title );
			$img->setAttribute( 'loading', 'lazy' );
			$img->setAttribute( 'decoding', 'async' );
			$media->appendChild( $img );
		}
		if ( $status ) {
			$badge = $document->createElement( 'span' );
			$badge->setAttribute( 'class', 'listing-card-badge' );
			$badge->appendChild( $document->createTextNode( $status ) );
			$media->appendChild( $badge );
		}
		$card->appendChild( $media );

		$body = $document->createElement( 'div' );
		$body->setAttribute( 'class', 'listing-card-body' );
		self::append_text( $document, $body, 'span', 'listing-card-type', $type . ' · ' . Volks_Propstack_Frontend::marketing_label( $post_id ) );
		self::append_text( $document, $body, 'h3', '', $title );
		self::append_text( $document, $body, 'p', 'listing-card-teaser', $teaser );

		$location = $document->createElement( 'p' );
		$location->setAttribute( 'class', 'listing-card-location' );
		$svg = $document->createDocumentFragment();
		$svg->appendXML( '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>' );
		$location->appendChild( $svg );
		$location->appendChild( $document->createTextNode( trim( $zip . ' ' . $city ) ?: 'Durmersheim' ) );
		$body->appendChild( $location );

		$details = $document->createElement( 'div' );
		$details->setAttribute( 'class', 'listing-card-details' );
		if ( is_numeric( $area ) && (float) $area > 0 ) {
			self::append_text( $document, $details, 'span', '', number_format_i18n( (float) $area, 0 ) . ' m²' );
		}
		if ( is_numeric( $rooms ) && (float) $rooms > 0 ) {
			$digits = floor( (float) $rooms ) === (float) $rooms ? 0 : 1;
			self::append_text( $document, $details, 'span', '', number_format_i18n( (float) $rooms, $digits ) . ' Zimmer' );
		}
		if ( is_numeric( $plot ) && (float) $plot > 0 ) {
			self::append_text( $document, $details, 'span', '', number_format_i18n( (float) $plot, 0 ) . ' m² Grund' );
		}
		$body->appendChild( $details );
		self::append_text( $document, $body, 'p', 'listing-card-price', Volks_Propstack_Frontend::format_price( $post_id ) );
		self::append_text( $document, $body, 'span', 'listing-card-cta', 'Details ansehen' );
		$card->appendChild( $body );

		return $card;
	}

	/**
	 * Append a text element.
	 *
	 * @param DOMDocument $document Document.
	 * @param DOMNode     $parent   Parent.
	 * @param string      $tag      Tag name.
	 * @param string      $class    Class name.
	 * @param string      $text     Text content.
	 * @return void
	 */
	private static function append_text( DOMDocument $document, DOMNode $parent, $tag, $class, $text ) {
		$element = $document->createElement( $tag );
		if ( $class ) {
			$element->setAttribute( 'class', $class );
		}
		$element->appendChild( $document->createTextNode( (string) $text ) );
		$parent->appendChild( $element );
	}
}
