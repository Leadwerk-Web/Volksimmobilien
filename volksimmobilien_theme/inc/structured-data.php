<?php
/**
 * Schema.org JSON-LD for Volksimmobilien (Rich Snippets).
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Canonical site origin for @id references (no trailing slash).
 *
 * @return string
 */
function volks_schema_site_origin() {
	$home = home_url( '/' );
	$home = is_string( $home ) ? untrailingslashit( $home ) : '';

	return '' !== $home ? $home : 'https://volks.immobilien';
}

/**
 * Organization @id.
 *
 * @return string
 */
function volks_schema_organization_id() {
	return volks_schema_site_origin() . '/#organization';
}

/**
 * RealEstateAgent @id.
 *
 * @return string
 */
function volks_schema_agent_id() {
	return volks_schema_site_origin() . '/#realestateagent';
}

/**
 * Base postal address for Organization / RealEstateAgent.
 *
 * @return array<string,mixed>
 */
function volks_schema_postal_address() {
	return array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => 'Würmersheimer Straße 6',
		'addressLocality' => 'Au am Rhein',
		'postalCode'      => '76474',
		'addressRegion'   => 'Baden-Württemberg',
		'addressCountry'  => 'DE',
	);
}

/**
 * Default German cities for regional pages.
 *
 * @return array<int,array<string,string>>
 */
function volks_schema_default_area_served_cities() {
	return array(
		array( '@type' => 'City', 'name' => 'Heidelberg' ),
		array( '@type' => 'City', 'name' => 'Baden-Baden' ),
		array( '@type' => 'City', 'name' => 'Karlsruhe' ),
		array( '@type' => 'City', 'name' => 'Bruchsal' ),
	);
}

/**
 * Page-specific RealEstateAgent overrides (mirrors static HTML JSON-LD).
 *
 * @return array<string,array<string,mixed>>
 */
function volks_schema_realestate_agent_page_map() {
	return array(
		'volks-home-v1'      => array(
			'description' => 'Immobilienmakler von Heidelberg bis Baden-Baden. Persönlich vor Ort, ehrliche Bewertung, klarer Vermarktungsprozess.',
			'areaServed'  => volks_schema_default_area_served_cities(),
			'with_address' => true,
		),
		'volks-kaufen-v1'    => array(
			'description' => 'Immobilie kaufen von Heidelberg bis Baden-Baden: Immobilienangebote, Suchwunsch und transparente Begleitung beim Kauf.',
			'areaServed'  => volks_schema_default_area_served_cities(),
			'with_address' => true,
		),
		'volks-verkaufen-v1' => array(
			'description' => 'Immobilie verkaufen von Heidelberg bis Baden-Baden: ehrliche Wertermittlung, diskrete Vermarktung und persönliche Begleitung.',
			'areaServed'  => volks_schema_default_area_served_cities(),
			'with_address' => true,
		),
		'volks-bewerten-v1'  => array(
			'description' => 'Immobilienbewertung und Wertermittlung von Heidelberg bis Baden-Baden – ehrlich, mit Substanzblick und persönlicher Begleitung.',
			'areaServed'  => volks_schema_default_area_served_cities(),
			'with_address' => true,
		),
		'volks-ausland-v1'   => array(
			'description' => 'Immobilien auf Mallorca und in Kroatien kaufen und verkaufen – mit persönlicher Begleitung, Marktverständnis und klarem Prozess.',
			'areaServed'  => array(
				array( '@type' => 'Place', 'name' => 'Mallorca' ),
				array( '@type' => 'Place', 'name' => 'Palma de Mallorca' ),
				array( '@type' => 'Place', 'name' => 'Kroatien' ),
				array( '@type' => 'Place', 'name' => 'Zadar' ),
				array( '@type' => 'Place', 'name' => 'Zadar County' ),
			),
			'with_address' => false,
		),
		'volks-mallorca-v1'  => array(
			'description' => 'Immobilien auf Mallorca kaufen und verkaufen – mit persönlicher Begleitung, Marktverständnis und klarem Prozess.',
			'areaServed'  => array(
				array( '@type' => 'Place', 'name' => 'Mallorca' ),
				array( '@type' => 'Place', 'name' => 'Palma de Mallorca' ),
				array( '@type' => 'Place', 'name' => 'Santanyí' ),
				array( '@type' => 'Place', 'name' => 'Pollença' ),
				array( '@type' => 'Place', 'name' => 'Andratx' ),
			),
			'with_address' => false,
		),
	);
}

/**
 * Breadcrumb labels per source key (empty = skip breadcrumb schema).
 *
 * @return array<string,string>
 */
function volks_schema_breadcrumb_label_map() {
	return array(
		'volks-kaufen-v1'      => 'Kaufen',
		'volks-verkaufen-v1'   => 'Verkaufen',
		'volks-bewerten-v1'    => 'Bewerten',
		'volks-ausland-v1'     => 'Immobilien Mallorca & Kroatien',
		'volks-mallorca-v1'    => 'Immobilien Mallorca',
		'volks-impressum-v1'   => 'Impressum',
		'volks-datenschutz-v1' => 'Datenschutz',
		'volks-danke-v1'       => 'Vielen Dank',
	);
}

/**
 * Whether structured data should run for the current request.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function volks_schema_is_volks_page( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	if ( $post_id <= 0 || ! is_singular( 'page' ) ) {
		return false;
	}

	return '' !== sanitize_key( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) );
}

/**
 * Build Organization node.
 *
 * @return array<string,mixed>
 */
function volks_schema_build_organization() {
	$logo = function_exists( 'volks_get_logo_url' ) ? volks_get_logo_url() : '';

	$org = array(
		'@type'        => 'Organization',
		'@id'          => volks_schema_organization_id(),
		'name'         => 'volksimmobilien km GmbH',
		'url'          => home_url( '/' ),
		'email'        => 'info@volksimmobilien.eu',
		'telephone'    => '+491702985141',
		'address'      => volks_schema_postal_address(),
	);

	if ( '' !== $logo ) {
		$org['logo'] = array(
			'@type' => 'ImageObject',
			'url'   => $logo,
		);
	}

	return $org;
}

/**
 * Build RealEstateAgent node for one page.
 *
 * @param int    $post_id    Post ID.
 * @param string $source_key Source key.
 * @return array<string,mixed>
 */
function volks_schema_build_realestate_agent( $post_id, $source_key ) {
	$post_id    = (int) $post_id;
	$source_key = sanitize_key( (string) $source_key );
	$page_url   = get_permalink( $post_id );
	$page_url   = is_string( $page_url ) && '' !== $page_url ? $page_url : home_url( '/' );

	$map      = volks_schema_realestate_agent_page_map();
	$override = isset( $map[ $source_key ] ) ? $map[ $source_key ] : array();

	$stored = get_post_meta( $post_id, 'leadwerk_realestate_schema_json', true );
	if ( is_string( $stored ) && '' !== trim( $stored ) ) {
		$decoded = json_decode( $stored, true );
		if ( is_array( $decoded ) && ( 'RealEstateAgent' === ( $decoded['@type'] ?? '' ) || isset( $decoded['name'] ) ) ) {
			unset( $decoded['@context'] );
			$decoded['@type'] = 'RealEstateAgent';
			$decoded['@id']   = volks_schema_agent_id();
			$decoded['url']   = $page_url;
			if ( empty( $decoded['parentOrganization'] ) ) {
				$decoded['parentOrganization'] = array( '@id' => volks_schema_organization_id() );
			}
			return $decoded;
		}
	}

	$agent = array(
		'@type'              => 'RealEstateAgent',
		'@id'                => volks_schema_agent_id(),
		'name'               => 'volksimmobilien km GmbH',
		'url'                => $page_url,
		'telephone'          => '+491702985141',
		'email'              => 'info@volksimmobilien.eu',
		'parentOrganization' => array( '@id' => volks_schema_organization_id() ),
	);

	if ( ! empty( $override['with_address'] ) ) {
		$agent['address'] = volks_schema_postal_address();
	}

	if ( ! empty( $override['description'] ) ) {
		$agent['description'] = (string) $override['description'];
	}

	if ( ! empty( $override['areaServed'] ) && is_array( $override['areaServed'] ) ) {
		$agent['areaServed'] = $override['areaServed'];
	}

	$image_url = function_exists( 'volks_get_page_og_image_url' ) ? volks_get_page_og_image_url( $post_id ) : '';
	if ( '' !== $image_url ) {
		$agent['image'] = $image_url;
	}

	return $agent;
}

/**
 * Build WebSite schema for the home page.
 *
 * @return array<string,mixed>
 */
function volks_schema_build_website() {
	return array(
		'@type'           => 'WebSite',
		'@id'             => volks_schema_site_origin() . '/#website',
		'url'             => home_url( '/' ),
		'name'            => 'volksimmobilien',
		'publisher'       => array( '@id' => volks_schema_organization_id() ),
		'inLanguage'      => 'de-DE',
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => home_url( '/?s={search_term_string}' ),
			'query-input' => 'required name=search_term_string',
		),
	);
}

/**
 * Build BreadcrumbList for one page.
 *
 * @param int    $post_id    Post ID.
 * @param string $source_key Source key.
 * @return array<string,mixed>|null
 */
function volks_schema_build_breadcrumb( $post_id, $source_key ) {
	$source_key = sanitize_key( (string) $source_key );
	$labels     = volks_schema_breadcrumb_label_map();

	if ( ! isset( $labels[ $source_key ] ) ) {
		return null;
	}

	$page_url = get_permalink( $post_id );
	if ( ! is_string( $page_url ) || '' === $page_url ) {
		return null;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => array(
			array(
				'@type'    => 'ListItem',
				'position' => 1,
				'name'     => 'Startseite',
				'item'     => home_url( '/' ),
			),
			array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => (string) $labels[ $source_key ],
				'item'     => $page_url,
			),
		),
	);
}

/**
 * Load FAQPage schema from post meta (imported from static HTML).
 *
 * @param int $post_id Post ID.
 * @return array<string,mixed>|null
 */
function volks_schema_get_faq_page( $post_id ) {
	$post_id = (int) $post_id;
	$stored  = get_post_meta( $post_id, 'leadwerk_faq_schema_json', true );
	if ( ! is_string( $stored ) || '' === trim( $stored ) ) {
		return null;
	}

	$decoded = json_decode( $stored, true );
	if ( ! is_array( $decoded ) ) {
		return null;
	}

	if ( 'FAQPage' !== ( $decoded['@type'] ?? '' ) || empty( $decoded['mainEntity'] ) || ! is_array( $decoded['mainEntity'] ) ) {
		return null;
	}

	unset( $decoded['@context'] );

	return array(
		'@type'      => 'FAQPage',
		'mainEntity' => $decoded['mainEntity'],
	);
}

/**
 * Collect all JSON-LD graph nodes for one page.
 *
 * @param int $post_id Post ID.
 * @return array<int,array<string,mixed>>
 */
function volks_schema_collect_graph_nodes( $post_id ) {
	$post_id    = (int) $post_id;
	$source_key = sanitize_key( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) );
	if ( '' === $source_key ) {
		return array();
	}

	$graph   = array();
	$graph[] = volks_schema_build_organization();
	$graph[] = volks_schema_build_realestate_agent( $post_id, $source_key );

	if ( 'volks-home-v1' === $source_key ) {
		$graph[] = volks_schema_build_website();
	}

	$breadcrumb = volks_schema_build_breadcrumb( $post_id, $source_key );
	if ( is_array( $breadcrumb ) ) {
		$graph[] = $breadcrumb;
	}

	$faq = volks_schema_get_faq_page( $post_id );
	if ( is_array( $faq ) ) {
		$graph[] = $faq;
	}

	return $graph;
}

/**
 * Print JSON-LD in wp_head.
 *
 * @return void
 */
function volks_output_structured_data() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	$post_id = (int) get_queried_object_id();
	if ( ! volks_schema_is_volks_page( $post_id ) ) {
		return;
	}

	$graph = volks_schema_collect_graph_nodes( $post_id );
	if ( empty( $graph ) ) {
		return;
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => array_values( $graph ),
	);

	$json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	if ( ! is_string( $json ) || '' === $json ) {
		return;
	}

	echo '<script type="application/ld+json">' . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'volks_output_structured_data', 20 );

/**
 * Prevent duplicate Organization / BreadcrumbList from Yoast on Volks pages.
 *
 * @param array<int,mixed> $pieces  Graph pieces.
 * @param mixed            $context Schema context.
 * @return array<int,mixed>
 */
function volks_filter_yoast_schema_pieces( $pieces, $context ) {
	unset( $context );

	if ( ! volks_schema_is_volks_page() ) {
		return $pieces;
	}

	$remove = array(
		'Yoast\WP\SEO\Generators\Schema\Organization',
		'Yoast\WP\SEO\Generators\Schema\Breadcrumb',
	);

	return array_values(
		array_filter(
			$pieces,
			static function ( $piece ) use ( $remove ) {
				if ( ! is_object( $piece ) ) {
					return true;
				}
				foreach ( $remove as $class_name ) {
					if ( is_a( $piece, $class_name ) ) {
						return false;
					}
				}
				return true;
			}
		)
	);
}
add_filter( 'wpseo_schema_graph_pieces', 'volks_filter_yoast_schema_pieces', 11, 2 );

/**
 * Strip FAQ microdata when JSON-LD FAQ is present (avoid duplicate FAQ markup).
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function volks_strip_faq_microdata_markup( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, 'itemprop=' ) ) {
		return $html;
	}

	$html = (string) preg_replace( '/\sitemscope(?:="[^"]*")?/i', '', $html );
	$html = (string) preg_replace( '/\sitemtype="[^"]*"/i', '', $html );
	$html = (string) preg_replace( '/\sitemprop="[^"]*"/i', '', $html );

	return $html;
}

/**
 * Whether FAQ JSON-LD is available for a page.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function volks_page_has_faq_schema( $post_id ) {
	return is_array( volks_schema_get_faq_page( (int) $post_id ) );
}
