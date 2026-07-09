<?php
/**
 * Open Graph / link preview (WhatsApp, Slack, iMessage, …).
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default share image path (under Fotos/) per leadwerk source key.
 *
 * @return array<string,string>
 */
function volks_get_og_image_path_map() {
	return array(
		'volks-home-v1'       => 'Fotos/Slider1_optimiert.webp',
		'volks-kaufen-v1'     => 'Fotos/volksimmobilien_kaufen.webp',
		'volks-verkaufen-v1'  => 'Fotos/volksimmobilien_verkaufen.webp',
		'volks-bewerten-v1'   => 'Fotos/Vertrauen_optimiert.webp',
		'volks-ausland-v1'    => 'Fotos/Zadar.webp',
		'volks-mallorca-v1'   => 'Fotos/Mallorca 1.webp',
		'volks-impressum-v1'  => 'Fotos/Slider1_optimiert.webp',
		'volks-datenschutz-v1' => 'Fotos/Slider1_optimiert.webp',
		'volks-danke-v1'      => 'Fotos/Slider1_optimiert.webp',
		'volks-404-v1'        => 'Fotos/Slider1_optimiert.webp',
	);
}

/**
 * Resolve one OG image path to a public absolute URL.
 *
 * @param string $path Relative path (Fotos/…) or absolute URL.
 * @return string
 */
function volks_resolve_og_image_url( $path ) {
	$path = trim( html_entity_decode( (string) $path, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( '' === $path ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $path ) ) {
		return esc_url_raw( $path );
	}

	if ( function_exists( 'volks_resolve_media_url' ) ) {
		$url = volks_resolve_media_url( $path );
		if ( is_string( $url ) && preg_match( '#^https?://#i', $url ) ) {
			return esc_url_raw( $url );
		}
	}

	$path = ltrim( str_replace( '\\', '/', $path ), '/' );
	if ( function_exists( 'volks_encode_source_url_path' ) ) {
		$encoded = volks_encode_source_url_path( $path );
	} else {
		$encoded = implode( '/', array_map( 'rawurlencode', explode( '/', $path ) ) );
	}

	return esc_url_raw( home_url( '/' . $encoded ) );
}

/**
 * OG image URL for the current or given page.
 *
 * @param int $post_id Post ID (0 = current).
 * @return string
 */
function volks_get_page_og_image_url( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	if ( $post_id <= 0 ) {
		return volks_resolve_og_image_url( volks_get_og_image_path_map()['volks-home-v1'] ?? 'Fotos/Slider1_optimiert.webp' );
	}

	$stored_id = (int) get_post_meta( $post_id, '_yoast_wpseo_opengraph-image-id', true );
	if ( $stored_id > 0 && function_exists( 'volks_attachment_url' ) ) {
		$url = volks_attachment_url( $stored_id );
		if ( '' !== $url ) {
			return esc_url_raw( $url );
		}
	}

	$stored_url = trim( (string) get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true ) );
	if ( '' !== $stored_url ) {
		return volks_resolve_og_image_url( $stored_url );
	}

	$import_path = trim( (string) get_post_meta( $post_id, 'leadwerk_og_image_path', true ) );
	if ( '' !== $import_path ) {
		return volks_resolve_og_image_url( $import_path );
	}

	$source_key = sanitize_key( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) );
	$map        = volks_get_og_image_path_map();
	if ( '' !== $source_key && isset( $map[ $source_key ] ) ) {
		return volks_resolve_og_image_url( $map[ $source_key ] );
	}

	return volks_resolve_og_image_url( $map['volks-home-v1'] ?? 'Fotos/Slider1_optimiert.webp' );
}

/**
 * Attachment ID for Yoast OG image from relative source path.
 *
 * @param string $path Relative Fotos path.
 * @return int
 */
function volks_get_og_attachment_id_for_path( $path ) {
	$path = trim( (string) $path );
	if ( '' === $path ) {
		return 0;
	}

	if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
		return (int) Leadwerk_Volks_Media::get_attachment_id( $path );
	}

	if ( function_exists( 'volks_get_attachment_id_by_source_path' ) ) {
		return (int) volks_get_attachment_id_by_source_path( $path );
	}

	return 0;
}

/**
 * Yoast: fallback OG image when none configured in admin.
 *
 * @param string $image Current image URL.
 * @return string
 */
function volks_filter_yoast_opengraph_image( $image ) {
	if ( is_string( $image ) && '' !== trim( $image ) ) {
		return $image;
	}

	return volks_get_page_og_image_url();
}
add_filter( 'wpseo_opengraph_image', 'volks_filter_yoast_opengraph_image' );

/**
 * Yoast: Twitter card image fallback.
 *
 * @param string $image Current image URL.
 * @return string
 */
function volks_filter_yoast_twitter_image( $image ) {
	if ( is_string( $image ) && '' !== trim( $image ) ) {
		return $image;
	}

	return volks_get_page_og_image_url();
}
add_filter( 'wpseo_twitter_image', 'volks_filter_yoast_twitter_image' );

/**
 * Without Yoast: print basic OG tags in head.
 *
 * @return void
 */
function volks_print_open_graph_tags_fallback() {
	if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) ) {
		return;
	}

	if ( ! is_singular() && ! is_front_page() ) {
		return;
	}

	$post_id = (int) get_queried_object_id();
	$title   = wp_get_document_title();
	$desc    = $post_id ? trim( (string) get_post_meta( $post_id, 'leadwerk_meta_description', true ) ) : '';
	if ( '' === $desc && $post_id ) {
		$desc = trim( wp_strip_all_tags( get_post_field( 'post_excerpt', $post_id ) ) );
	}
	$url   = $post_id ? get_permalink( $post_id ) : home_url( '/' );
	$image = volks_get_page_og_image_url( $post_id );

	if ( '' === $image ) {
		return;
	}

	echo '<meta property="og:locale" content="de_DE" />' . "\n";
	echo '<meta property="og:site_name" content="volksimmobilien" />' . "\n";
	echo '<meta property="og:type" content="website" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( wp_strip_all_tags( $title ) ) . '" />' . "\n";
	if ( '' !== $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
	}
	echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
}
add_action( 'wp_head', 'volks_print_open_graph_tags_fallback', 5 );

/**
 * Resolve OG image path for import (HTML meta or default map).
 *
 * @param string $source_key   leadwerk_source_key.
 * @param string $og_from_html og:image content from static HTML.
 * @return string Relative Fotos path or empty.
 */
function volks_resolve_og_image_path_for_import( $source_key, $og_from_html = '' ) {
	$og_from_html = trim( html_entity_decode( (string) $og_from_html, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( '' !== $og_from_html ) {
		if ( preg_match( '#^https?://[^/]+/(.+)$#i', $og_from_html, $m ) ) {
			return ltrim( rawurldecode( $m[1] ), '/' );
		}
		return ltrim( str_replace( '\\', '/', $og_from_html ), '/' );
	}

	$map = volks_get_og_image_path_map();
	$source_key = sanitize_key( (string) $source_key );

	return isset( $map[ $source_key ] ) ? $map[ $source_key ] : ( $map['volks-home-v1'] ?? '' );
}
