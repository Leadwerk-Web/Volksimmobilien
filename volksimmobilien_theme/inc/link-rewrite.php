<?php
/**
 * Rewrite static HTML links and media paths in stored content.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Section CSS classes whose background is defined in theme CSS (not inline HTML).
 *
 * @return array<string,string> Class fragment => static Fotos path.
 */
function volks_known_section_css_backgrounds() {
	return array(
		'editorial-section--sell-bg' => 'Fotos/Mallorca%203.webp',
		'cta-section--mallorca-bg'   => 'Fotos/Mallorca%202.webp',
	);
}

/**
 * Resolve a static Fotos path to a WordPress media URL.
 *
 * @param string $path Static path.
 * @return string
 */
function volks_resolve_static_media_url( $path ) {
	$path = trim( (string) $path );
	if ( '' === $path ) {
		return '';
	}

	if ( function_exists( 'volks_resolve_media_url' ) ) {
		return (string) volks_resolve_media_url( $path );
	}

	if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
		$url = (string) Leadwerk_Volks_Media::resolve_url( $path );
		if ( '' !== $url && preg_match( '#^https?://#i', $url ) ) {
			return $url;
		}
	}

	return function_exists( 'volks_theme_assets_fotos_url' )
		? (string) volks_theme_assets_fotos_url( $path )
		: '';
}

/**
 * Merge one CSS declaration into an inline style attribute.
 *
 * @param string $style    Existing style.
 * @param string $property Property name.
 * @param string $value    Property value.
 * @return string
 */
function volks_merge_inline_style_property( $style, $property, $value ) {
	$style = trim( (string) $style );
	$style = preg_replace( '/' . preg_quote( $property, '/' ) . '\s*:[^;]*;?/i', '', $style );
	$style = trim( (string) $style, " ;" );
	if ( '' !== $style ) {
		$style .= ';';
	}

	return $style . $property . ':' . $value;
}

/**
 * Build scoped CSS so ::before carries the section photo (same stacking as static HTML).
 *
 * @param string $section_id Section DOM id.
 * @param string $class_key  CSS class key.
 * @param string $url        Image URL.
 * @return string
 */
function volks_build_section_background_style_rule( $section_id, $class_key, $url ) {
	$section_id = sanitize_html_class( (string) $section_id );
	$url        = esc_url( (string) $url );
	if ( '' === $section_id || '' === $url ) {
		return '';
	}

	$selector = '#' . $section_id;

	if ( 'editorial-section--sell-bg' === $class_key ) {
		return $selector . '.editorial-section--sell-bg::before{background-image:url("' . $url . '")!important;background-size:cover!important;background-position:72% center!important;background-repeat:no-repeat!important;z-index:0!important;}';
	}

	if ( 'cta-section--mallorca-bg' === $class_key ) {
		return $selector . '.cta-section--mallorca-bg::before{background:linear-gradient(180deg,rgba(10,10,10,.72) 0%,rgba(10,10,10,.66) 40%,rgba(10,10,10,.6) 100%) 0 0/100% 100% no-repeat,radial-gradient(ellipse 90% 50% at 50% 0%,rgba(201,90,63,.12),transparent 55%) 0 0/100% 100% no-repeat,radial-gradient(ellipse 50% 45% at 100% 100%,rgba(127,189,189,.1),transparent 50%) 0 0/100% 100% no-repeat,url("' . $url . '")!important;background-size:100% 100%,100% 100%,100% 100%,cover!important;background-position:0 0,0 0,0 0,center 42%!important;background-repeat:no-repeat!important;z-index:0!important;}';
	}

	return '';
}

/**
 * Apply CSS section backgrounds from import fields (::before, not DOM layer).
 *
 * @param string              $html                       HTML fragment.
 * @param array<int,mixed>    $background_rows            Repeater rows (image + css_class).
 * @param int                 $css_section_background     Attachment ID from import field.
 * @param string              $css_section_background_class CSS class key.
 * @return string
 */
function volks_apply_section_css_background_variables( $html, $background_rows = array(), $css_section_background = 0, $css_section_background_class = '' ) {
	$html = trim( (string) $html );
	if ( '' === $html ) {
		return $html;
	}

	$known = volks_known_section_css_backgrounds();
	if ( ! preg_match( '/editorial-section--sell-bg|cta-section--mallorca-bg/', $html ) ) {
		return $html;
	}

	$url_by_class = array();

	if ( $css_section_background > 0 && '' !== trim( (string) $css_section_background_class ) ) {
		$direct_url = function_exists( 'volks_attachment_url' )
			? volks_attachment_url( (int) $css_section_background )
			: '';
		if ( '' !== $direct_url ) {
			$url_by_class[ trim( (string) $css_section_background_class ) ] = $direct_url;
		}
	}

	foreach ( (array) $background_rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$class_key = (string) ( $row['css_class'] ?? '' );
		if ( '' === $class_key ) {
			continue;
		}
		$url = function_exists( 'volks_attachment_url' )
			? volks_attachment_url( (int) ( $row['image'] ?? 0 ) )
			: '';
		if ( '' !== $url ) {
			$url_by_class[ $class_key ] = $url;
		}
	}

	foreach ( $known as $class_key => $static_path ) {
		if ( isset( $url_by_class[ $class_key ] ) ) {
			continue;
		}
		$url = volks_resolve_static_media_url( $static_path );
		if ( '' !== $url ) {
			$url_by_class[ $class_key ] = $url;
		}
	}

	if ( array() === $url_by_class ) {
		return $html;
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return $html;
	}

	$previous = libxml_use_internal_errors( true );
	$doc      = new DOMDocument();
	$loaded   = $doc->loadHTML( '<?xml encoding="utf-8" ?><div id="volks-css-bg-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded ) {
		return $html;
	}

	$root = $doc->getElementById( 'volks-css-bg-root' );
	if ( ! $root instanceof DOMElement ) {
		return $html;
	}

	$xpath = new DOMXPath( $doc );
	foreach ( $url_by_class as $class_key => $url ) {
		$escaped = str_replace( "'", "\\'", $class_key );
		$query   = './/*[contains(concat(" ", normalize-space(@class), " "), " ' . $escaped . ' ")]';
		$nodes   = $xpath->query( $query, $root );
		if ( ! $nodes instanceof DOMNodeList ) {
			continue;
		}
		foreach ( $nodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$section_id = trim( (string) $node->getAttribute( 'id' ) );
			if ( '' === $section_id ) {
				continue;
			}

			$css_rule = volks_build_section_background_style_rule( $section_id, $class_key, $url );
			if ( '' === $css_rule ) {
				continue;
			}

			$style_id = 'volks-section-bg-style-' . $section_id;
			$existing = $xpath->query( './/style[@id="' . $style_id . '"]', $node );
			if ( $existing instanceof DOMNodeList && $existing->length > 0 ) {
				$existing->item( 0 )->nodeValue = $css_rule;
			} else {
				$style_el = $doc->createElement( 'style', $css_rule );
				$style_el->setAttribute( 'id', $style_id );
				if ( $node->firstChild ) {
					$node->insertBefore( $style_el, $node->firstChild );
				} else {
					$node->appendChild( $style_el );
				}
			}

			$legacy_layers = $xpath->query( './/div[contains(@class,"volks-section-bg-layer")]', $node );
			if ( $legacy_layers instanceof DOMNodeList ) {
				for ( $i = $legacy_layers->length - 1; $i >= 0; $i-- ) {
					$legacy = $legacy_layers->item( $i );
					if ( $legacy instanceof DOMElement && $legacy->parentNode instanceof DOMNode ) {
						$legacy->parentNode->removeChild( $legacy );
					}
				}
			}

			// Legacy CSS-variable fallback entfernen.
			$section_style = (string) $node->getAttribute( 'style' );
			$section_style = preg_replace( '/--volks-section-bg-image\s*:[^;]*;?/i', '', $section_style );
			$section_style = trim( $section_style, " ;" );
			if ( '' !== $section_style ) {
				$node->setAttribute( 'style', $section_style );
			} else {
				$node->removeAttribute( 'style' );
			}

			// #region agent log
			if ( function_exists( 'volks_agent_debug_log' ) ) {
				volks_agent_debug_log(
					'H-css-bg-style',
					'link-rewrite.php:volks_apply_section_css_background_variables',
					'section ::before background style injected',
					array(
						'class'      => $class_key,
						'url'        => $url,
						'section_id' => $section_id,
						'image_id'   => (int) $css_section_background,
					)
				);
			}
			// #endregion
		}
	}

	$out = '';
	foreach ( $root->childNodes as $child ) {
		$out .= $doc->saveHTML( $child );
	}

	return $out;
}

/**
 * Fix SVG attributes broken by DOMDocument (e.g. viewbox instead of viewBox).
 *
 * @param string $svg SVG markup.
 * @return string
 */
function volks_normalize_svg_markup( $svg ) {
	$svg = trim( (string) $svg );
	if ( '' === $svg ) {
		return $svg;
	}

	return preg_replace( '/\bviewbox\s*=/i', 'viewBox=', $svg );
}

/**
 * Remove anchor nodes whose trimmed text matches one of the labels.
 *
 * @param DOMXPath     $xpath XPath.
 * @param DOMElement   $root  Fragment root.
 * @param string|null  $scope Section id to limit search, or null for whole fragment.
 * @param string[]     $labels Exact button labels to remove.
 * @return int Number of removed nodes.
 */
function volks_dom_remove_ctas_by_labels( DOMXPath $xpath, DOMElement $root, $scope, array $labels ) {
	$scope_query = null !== $scope && '' !== $scope
		? './/section[@id="' . $scope . '"]//a[contains(@class,"btn")]'
		: './/a[contains(@class,"btn")]';
	$removed     = 0;

	foreach ( $xpath->query( $scope_query, $root ) as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		$text = trim( preg_replace( '/\s+/u', ' ', $node->textContent ) );
		if ( ! in_array( $text, $labels, true ) ) {
			continue;
		}
		if ( $node->parentNode instanceof DOMNode ) {
			$node->parentNode->removeChild( $node );
			++$removed;
		}
	}

	return $removed;
}

/**
 * Promote the remaining CTA in a row to primary (red) styling.
 *
 * @param DOMXPath   $xpath XPath.
 * @param DOMElement $root  Fragment root.
 * @param string     $row_query XPath query for CTA rows.
 */
function volks_dom_promote_solo_cta_rows( DOMXPath $xpath, DOMElement $root, $row_query ) {
	foreach ( $xpath->query( $row_query, $root ) as $row ) {
		if ( ! $row instanceof DOMElement ) {
			continue;
		}
		$links = array();
		foreach ( $xpath->query( './/a[contains(@class,"btn")]', $row ) as $link ) {
			if ( $link instanceof DOMElement ) {
				$links[] = $link;
			}
		}
		if ( 1 !== count( $links ) ) {
			continue;
		}
		$btn = $links[0];
		$class = (string) $btn->getAttribute( 'class' );
		$class = preg_replace( '/\bbtn-(?:outline(?:-(?:light|accent))?|ghost-light|ghost)\b/', '', $class );
		$class = trim( preg_replace( '/\s+/', ' ', $class . ' btn-primary' ) );
		$btn->setAttribute( 'class', $class );
	}
}

/**
 * Restore Ausland intro banner + Kaufen & Verkaufen CTA when import stripped markup.
 *
 * @param DOMXPath   $xpath XPath.
 * @param DOMElement $root  Fragment root.
 */
function volks_apply_ausland_markup_patches_dom( DOMXPath $xpath, DOMElement $root ) {
	$doc = $root->ownerDocument;
	if ( ! $doc instanceof DOMDocument ) {
		return;
	}

	foreach ( $xpath->query( './/section[@id="einleitung"]//a[contains(@href,"#kaufen")]', $root ) as $link ) {
		if ( ! $link instanceof DOMElement ) {
			continue;
		}
		if ( '' === trim( $link->textContent ) ) {
			$link->textContent = 'Kaufen & Verkaufen';
		}
		$class = trim( preg_replace( '/\s+/', ' ', (string) $link->getAttribute( 'class' ) . ' btn-primary btn-lg' ) );
		$class = preg_replace( '/\bbtn-(?:outline(?:-(?:light|accent))?)\b/', '', $class );
		$link->setAttribute( 'class', trim( preg_replace( '/\s+/', ' ', $class ) ) );
	}

	foreach ( $xpath->query( './/*[contains(@class,"trust-stat-banner") and not(contains(@class,"trust-stat-banner--solo"))]', $root ) as $banner ) {
		if ( ! $banner instanceof DOMElement ) {
			continue;
		}
		if ( $xpath->query( './/*[contains(@class,"trust-stat-banner__value")]', $banner )->length > 0 ) {
			continue;
		}

		$desc = trim( preg_replace( '/\s+/u', ' ', $banner->textContent ) );
		$desc = preg_replace( '/^DE\s*·\s*ES\s*·\s*HR\s*/u', '', $desc );
		if ( '' === $desc ) {
			$desc = 'Deutschsprachige Begleitung, lokale Netzwerke auf Mallorca und in Kroatien – ein Ansprechpartner, mehrere Märkte.';
		}

		while ( $banner->firstChild ) {
			$banner->removeChild( $banner->firstChild );
		}

		$value = $doc->createElement( 'span' );
		$value->setAttribute( 'class', 'trust-stat-banner__value' );
		$value->appendChild( $doc->createTextNode( 'DE · ES · HR' ) );
		$banner->appendChild( $value );

		$body = $doc->createElement( 'span' );
		$body->setAttribute( 'class', 'trust-stat-banner__body' );
		$desc_el = $doc->createElement( 'span' );
		$desc_el->setAttribute( 'class', 'trust-stat-banner__desc' );
		$desc_el->appendChild( $doc->createTextNode( $desc ) );
		$body->appendChild( $desc_el );
		$banner->appendChild( $body );

		if ( ! $banner->hasAttribute( 'role' ) ) {
			$banner->setAttribute( 'role', 'note' );
		}
		if ( ! $banner->hasAttribute( 'aria-label' ) ) {
			$banner->setAttribute( 'aria-label', $desc );
		}
	}
}

/**
 * Runtime CTA/content patches for Kaufen & Verkaufen (works without re-import).
 *
 * @param DOMXPath   $xpath XPath.
 * @param DOMElement $root  Fragment root.
 */
function volks_apply_landing_cta_patches_dom( DOMXPath $xpath, DOMElement $root ) {
	if ( ! function_exists( 'volks_get_current_source_key' ) ) {
		return;
	}

	$key = volks_get_current_source_key();

	if ( 'volks-kaufen-v1' === $key ) {
		volks_dom_remove_ctas_by_labels(
			$xpath,
			$root,
			'region',
			array( 'Karte auf der Startseite' )
		);
		foreach ( $xpath->query( './/section[@id="angebote"]', $root ) as $section ) {
			if ( ! $section instanceof DOMElement ) {
				continue;
			}
			$class = trim( preg_replace( '/\bvolks-hidden\b/', '', (string) $section->getAttribute( 'class' ) ) );
			$section->setAttribute( 'class', preg_replace( '/\s+/', ' ', $class ) );
			$section->removeAttribute( 'hidden' );
		}

		$archive_url = post_type_exists( 'volks_property' )
			? get_post_type_archive_link( 'volks_property' )
			: home_url( '/immobilien/' );
		$cta_locations = array(
			array(
				'query'   => './/section[@id="hero"]//div[contains(concat(" ", normalize-space(@class), " "), " hero-actions ")]',
				'label'   => 'Immobilien ansehen',
				'class'   => 'btn btn-primary btn-lg',
				'prepend' => true,
			),
			array(
				'query'   => './/section[@id="kontakt-abschluss"]//div[contains(concat(" ", normalize-space(@class), " "), " cta-actions--main ")]',
				'label'   => 'Immobilien ansehen',
				'class'   => 'btn btn-outline-accent btn-lg',
				'prepend' => false,
			),
		);
		foreach ( $cta_locations as $cta ) {
			$row = $xpath->query( $cta['query'], $root )->item( 0 );
			if ( ! $row instanceof DOMElement ) {
				continue;
			}
			$exists = false;
			foreach ( $xpath->query( './/a', $row ) as $link ) {
				if ( $link instanceof DOMElement && untrailingslashit( (string) $link->getAttribute( 'href' ) ) === untrailingslashit( (string) $archive_url ) ) {
					$exists = true;
					break;
				}
			}
			if ( $exists ) {
				continue;
			}
			$link = $root->ownerDocument->createElement( 'a', $cta['label'] );
			$link->setAttribute( 'href', $archive_url );
			$link->setAttribute( 'class', $cta['class'] );
			if ( $cta['prepend'] && $row->firstChild ) {
				$row->insertBefore( $link, $row->firstChild );
			} else {
				$row->appendChild( $link );
			}
		}

		volks_dom_promote_solo_cta_rows( $xpath, $root, './/section[@id="hero"]//div[contains(@class,"hero-actions")]' );
		volks_dom_promote_solo_cta_rows( $xpath, $root, './/section[@id="kaeufergruppen"]//div[contains(@class,"section-cta-row")]' );
		volks_dom_promote_solo_cta_rows( $xpath, $root, './/section[@id="region"]//div[contains(@class,"section-cta-row")]' );
	}

	if ( 'volks-verkaufen-v1' === $key ) {
		volks_dom_remove_ctas_by_labels(
			$xpath,
			$root,
			'region',
			array( 'Interaktive Karte auf der Startseite' )
		);

		foreach ( $xpath->query( './/section[@id="objektarten"]//*[@data-immo-panel]', $root ) as $panel ) {
			if ( $panel instanceof DOMElement && $panel->hasAttribute( 'style' ) ) {
				$panel->removeAttribute( 'style' );
			}
		}

		foreach ( $xpath->query( './/section[@id="diskret"]//a[contains(@class,"btn")]', $root ) as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			if ( 'Anonym Rückruf' === trim( $link->textContent ) ) {
				$link->textContent = 'Rückruf';
			}
		}

		volks_dom_promote_solo_cta_rows( $xpath, $root, './/section[@id="region"]//div[contains(@class,"section-cta-row")]' );
	}

	if ( 'volks-ausland-v1' === $key ) {
		volks_apply_ausland_markup_patches_dom( $xpath, $root );
	}

	if ( 'volks-bewerten-v1' === $key ) {
		foreach ( $xpath->query( './/section[@id="kontakt-cta"]//a[contains(@class,"btn")]', $root ) as $link ) {
			if ( ! $link instanceof DOMElement ) {
				continue;
			}
			if ( 'Persönliche Bewertung' === trim( $link->textContent ) ) {
				$link->setAttribute( 'href', volks_section_url( 'kontakt-formular' ) );
			}
		}
	}
}

/**
 * Rewrite href/src attributes in an HTML fragment.
 *
 * @param string $html Raw HTML.
 * @return string
 */
function volks_normalize_html_fragment( $html ) {
	$html = trim( (string) $html );
	if ( '' === $html ) {
		return $html;
	}

	if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
		Leadwerk_Volks_Media::warm_path_cache();
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
			$html = Leadwerk_Volks_Media::rewrite_html_fragment( $html );
		}
		if ( function_exists( 'volks_swap_static_contact_form_for_wpforms' ) ) {
			$html = volks_swap_static_contact_form_for_wpforms( $html );
		}
		return $html;
	}

	$wrapped = '<?xml encoding="utf-8" ?><html><body><div id="volks-root">' . $html . '</div></body></html>';
	$dom     = new DOMDocument( '1.0', 'UTF-8' );
	libxml_use_internal_errors( true );
	$dom->loadHTML( $wrapped );
	libxml_clear_errors();

	$xpath = new DOMXPath( $dom );
	$root  = $xpath->query( '//*[@id="volks-root"]' )->item( 0 );
	if ( ! $root instanceof DOMElement ) {
		return $html;
	}

	foreach ( $xpath->query( './/*[@data-volks-page-key]', $root ) as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		$page_key = sanitize_key( (string) $node->getAttribute( 'data-volks-page-key' ) );
		$anchor   = trim( (string) $node->getAttribute( 'data-volks-anchor' ) );
		if ( '' === $page_key ) {
			continue;
		}
		if ( '' !== $anchor ) {
			if ( 'volks-home-v1' === $page_key ) {
				$url = volks_section_url( $anchor );
			} else {
				$base = volks_get_page_url( $page_key, home_url( '/' ) );
				$url  = trailingslashit( $base ) . '#' . sanitize_title( $anchor );
			}
		} else {
			$url = volks_get_page_url( $page_key, home_url( '/' ) );
		}
		$node->setAttribute( 'href', $url );
		$node->removeAttribute( 'data-volks-page-key' );
		$node->removeAttribute( 'data-volks-anchor' );
	}

	foreach ( $xpath->query( './/*[@href]', $root ) as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		$raw = trim( (string) $node->getAttribute( 'href' ) );
		if ( '' === $raw ) {
			continue;
		}
		$resolved = volks_resolve_href( $raw );
		$node->setAttribute( 'href', $resolved );
		$site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$link_host = wp_parse_url( $resolved, PHP_URL_HOST );
		if ( $site_host && $link_host && strtolower( (string) $site_host ) === strtolower( (string) $link_host ) ) {
			$node->removeAttribute( 'target' );
			$node->removeAttribute( 'rel' );
		}
	}

	volks_apply_landing_cta_patches_dom( $xpath, $root );

	$out = '';
	foreach ( $root->childNodes as $child ) {
		$out .= $dom->saveHTML( $child );
	}

	if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
		$out = Leadwerk_Volks_Media::rewrite_html_fragment( $out );
	}

	if ( function_exists( 'volks_swap_static_contact_form_for_wpforms' ) ) {
		$out = volks_swap_static_contact_form_for_wpforms( $out );
	}

	$out = volks_apply_section_css_background_variables( $out );
	$out = preg_replace_callback(
		'/<svg\b[^>]*>[\s\S]*?<\/svg>/i',
		static function ( $matches ) {
			return volks_normalize_svg_markup( $matches[0] );
		},
		$out
	);

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) ) {
		$fotos_src = 0;
		$http_src  = 0;
		if ( preg_match_all( '/\bsrc=(["\'])([^"\']+)\1/i', $out, $src_matches ) ) {
			foreach ( $src_matches[2] as $src ) {
				if ( false !== stripos( $src, 'Fotos/' ) ) {
					++$fotos_src;
				} elseif ( preg_match( '#^https?://#i', $src ) ) {
					++$http_src;
				}
			}
		}
		volks_agent_debug_log(
			'D',
			'link-rewrite.php:volks_normalize_html_fragment',
			'html_img_src_stats',
			array(
				'fotos_src' => $fotos_src,
				'http_src'  => $http_src,
				'has_wizard' => false !== strpos( $out, 'valuationWizard' ),
				'promo_count' => substr_count( $out, 'valuation-promo-badge' ),
			)
		);
	}
	// #endregion

	return $out;
}
