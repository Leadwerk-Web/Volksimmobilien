<?php
/**
 * Media URL resolution for volksimmobilien imports (shared with theme).
 *
 * @package Leadwerk_Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leadwerk_Volks_Media {

	/** @var array<string,int> Per-request attachment lookup cache. */
	private static $attachment_id_cache = array();

	/** @var array<string,string> Per-request resolved URL cache. */
	private static $resolve_url_cache = array();

	/** @var bool Whether attachment paths were bulk-loaded this request. */
	private static $cache_warmed = false;

	/**
	 * Preload all leadwerk_source_path → attachment mappings (one DB query).
	 *
	 * @return void
	 */
	public static function warm_path_cache() {
		if ( self::$cache_warmed ) {
			return;
		}
		self::$cache_warmed = true;

		global $wpdb;
		if ( ! isset( $wpdb ) ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'leadwerk_source_path' AND meta_value <> ''",
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return;
		}

		foreach ( $rows as $row ) {
			$attachment_id = (int) ( $row['post_id'] ?? 0 );
			$path          = self::normalize_path( (string) ( $row['meta_value'] ?? '' ) );
			if ( $attachment_id <= 0 || '' === $path ) {
				continue;
			}

			self::$attachment_id_cache[ $path ] = $attachment_id;

			$url = wp_get_attachment_url( $attachment_id );
			if ( is_string( $url ) && '' !== $url ) {
				self::$resolve_url_cache[ $path ] = $url;
			}
		}
	}

	/**
	 * Normalize a relative source path like the media importer does.
	 *
	 * @param string $path Raw path from HTML.
	 * @return string
	 */
	public static function normalize_path( $path ) {
		$path = trim( html_entity_decode( (string) $path, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$path = rawurldecode( $path );
		$path = str_replace( '\\', '/', $path );
		$path = preg_replace( '#^\./#', '', $path );
		$path = ltrim( (string) $path, '/' );
		$path = str_replace( array( "\xE2\x80\x93", "\xE2\x80\x94" ), '-', $path );

		return trim( $path );
	}

	/**
	 * Candidate paths for attachment lookup.
	 *
	 * @param string $path Raw path.
	 * @return string[]
	 */
	public static function path_candidates( $path ) {
		$candidates = array();
		$norm       = self::normalize_path( $path );
		if ( '' === $norm ) {
			return $candidates;
		}

		$candidates[] = $norm;

		if ( 0 === stripos( $norm, 'fotos/' ) ) {
			$candidates[] = substr( $norm, 6 );
		} else {
			$candidates[] = 'Fotos/' . $norm;
			$candidates[] = 'fotos/' . $norm;
		}

		return array_values( array_unique( array_filter( $candidates ) ) );
	}

	/**
	 * Find attachment ID by leadwerk_source_path meta.
	 *
	 * @param string $path Relative path.
	 * @return int
	 */
	public static function get_attachment_id( $path ) {
		self::warm_path_cache();

		$cache_key = self::normalize_path( $path );
		if ( '' === $cache_key ) {
			return 0;
		}
		if ( array_key_exists( $cache_key, self::$attachment_id_cache ) ) {
			return self::$attachment_id_cache[ $cache_key ];
		}

		foreach ( self::path_candidates( $path ) as $candidate ) {
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
							'value' => $candidate,
						),
					),
				)
			);

			if ( ! empty( $posts ) ) {
				self::$attachment_id_cache[ $cache_key ] = (int) $posts[0];
				return self::$attachment_id_cache[ $cache_key ];
			}
		}

		self::$attachment_id_cache[ $cache_key ] = 0;

		return 0;
	}

	/**
	 * Resolve one media path to a public URL.
	 *
	 * @param string $path Relative path.
	 * @return string
	 */
	public static function resolve_url( $path ) {
		$path = trim( (string) $path );
		if ( '' === $path || preg_match( '#^(?:https?:)?//#i', $path ) || preg_match( '#^(?:data:|mailto:|tel:)#i', $path ) ) {
			return $path;
		}

		self::warm_path_cache();

		$cache_key = self::normalize_path( $path );
		if ( '' !== $cache_key && array_key_exists( $cache_key, self::$resolve_url_cache ) ) {
			return self::$resolve_url_cache[ $cache_key ];
		}

		$attachment_id = self::get_attachment_id( $path );
		if ( $attachment_id > 0 ) {
			$url = wp_get_attachment_url( $attachment_id );
			if ( is_string( $url ) && '' !== $url ) {
				if ( '' !== $cache_key ) {
					self::$resolve_url_cache[ $cache_key ] = $url;
				}
				return $url;
			}
		}

		$norm = '' !== $cache_key ? $cache_key : self::normalize_path( $path );
		if ( '' === $norm ) {
			return $path;
		}

		if ( defined( 'LEADWERK_IMPORTER_URL' ) ) {
			$segments                              = array_map( 'rawurlencode', explode( '/', $norm ) );
			$resolved                              = trailingslashit( LEADWERK_IMPORTER_URL ) . 'source_assets/' . implode( '/', $segments );
			self::$resolve_url_cache[ $cache_key ] = $resolved;

			return $resolved;
		}

		if ( '' !== $cache_key ) {
			self::$resolve_url_cache[ $cache_key ] = $path;
		}

		return $path;
	}

	/**
	 * Rewrite src/href/poster/style urls inside HTML fragment.
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	public static function rewrite_html_fragment( $html ) {
		$html = trim( (string) $html );
		if ( '' === $html ) {
			return $html;
		}

		self::warm_path_cache();

		if ( ! preg_match( '#(?:\b(?:src|poster|data-src|data-img|data-bg|srcset)\s*=|url\s*\()[^>]*(?:Fotos/|fotos/)#i', $html ) ) {
			return $html;
		}

		return self::rewrite_html_fragment_regex( $html );
	}

	/**
	 * Fast regex-based media URL rewrite (no extra DOM pass).
	 *
	 * @param string $html HTML fragment.
	 * @return string
	 */
	public static function rewrite_html_fragment_regex( $html ) {
		$html = preg_replace_callback(
			'/\b(src|poster|data-src|data-img|data-bg)\s*=\s*(["\'])([^"\']+)\2/i',
			static function ( $matches ) {
				$raw = trim( (string) ( $matches[3] ?? '' ) );
				if ( '' === $raw || preg_match( '#^(?:https?:)?//#i', $raw ) || preg_match( '#^(?:data:|mailto:|tel:)#i', $raw ) ) {
					return (string) $matches[0];
				}
				$resolved = self::resolve_url( $raw );

				return (string) ( $matches[1] ?? 'src' ) . '=' . (string) ( $matches[2] ?? '"' ) . $resolved . (string) ( $matches[2] ?? '"' );
			},
			$html
		);

		if ( ! is_string( $html ) ) {
			return '';
		}

		$html = preg_replace_callback(
			'/\bsrcset\s*=\s*(["\'])([^"\']+)\1/i',
			static function ( $matches ) {
				$quote = (string) ( $matches[1] ?? '"' );
				$parts = array_map( 'trim', explode( ',', (string) ( $matches[2] ?? '' ) ) );
				$parts = array_map( array( self::class, 'normalize_srcset_part' ), $parts );

				return 'srcset=' . $quote . implode( ', ', array_filter( $parts ) ) . $quote;
			},
			$html
		);

		if ( ! is_string( $html ) ) {
			return '';
		}

		$rewritten = preg_replace_callback(
			'/url\(\s*(["\']?)([^"\')]+)\1\s*\)/i',
			static function ( $matches ) {
				$raw = trim( (string) ( $matches[2] ?? '' ) );
				if ( '' === $raw || preg_match( '#^(?:https?:)?//#i', $raw ) || preg_match( '#^data:#i', $raw ) ) {
					return (string) $matches[0];
				}
				$resolved = self::resolve_url( $raw );
				$quote    = (string) ( $matches[1] ?? '' );

				return 'url(' . $quote . $resolved . $quote . ')';
			},
			$html
		);

		return is_string( $rewritten ) ? $rewritten : $html;
	}

	/**
	 * Normalize one srcset entry.
	 *
	 * @param string $part Srcset part.
	 * @return string
	 */
	public static function normalize_srcset_part( $part ) {
		$part = trim( (string) $part );
		if ( '' === $part ) {
			return '';
		}

		if ( preg_match( '/^(\S+)(\s+.+)$/', $part, $m ) ) {
			return self::resolve_url( $m[1] ) . $m[2];
		}

		return self::resolve_url( $part );
	}
}
