<?php
/**
 * URL helpers for volksimmobilien navigation.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Source key → relative static HTML filename.
 *
 * @return array<string,string>
 */
function volks_get_source_file_map() {
	return array(
		'volks-home-v1'       => 'index.html',
		'volks-bewerten-v1'   => 'bewerten.html',
		'volks-kaufen-v1'     => 'kaufen.html',
		'volks-verkaufen-v1'  => 'verkaufen.html',
		'volks-mallorca-v1'   => 'mallorca.html',
		'volks-ausland-v1'    => 'ausland.html',
		'volks-impressum-v1'   => 'impressum.html',
		'volks-datenschutz-v1' => 'datenschutz.html',
		'volks-danke-v1'       => 'danke.html',
		'volks-404-v1'         => '404.html',
	);
}

/**
 * Static HTML filename → source key.
 *
 * @return array<string,string>
 */
function volks_get_href_source_key_map() {
	$out = array();
	foreach ( volks_get_source_file_map() as $source_key => $file ) {
		$out[ $file ] = $source_key;
	}
	return $out;
}

/**
 * Find a page ID by leadwerk_source_key.
 *
 * @param string $source_key Source key.
 * @return int
 */
function volks_get_page_id_by_source_key( $source_key ) {
	$source_key = sanitize_key( (string) $source_key );
	if ( '' === $source_key ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => array( 'publish', 'draft', 'private' ),
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => 'leadwerk_source_key',
					'value' => $source_key,
				),
			),
		)
	);

	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Permalink for one volks page source key.
 *
 * @param string $source_key Source key.
 * @param string $fallback   Fallback URL.
 * @return string
 */
function volks_get_page_url( $source_key, $fallback = '#' ) {
	$page_id = volks_get_page_id_by_source_key( $source_key );
	if ( $page_id > 0 ) {
		$url = get_permalink( $page_id );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	return (string) $fallback;
}

/**
 * Whether the current request is the static front page.
 *
 * @return bool
 */
function volks_is_front_page() {
	return is_front_page() || volks_is_source_key( 'volks-home-v1' );
}

/**
 * Whether the current page matches a source key.
 *
 * @param string $source_key Source key.
 * @return bool
 */
function volks_is_source_key( $source_key ) {
	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return false;
	}

	return sanitize_key( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) ) === sanitize_key( (string) $source_key );
}

/**
 * Section IDs available on a Volks landing page (by source key).
 *
 * @param string $source_key Source key.
 * @return string[]
 */
function volks_get_section_ids_for_source_key( $source_key ) {
	$map = array(
		'volks-home-v1'       => array( 'hero', 'einleitung', 'wertermittlung', 'prozess', 'regionen', 'haltung', 'vertrauen', 'kontakt-cta', 'kontakt-formular' ),
		'volks-bewerten-v1'   => array( 'hero', 'einleitung', 'persoenliche-bewertung', 'wertermittlung', 'faq', 'kontakt-cta', 'kontakt-formular' ),
		'volks-kaufen-v1'     => array( 'hero', 'einstieg', 'marktgebiet', 'kaeufergruppen', 'angebote', 'suchwunsch', 'ablauf', 'fragen-kauf', 'region', 'vertrauen-kauf', 'begleitung', 'faq', 'kontakt-abschluss' ),
		'volks-verkaufen-v1'  => array( 'hero', 'einstieg', 'strategie', 'objektarten', 'wertermittlung', 'vertrauen', 'region', 'ablauf', 'diskret', 'faq', 'kontakt-abschluss' ),
		'volks-mallorca-v1'   => array( 'hero', 'einleitung', 'kaufen', 'verkaufen', 'immobilienarten', 'prozess', 'regionen', 'vertrauen', 'kontakt-cta' ),
		'volks-ausland-v1'    => array( 'hero', 'einleitung', 'mallorca', 'kroatien', 'kaufen', 'verkaufen', 'immobilienarten', 'prozess', 'vertrauen', 'kontakt-cta' ),
	);

	$source_key = sanitize_key( (string) $source_key );

	return isset( $map[ $source_key ] ) ? $map[ $source_key ] : array();
}

/**
 * Current page source key (leadwerk_source_key).
 *
 * @return string
 */
function volks_get_current_source_key() {
	$post_id = (int) get_queried_object_id();
	if ( $post_id <= 0 ) {
		return '';
	}

	$key = sanitize_key( (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) );
	if ( '' !== $key ) {
		return $key;
	}

	$slug_map = array(
		'home'        => 'volks-home-v1',
		'bewerten'    => 'volks-bewerten-v1',
		'kaufen'      => 'volks-kaufen-v1',
		'verkaufen'   => 'volks-verkaufen-v1',
		'mallorca'    => 'volks-mallorca-v1',
		'ausland'     => 'volks-ausland-v1',
		'impressum'   => 'volks-impressum-v1',
		'datenschutz' => 'volks-datenschutz-v1',
		'danke'       => 'volks-danke-v1',
		'404'         => 'volks-404-v1',
	);

	$slug = sanitize_key( (string) get_post_field( 'post_name', $post_id ) );

	return isset( $slug_map[ $slug ] ) ? $slug_map[ $slug ] : '';
}

/**
 * Whether the current page contains a section with this HTML id.
 *
 * @param string $anchor_id Section id without hash.
 * @return bool
 */
function volks_current_page_has_section( $anchor_id ) {
	$anchor_id = sanitize_title( (string) $anchor_id );
	if ( '' === $anchor_id ) {
		return false;
	}

	$sections = volks_get_section_ids_for_source_key( volks_get_current_source_key() );

	return in_array( $anchor_id, $sections, true );
}

/**
 * Permalink + hash for a section anchor (current page or homepage).
 *
 * @param string $anchor_id Section id without hash.
 * @return string
 */
function volks_section_url( $anchor_id ) {
	$anchor_id = trim( (string) $anchor_id, "# \t\n\r\0\x0B" );
	if ( 'suchwunsch' === $anchor_id ) {
		$anchor_id = 'kontakt-formular';
		if ( volks_is_front_page() ) {
			return '#' . $anchor_id;
		}
		return trailingslashit( home_url( '/' ) ) . '#' . $anchor_id;
	}
	if ( '' === $anchor_id ) {
		return volks_is_front_page() ? '#' : trailingslashit( home_url( '/' ) );
	}

	if ( volks_current_page_has_section( $anchor_id ) && is_singular( 'page' ) ) {
		$permalink = get_permalink( (int) get_queried_object_id() );
		if ( is_string( $permalink ) && '' !== $permalink ) {
			return trailingslashit( $permalink ) . '#' . $anchor_id;
		}

		return '#' . $anchor_id;
	}

	if ( volks_is_front_page() ) {
		return '#' . $anchor_id;
	}

	return trailingslashit( home_url( '/' ) ) . '#' . $anchor_id;
}

/**
 * URL for a homepage section anchor.
 *
 * @param string $anchor_id Section id without hash.
 * @return string
 */
function volks_home_section_url( $anchor_id ) {
	return volks_section_url( $anchor_id );
}

/**
 * Main navigation items.
 *
 * @return array<int,array{label:string,source_key:string,anchor?:string}>
 */
function volks_get_main_nav_items() {
	return array(
		array(
			'label'      => 'Start',
			'source_key' => 'volks-home-v1',
		),
		array(
			'label'      => 'Kaufen',
			'source_key' => 'volks-kaufen-v1',
		),
		array(
			'label'      => 'Verkaufen',
			'source_key' => 'volks-verkaufen-v1',
		),
		array(
			'label'      => 'Bewerten',
			'source_key' => 'volks-bewerten-v1',
		),
		array(
			'label'      => 'Ausland',
			'source_key' => 'volks-ausland-v1',
		),
		array(
			'label'      => 'Kontakt',
			'source_key' => 'volks-home-v1',
			'anchor'     => 'kontakt-formular',
		),
	);
}

/**
 * Resolved URL for one navigation item.
 *
 * @param array{label:string,source_key:string,anchor?:string} $item Nav item.
 * @return string
 */
function volks_nav_item_url( $item ) {
	$anchor = trim( (string) ( $item['anchor'] ?? '' ) );
	if ( '' !== $anchor ) {
		return volks_section_url( $anchor );
	}

	return volks_get_page_url( (string) ( $item['source_key'] ?? '' ), home_url( '/' ) );
}

/**
 * Whether a nav item matches the current page.
 *
 * @param array{label:string,source_key:string,anchor?:string} $item Nav item.
 * @return bool
 */
function volks_is_nav_item_active( $item ) {
	$anchor = trim( (string) ( $item['anchor'] ?? '' ) );
	if ( '' !== $anchor ) {
		return false;
	}

	return volks_is_source_key( (string) ( $item['source_key'] ?? '' ) );
}

/**
 * Undo esc_url() corruption of static filenames (bewerten.html → http://bewerten.html).
 *
 * @param string $href Raw href.
 * @return string
 */
function volks_unwrap_false_absolute_html_href( $href ) {
	$href = trim( (string) $href );
	if ( '' === $href ) {
		return $href;
	}

	if ( preg_match( '#^https?://([^/:#?]+\.html)(.*)$#i', $href, $matches ) ) {
		$href = $matches[1] . (string) ( $matches[2] ?? '' );
	}

	// http://index.html/ → index.html#…
	$href = preg_replace( '#^([a-z0-9_-]+\.html)/+#i', '$1', $href );

	return $href;
}

/**
 * Resolve one href from static HTML to a WordPress URL.
 *
 * @param string $href Raw href.
 * @return string
 */
function volks_resolve_href( $href ) {
	$href = trim( html_entity_decode( (string) $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	$href = volks_unwrap_false_absolute_html_href( $href );

	if ( '' === $href || '#' === $href ) {
		return $href;
	}

	if ( preg_match( '#^(?:mailto:|tel:)#i', $href ) ) {
		return $href;
	}

	// Real external URLs only (not http://bewerten.html placeholders).
	if ( preg_match( '#^https?://#i', $href ) ) {
		$parsed   = wp_parse_url( $href );
		$fragment = is_array( $parsed ) ? (string) ( $parsed['fragment'] ?? '' ) : '';
		if ( '' !== $fragment ) {
			$site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
			$href_host = wp_parse_url( $href, PHP_URL_HOST );
			$href_path = rtrim( (string) ( $parsed['path'] ?? '' ), '/' );
			$anchor    = sanitize_title( ltrim( $fragment, '#' ) );

			if ( 'suchwunsch' === $anchor ) {
				return volks_section_url( 'kontakt-formular' );
			}

			if ( $site_host && $href_host && strtolower( (string) $site_host ) === strtolower( (string) $href_host ) ) {
				if ( '' === $href_path || '/' === $href_path ) {
					if ( 'prozess' === $anchor && 'volks-verkaufen-v1' === volks_get_current_source_key() ) {
						return trailingslashit( volks_get_page_url( 'volks-verkaufen-v1', home_url( '/' ) ) ) . '#ablauf';
					}
					return volks_section_url( $fragment );
				}

				$slug_key = strtolower( preg_replace( '#\.html$#i', '', basename( $href_path ) ) );
				$slug_map = array(
					'bewerten'  => 'volks-bewerten-v1',
					'kaufen'    => 'volks-kaufen-v1',
					'verkaufen' => 'volks-verkaufen-v1',
					'mallorca'  => 'volks-mallorca-v1',
					'ausland'   => 'volks-ausland-v1',
				);
				if ( isset( $slug_map[ $slug_key ] ) ) {
					$page_key = $slug_map[ $slug_key ];
					if ( 'prozess' === $anchor && 'volks-verkaufen-v1' === $page_key ) {
						return trailingslashit( volks_get_page_url( $page_key, home_url( '/' ) ) ) . '#ablauf';
					}
					if ( '' !== $anchor && in_array( $anchor, volks_get_section_ids_for_source_key( $page_key ), true ) ) {
						return trailingslashit( volks_get_page_url( $page_key, home_url( '/' ) ) ) . '#' . $anchor;
					}
				}
			}
		}
		if ( preg_match( '#^https?://[^/:#?]+\.html#i', $href ) ) {
			$href = volks_unwrap_false_absolute_html_href( $href );
		} else {
			return $href;
		}
	}

	if ( 0 === strpos( $href, '#' ) ) {
		return volks_section_url( substr( $href, 1 ) );
	}

	$normalized = str_replace( '\\', '/', $href );
	$fragment   = '';
	$hash_pos   = strpos( $normalized, '#' );
	if ( false !== $hash_pos ) {
		$fragment   = substr( $normalized, $hash_pos );
		$normalized = substr( $normalized, 0, $hash_pos );
	}

	$fragment_anchor = sanitize_title( ltrim( (string) $fragment, '#' ) );
	if ( 'suchwunsch' === $fragment_anchor ) {
		return volks_section_url( 'kontakt-formular' );
	}

	$normalized = ltrim( $normalized, '/' );

	// Fix broken relative paths like bewerten/.html (browser → /bewerten/.html).
	if ( preg_match( '#^([a-z0-9_-]+)/\.html$#i', $normalized, $slug_match ) ) {
		$normalized = strtolower( $slug_match[1] ) . '.html';
	}

	$map = volks_get_href_source_key_map();

	if ( isset( $map[ $normalized ] ) ) {
		$page_key = $map[ $normalized ];
		if ( '' !== $fragment && 'volks-home-v1' === $page_key ) {
			return volks_section_url( ltrim( $fragment, '#' ) );
		}
		if ( '' !== $fragment ) {
			$anchor = sanitize_title( ltrim( $fragment, '#' ) );
			if ( 'suchwunsch' === $anchor ) {
				return volks_section_url( 'kontakt-formular' );
			}
			if ( 'prozess' === $anchor && 'volks-verkaufen-v1' === $page_key ) {
				$base = volks_get_page_url( $page_key, home_url( '/' ) );
				return trailingslashit( $base ) . '#ablauf';
			}
			$base   = volks_get_page_url( $page_key, home_url( '/' ) );
			if ( '' !== $anchor && in_array( $anchor, volks_get_section_ids_for_source_key( $page_key ), true ) ) {
				return trailingslashit( $base ) . '#' . $anchor;
			}
			return $base . $fragment;
		}
		return volks_get_page_url( $page_key, home_url( '/' ) );
	}

	// Slug without .html (e.g. bewerten).
	$slug_candidates = array(
		'bewerten'    => 'volks-bewerten-v1',
		'kaufen'      => 'volks-kaufen-v1',
		'verkaufen'   => 'volks-verkaufen-v1',
		'mallorca'    => 'volks-mallorca-v1',
		'ausland'     => 'volks-ausland-v1',
		'impressum'   => 'volks-impressum-v1',
		'datenschutz' => 'volks-datenschutz-v1',
		'danke'       => 'volks-danke-v1',
	);
	$slug_key        = strtolower( preg_replace( '#\.html$#i', '', $normalized ) );
	if ( isset( $slug_candidates[ $slug_key ] ) ) {
		return volks_get_page_url( $slug_candidates[ $slug_key ], home_url( '/' ) ) . $fragment;
	}

	if ( preg_match( '#^index\.html$#i', $normalized ) ) {
		$anchor = sanitize_title( ltrim( $fragment, '#' ) );
		if ( 'prozess' === $anchor && 'volks-verkaufen-v1' === volks_get_current_source_key() ) {
			return trailingslashit( volks_get_page_url( 'volks-verkaufen-v1', home_url( '/' ) ) ) . '#ablauf';
		}
		return volks_section_url( ltrim( $fragment, '#' ) );
	}

	if ( preg_match( '#^(?:Fotos|css|js)/#i', $normalized ) ) {
		return volks_resolve_media_url( $normalized );
	}

	// Last resort: never leave *.html as relative (prevents /bewerten/bewerten.html).
	if ( preg_match( '#^[a-z0-9_.-]+\.html$#i', $normalized ) ) {
		$file_key = strtolower( $normalized );
		if ( isset( $map[ $file_key ] ) ) {
			return volks_get_page_url( $map[ $file_key ], home_url( '/' ) ) . $fragment;
		}
	}

	return $href;
}

/**
 * URL for a file under theme assets/Fotos (bundled static assets).
 *
 * @param string $path Path with or without "Fotos/" prefix.
 * @return string
 */
function volks_theme_assets_fotos_url( $path ) {
	if ( ! defined( 'VOLKS_THEME_PATH' ) || ! defined( 'VOLKS_THEME_URI' ) ) {
		return '';
	}

	$path = trim( str_replace( '\\', '/', (string) $path ), '/' );
	if ( '' === $path ) {
		return '';
	}

	if ( str_starts_with( $path, 'Fotos/' ) ) {
		$path = substr( $path, 6 );
	}

	$abs = VOLKS_THEME_PATH . '/assets/Fotos/' . $path;
	if ( ! is_readable( $abs ) ) {
		return '';
	}

	$encoded = function_exists( 'volks_encode_source_url_path' )
		? volks_encode_source_url_path( $path )
		: rawurlencode( $path );

	return VOLKS_THEME_URI . '/assets/Fotos/' . $encoded;
}

/**
 * Resolve a media path to uploads or importer source URL.
 *
 * @param string $path Relative path.
 * @return string
 */
function volks_resolve_media_url( $path ) {
	$path = trim( str_replace( '\\', '/', (string) $path ), '/' );
	if ( '' === $path ) {
		return '';
	}

	if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
		$url = (string) Leadwerk_Volks_Media::resolve_url( $path );
		if ( '' !== $url && preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}
	}

	$attachment_id = volks_get_attachment_id_by_source_path( $path );
	if ( $attachment_id > 0 ) {
		$url = wp_get_attachment_url( $attachment_id );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}

	$base = volks_get_static_source_base();
	if ( ! empty( $base['url'] ) ) {
		return trailingslashit( (string) $base['url'] ) . volks_encode_source_url_path( $path );
	}

	$theme_url = volks_theme_assets_fotos_url( $path );
	if ( '' !== $theme_url ) {
		return $theme_url;
	}

	return '';
}

/**
 * URL-encode each path segment for static fallback URLs.
 *
 * @param string $path Relative path.
 * @return string
 */
function volks_encode_source_url_path( $path ) {
	$path = trim( str_replace( '\\', '/', (string) $path ), '/' );
	if ( '' === $path ) {
		return '';
	}

	$parts = array_map( 'rawurlencode', explode( '/', $path ) );

	return implode( '/', $parts );
}

/**
 * Lookup attachment by leadwerk_source_path meta.
 *
 * @param string $path Source path.
 * @return int
 */
function volks_get_attachment_id_by_source_path( $path ) {
	if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
		return Leadwerk_Volks_Media::get_attachment_id( $path );
	}

	$path = trim( str_replace( '\\', '/', (string) $path ), '/' );
	if ( '' === $path ) {
		return 0;
	}

	$posts = get_posts(
		array(
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'meta_query'             => array(
				array(
					'key'   => 'leadwerk_source_path',
					'value' => $path,
				),
			),
		)
	);

	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Static source base for fallback asset URLs during import.
 *
 * @return array{path:string,url:string}
 */
function volks_get_static_source_base() {
	$default_path = '';
	if ( defined( 'LEADWERK_IMPORTER_PATH' ) ) {
		$candidate = trailingslashit( LEADWERK_IMPORTER_PATH ) . 'source_assets';
		if ( is_dir( $candidate ) ) {
			$default_path = $candidate;
		}
	}

	$path = (string) apply_filters( 'volks_static_source_base_path', $default_path );
	$url  = '';

	if ( '' !== $path && defined( 'LEADWERK_IMPORTER_URL' ) ) {
		$url = trailingslashit( LEADWERK_IMPORTER_URL ) . 'source_assets/';
	}

	return array(
		'path' => $path,
		'url'  => (string) apply_filters( 'volks_static_source_base_url', $url ),
	);
}

/**
 * Canonical inline background paths from static source HTML (hero slider, process reel).
 *
 * @param string $source_key Source key e.g. volks-ausland-v1.
 * @param string $section_id Section DOM id e.g. hero, prozess.
 * @return string[]
 */
function volks_get_canonical_section_background_paths( $source_key, $section_id ) {
	$source_key = sanitize_key( (string) $source_key );
	$section_id = sanitize_html_class( (string) $section_id );
	if ( '' === $source_key || '' === $section_id ) {
		return array();
	}

	$file_map = volks_get_source_file_map();
	if ( ! isset( $file_map[ $source_key ] ) ) {
		return array();
	}

	$base = volks_get_static_source_base();
	if ( '' === $base['path'] ) {
		return array();
	}

	$file = trailingslashit( (string) $base['path'] ) . (string) $file_map[ $source_key ];
	if ( ! is_readable( $file ) ) {
		return array();
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$html = file_get_contents( $file );
	if ( ! is_string( $html ) || '' === trim( $html ) ) {
		return array();
	}

	$pattern = '/<section[^>]*\bid="' . preg_quote( $section_id, '/' ) . '"[^>]*>.*?<\/section>/is';
	if ( ! preg_match( $pattern, $html, $matches ) ) {
		return array();
	}

	return function_exists( 'volks_extract_inline_background_urls_from_html' )
		? volks_extract_inline_background_urls_from_html( (string) $matches[0] )
		: array();
}

/**
 * Normalize a media filename for loose comparison (Mallorca-1 == Mallorca 1).
 *
 * @param string $path_or_url Path or URL.
 * @return string
 */
function volks_normalize_media_basename( $path_or_url ) {
	$path = (string) $path_or_url;
	if ( preg_match( '#^https?://#i', $path ) ) {
		$parsed = wp_parse_url( $path );
		$path   = is_array( $parsed ) ? (string) ( $parsed['path'] ?? '' ) : $path;
	}

	$base = rawurldecode( wp_basename( $path ) );
	$stem = pathinfo( $base, PATHINFO_FILENAME );

	return strtolower( preg_replace( '/[^a-z0-9]+/i', '', (string) $stem ) );
}
