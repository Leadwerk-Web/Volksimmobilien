<?php
/**
 * Output helpers for structured Volks fields.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// #region agent log
if ( ! function_exists( 'volks_agent_debug_log' ) ) {
	/**
	 * @param string               $hypothesis_id Hypothesis id.
	 * @param string               $location      File:line context.
	 * @param string               $message       Log message.
	 * @param array<string,mixed>  $data          Extra data.
	 * @return void
	 */
	function volks_agent_debug_log( $hypothesis_id, $location, $message, $data = array() ) {
		$payload = array(
			'sessionId'    => '4d0610',
			'hypothesisId' => (string) $hypothesis_id,
			'location'     => (string) $location,
			'message'      => (string) $message,
			'data'         => is_array( $data ) ? $data : array(),
			'timestamp'    => (int) round( microtime( true ) * 1000 ),
		);
		$line    = wp_json_encode( $payload ) . "\n";
		$paths   = array();
		if ( defined( 'VOLKS_THEME_PATH' ) ) {
			$paths[] = dirname( VOLKS_THEME_PATH ) . '/.cursor/debug-4d0610.log';
		}
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$paths[] = WP_CONTENT_DIR . '/uploads/volks-debug-4d0610.log';
		}
		$paths[] = '/Users/atlas/Documents/Github/Volksimmobilien/.cursor/debug-4d0610.log';
		foreach ( array_unique( array_filter( $paths ) ) as $path ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== @file_put_contents( $path, $line, FILE_APPEND | LOCK_EX ) ) {
				break;
			}
		}
	}
}
// #endregion

/**
 * Load Leadwerk field data (bypasses ACF when both plugins are active).
 *
 * @param string $name    Field name.
 * @param int    $post_id Post ID.
 * @return mixed
 */
function volks_get_stored_field( $name, $post_id ) {
	$post_id = (int) $post_id;
	$name    = (string) $name;
	if ( $post_id <= 0 || '' === $name ) {
		return null;
	}

	if ( class_exists( 'Leadwerk_Fields_API' ) ) {
		return Leadwerk_Fields_API::get_field( $name, $post_id );
	}

	if ( function_exists( 'get_field' ) ) {
		return get_field( $name, $post_id );
	}

	$raw = get_post_meta( $post_id, $name, true );
	if ( is_string( $raw ) && '' !== $raw ) {
		$first = $raw[0] ?? '';
		if ( '{' === $first || '[' === $first ) {
			$decoded = json_decode( $raw, true );
			if ( JSON_ERROR_NONE === json_last_error() ) {
				return $decoded;
			}
		}
	}

	return $raw;
}

/**
 * Normalize one flexible-content section before rendering.
 *
 * @param array<string,mixed> $section Section row.
 * @return array<string,mixed>|null
 */
function volks_prepare_section_for_render( $section ) {
	if ( ! is_array( $section ) ) {
		return null;
	}

	$layout = (string) ( $section['acf_fc_layout'] ?? '' );
	if ( 'volks_editable_html_section' === $layout ) {
		$html = (string) ( $section['original_html'] ?? $section['html'] ?? '' );
		if ( '' === trim( $html ) ) {
			return null;
		}

		$section_id = (string) ( $section['section_id'] ?? '' );
		if ( 'kontakt-formular' === $section_id && ! empty( $section['info_cards'] ) && is_array( $section['info_cards'] ) ) {
			return array_merge(
				$section,
				array(
					'acf_fc_layout' => 'volks_contact',
				)
			);
		}

		$merge_fields = (bool) apply_filters( 'volks_apply_editable_html_field_merge', true, $section );
		if ( $merge_fields ) {
			return $section;
		}

		return array(
			'acf_fc_layout' => 'html_section',
			'section_id'    => (string) ( $section['section_id'] ?? '' ),
			'html'          => $html,
		);
	}

	return $section;
}

/**
 * Remove duplicate/empty Weil-slider rows from legacy imports.
 *
 * @param array<int,mixed> $slides Slide rows.
 * @return array<int,array<string,mixed>>
 */
function volks_sanitize_weil_slides( $slides ) {
	if ( ! is_array( $slides ) ) {
		return array();
	}

	$out  = array();
	$seen = array();

	foreach ( $slides as $slide ) {
		if ( ! is_array( $slide ) ) {
			continue;
		}

		$claim = trim( (string) ( $slide['claim'] ?? '' ) );
		$text  = trim( (string) ( $slide['text'] ?? '' ) );
		if ( '' === $claim ) {
			continue;
		}
		if ( '' === $text && empty( $slide['image'] ) ) {
			continue;
		}

		$key = strtolower( $claim );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$out[]        = $slide;
	}

	return $out;
}

/**
 * Remove duplicate/empty region stat rows from legacy imports.
 *
 * @param array<int,mixed> $stats Stat rows.
 * @return array<int,array<string,mixed>>
 */
function volks_sanitize_region_stats( $stats ) {
	if ( ! is_array( $stats ) ) {
		return array();
	}

	$out  = array();
	$seen = array();

	foreach ( $stats as $stat ) {
		if ( ! is_array( $stat ) ) {
			continue;
		}

		$number = trim( wp_strip_all_tags( (string) ( $stat['number'] ?? '' ) ) );
		$label  = trim( (string) ( $stat['label'] ?? '' ) );
		if ( '' === $number && '' === $label ) {
			continue;
		}

		$key = strtolower( $number . '|' . $label );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}

		$seen[ $key ] = true;
		$out[]        = $stat;
	}

	return $out;
}

/**
 * @param string $text Plain text.
 * @return string
 */
function volks_esc_text( $text ) {
	$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	return esc_html( $text );
}

/**
 * @param string $html Heading inner HTML.
 * @return string
 */
function volks_heading_inner( $html ) {
	$html = class_exists( 'Leadwerk_Content_Schema' )
		? Leadwerk_Content_Schema::sanitize_heading_html( (string) $html )
		: wp_kses_post( (string) $html );
	return $html;
}

/**
 * @param string $html Small HTML fragment (prices, stats).
 * @return string
 */
function volks_inline_html( $html ) {
	return wp_kses_post( (string) $html );
}

/**
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Image size.
 * @param array<string,string> $attrs Extra attributes.
 * @return string
 */
/**
 * Default SVG icon for a contact info card (matches static HTML).
 *
 * @param string $title Card title.
 * @return string
 */
function volks_default_contact_info_icon_html( $title ) {
	$title = strtolower( trim( (string) $title ) );

	if ( str_contains( $title, 'sprechen' ) || str_contains( $title, 'direkt' ) ) {
		return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>';
	}

	if ( str_contains( $title, 'büro' ) || str_contains( $title, 'buero' ) || str_contains( $title, 'unser' ) ) {
		return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>';
	}

	return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
}

/**
 * Contact info card icon from import field or static fallback.
 *
 * @param array<string,mixed> $card Card row.
 * @return string
 */
function volks_render_contact_info_icon_html( $card ) {
	$icon_html = trim( (string) ( $card['icon_html'] ?? '' ) );
	if ( '' !== $icon_html ) {
		if ( function_exists( 'volks_normalize_svg_markup' ) ) {
			$icon_html = volks_normalize_svg_markup( $icon_html );
		}

		return $icon_html;
	}

	return volks_default_contact_info_icon_html( (string) ( $card['title'] ?? '' ) );
}

/**
 * Valuation option card icon (attachment or static Fotos fallback).
 *
 * @param array<string,mixed> $card Card row.
 * @return string
 */
function volks_valuation_option_icon_html( $card ) {
	$attrs = array(
		'width'    => '65',
		'height'   => '65',
		'loading'  => 'lazy',
		'decoding' => 'async',
		'alt'      => '',
	);

	$tag = volks_img_tag( (int) ( $card['icon'] ?? 0 ), 'medium', $attrs );
	if ( '' !== $tag ) {
		return $tag;
	}

	$kicker = strtolower( trim( (string) ( $card['kicker'] ?? '' ) ) );
	$path   = '';
	if ( str_contains( $kicker, 'online' ) ) {
		$path = 'Fotos/Onlinerechner.webp';
	} elseif ( str_contains( $kicker, 'persönlich' ) || str_contains( $kicker, 'persoenlich' ) || str_contains( $kicker, 'personal' ) ) {
		$path = 'Fotos/persönliche-Bewertung.webp';
	}

	if ( '' === $path ) {
		// #region agent log
		if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) ) {
			volks_agent_debug_log( 'B', 'volks-field-output.php:volks_valuation_option_icon_html', 'icon_path_empty', array( 'kicker' => $kicker ) );
		}
		// #endregion
		return '';
	}

	$url = function_exists( 'volks_resolve_media_url' )
		? volks_resolve_media_url( $path )
		: '';

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) ) {
		volks_agent_debug_log(
			'B',
			'volks-field-output.php:volks_valuation_option_icon_html',
			'icon_resolve',
			array(
				'kicker'     => $kicker,
				'path'       => $path,
				'url_len'    => strlen( (string) $url ),
				'url_prefix' => substr( (string) $url, 0, 80 ),
				'icon_id'    => (int) ( $card['icon'] ?? 0 ),
			)
		);
	}
	// #endregion

	if ( '' === $url ) {
		return '';
	}

	return sprintf(
		'<img src="%1$s" alt="" width="65" height="65" loading="lazy" decoding="async">',
		esc_url( $url )
	);
}

/**
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Image size.
 * @param array<string,string> $attrs Extra attributes.
 * @return string
 */
function volks_img_tag( $attachment_id, $size = 'full', $attrs = array() ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	$attr = array_merge(
		array(
			'alt' => $alt,
		),
		$attrs
	);

	return wp_get_attachment_image( $attachment_id, $size, false, $attr );
}

/**
 * @param int $attachment_id Attachment ID.
 * @return string
 */
function volks_attachment_url( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 ) {
		return '';
	}
	$url = wp_get_attachment_url( $attachment_id );
	return is_string( $url ) ? $url : '';
}

/**
 * @param int $attachment_id Attachment ID.
 * @return string
 */
function volks_bg_image_style( $attachment_id ) {
	$url = volks_attachment_url( $attachment_id );
	if ( '' === $url ) {
		return '';
	}
	return 'background-image: url(' . esc_url( $url ) . ');';
}

/**
 * @param int|string $video Video attachment ID or legacy URL.
 * @return string
 */
function volks_video_source_url( $video ) {
	if ( is_numeric( $video ) && (int) $video > 0 ) {
		$url = wp_get_attachment_url( (int) $video );
		return is_string( $url ) ? $url : '';
	}
	return trim( (string) $video );
}

/**
 * @param string $url   Raw URL.
 * @param string $label Link label.
 * @param string $class CSS classes.
 * @return string
 */
function volks_btn_link( $url, $label, $class = 'btn btn-primary' ) {
	$url   = trim( (string) $url );
	$label = trim( (string) $label );
	if ( '' === $url || '' === $label ) {
		return '';
	}

	if ( function_exists( 'volks_unwrap_false_absolute_html_href' ) ) {
		$url = volks_unwrap_false_absolute_html_href( $url );
	}

	$href = function_exists( 'volks_resolve_href' ) ? volks_resolve_href( $url ) : $url;

	return sprintf(
		'<a href="%1$s" class="%2$s">%3$s</a>',
		esc_url( $href ),
		esc_attr( $class ),
		esc_html( $label )
	);
}

/**
 * @param string $lines Newline-separated list items.
 * @param bool   $featured Featured card styles.
 * @return string
 */
function volks_features_list_html( $lines, $featured = false ) {
	$items = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $lines ) ) );
	if ( empty( $items ) ) {
		return '';
	}
	$class = 'valuation-option-card__features' . ( $featured ? ' valuation-option-card__features--featured' : '' );
	$html  = '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $items as $item ) {
		$icon = $featured
			? '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M8 12l3 3 5-6" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
			: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M8 12l3 3 5-6"/></svg>';
		$html .= '<li>' . $icon . esc_html( $item ) . '</li>';
	}
	$html .= '</ul>';
	return $html;
}

/**
 * WPForms form ID or shortcode from Leadwerk options.
 *
 * @param string $locale de|en.
 * @return string
 */
function volks_get_wpforms_option_value( $locale = 'de' ) {
	$key = ( 'en' === $locale ) ? 'wpforms_form_id_en' : 'wpforms_form_id_de';
	if ( class_exists( 'Leadwerk_Fields_API' ) ) {
		return trim( (string) Leadwerk_Fields_API::get_field( $key, 'option' ) );
	}
	if ( function_exists( 'get_field' ) ) {
		return trim( (string) get_field( $key, 'option' ) );
	}
	return '';
}

/**
 * Build a WPForms shortcode from option value (numeric ID or full shortcode).
 *
 * @param string $raw Option value.
 * @return string
 */
function volks_build_wpforms_shortcode( $raw ) {
	$raw = trim( (string) $raw );
	if ( '' === $raw ) {
		return '';
	}

	if ( preg_match( '/\[wpforms[^\]]*id\s*=\s*["\']?(\d+)["\']?/i', $raw, $matches ) ) {
		$raw = (string) $matches[1];
	}

	if ( false !== strpos( $raw, '[' ) ) {
		return $raw;
	}

	if ( preg_match( '/^\d+$/', $raw ) ) {
		$form_id = (int) $raw;
		if ( $form_id <= 0 || $form_id > 99999 ) {
			return '';
		}
		if ( function_exists( 'get_post' ) ) {
			$form_post = get_post( $form_id );
			if ( ! $form_post || 'wpforms' !== $form_post->post_type ) {
				return '';
			}
		}
		return '[wpforms id="' . $form_id . '"]';
	}

	if ( preg_match( '/(\d{1,5})/', $raw, $matches ) ) {
		return volks_build_wpforms_shortcode( (string) $matches[1] );
	}

	return '';
}

/**
 * Render WPForms markup for the contact area (empty if not configured).
 *
 * @param string $locale de|en.
 * @return string
 */
function volks_render_wpforms_markup( $locale = 'de' ) {
	static $rendering = false;
	static $call_count = 0;
	static $cached_markup = null;
	static $cached_locale = '';
	++$call_count;
	$t0 = microtime( true );

	if ( null !== $cached_markup && $cached_locale === $locale ) {
		// #region agent log
		volks_agent_debug_log(
			'A',
			'volks-field-output.php:volks_render_wpforms_markup',
			'wpforms_render_cache_hit',
			array(
				'call_count' => $call_count,
				'html_len'   => strlen( (string) $cached_markup ),
				'runId'      => 'post-fix',
			)
		);
		// #endregion
		return $cached_markup;
	}

	// #region agent log
	volks_agent_debug_log(
		'A',
		'volks-field-output.php:volks_render_wpforms_markup',
		'wpforms_render_enter',
		array(
			'call_count'     => $call_count,
			'rendering_flag' => $rendering,
			'locale'         => $locale,
		)
	);
	// #endregion

	if ( $rendering ) {
		// #region agent log
		volks_agent_debug_log(
			'B',
			'volks-field-output.php:volks_render_wpforms_markup',
			'wpforms_render_blocked_by_flag',
			array( 'call_count' => $call_count )
		);
		// #endregion
		return '';
	}

	$shortcode = volks_build_wpforms_shortcode( volks_get_wpforms_option_value( $locale ) );
	if ( '' === $shortcode || ! function_exists( 'do_shortcode' ) ) {
		return '';
	}

	$rendering = true;
	$html      = do_shortcode( $shortcode );
	$rendering = false;
	$ms        = (int) round( ( microtime( true ) - $t0 ) * 1000 );

	// #region agent log
	volks_agent_debug_log(
		'C',
		'volks-field-output.php:volks_render_wpforms_markup',
		'wpforms_render_exit',
		array(
			'call_count'  => $call_count,
			'duration_ms' => $ms,
			'html_len'    => strlen( (string) $html ),
		)
	);
	// #endregion

	if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
		$cached_markup = '';
		$cached_locale = $locale;
		return '';
	}

	$cached_markup = '<div class="volks-wpforms home-contact-wpforms" data-volks-wpforms="1">' . $html . '</div>';
	$cached_locale = $locale;

	return $cached_markup;
}

/**
 * Whether a WPForms contact form is configured in theme options.
 *
 * @param string $locale de|en.
 * @return bool
 */
function volks_has_wpforms_contact_form( $locale = 'de' ) {
	return '' !== volks_build_wpforms_shortcode( volks_get_wpforms_option_value( $locale ) );
}

/**
 * Pages that include the imported #kontakt-formular / .home-contact-form block.
 *
 * @return bool
 */
function volks_page_uses_wpforms_contact() {
	if ( ! is_singular( 'page' ) || ! function_exists( 'volks_is_source_key' ) ) {
		return false;
	}

	return volks_is_source_key( 'volks-home-v1' ) || volks_is_source_key( 'volks-bewerten-v1' );
}

/**
 * Replace static mailto contact forms in HTML with WPForms when configured.
 *
 * @param string $html HTML fragment.
 * @return string
 */
function volks_swap_static_contact_form_for_wpforms( $html ) {
	static $swapping = false;
	static $swap_count = 0;
	++$swap_count;
	$has_form = false !== strpos( (string) $html, 'home-contact-form' );

	// #region agent log
	volks_agent_debug_log(
		'A',
		'volks-field-output.php:volks_swap_static_contact_form_for_wpforms',
		'swap_enter',
		array(
			'swap_count'   => $swap_count,
			'has_form'     => $has_form,
			'html_len'     => strlen( (string) $html ),
			'swapping'     => $swapping,
		)
	);
	// #endregion

	if ( $swapping || ! $has_form ) {
		if ( ! $has_form ) {
			// #region agent log
			volks_agent_debug_log(
				'A',
				'volks-field-output.php:volks_swap_static_contact_form_for_wpforms',
				'swap_skip_no_contact_section',
				array(
					'swap_count' => $swap_count,
					'runId'      => 'post-fix',
				)
			);
			// #endregion
		}
		return $html;
	}

	$wpforms = volks_render_wpforms_markup();
	if ( '' === $wpforms ) {
		// #region agent log
		volks_agent_debug_log(
			'A',
			'volks-field-output.php:volks_swap_static_contact_form_for_wpforms',
			'swap_skip_empty_wpforms',
			array(
				'swap_count' => $swap_count,
				'runId'      => 'post-fix',
			)
		);
		// #endregion
		return $html;
	}

	$swapping = true;
	$out      = preg_replace(
		'#<form\b[^>]*\bhome-contact-form\b[^>]*>.*?</form>#is',
		$wpforms,
		(string) $html,
		1
	);
	$swapping = false;

	// #region agent log
	volks_agent_debug_log(
		'A',
		'volks-field-output.php:volks_swap_static_contact_form_for_wpforms',
		'swap_done_preg',
		array(
			'swap_count' => $swap_count,
			'replaced'   => is_string( $out ) && $out !== $html,
			'runId'      => 'post-fix',
		)
	);
	// #endregion

	return is_string( $out ) ? $out : $html;
}
