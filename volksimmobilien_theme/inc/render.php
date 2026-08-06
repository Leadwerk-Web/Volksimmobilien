<?php
/**
 * Page content rendering from Leadwerk Fields.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render one field group to HTML (Leadwerk Importer validation / repair).
 *
 * @param array<string,mixed> $group   Field group schema.
 * @param mixed               $value   Stored field value.
 * @param int                 $post_id Post ID (unused, API compatibility).
 * @return string
 */
function leadwerk_theme_render_exact_page_group( $group, $value, $post_id = 0 ) {
	unset( $post_id );

	if ( ! is_array( $group ) || ! is_array( $value ) ) {
		return '';
	}

	if ( empty( $group['layouts'] ) ) {
		return volks_render_legal_page( $value );
	}

	$html = '';
	foreach ( $value as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$layout = (string) ( $section['acf_fc_layout'] ?? '' );
		$section_id = sanitize_key( (string) ( $section['section_id'] ?? '' ) );
		$original_html = (string) ( $section['original_html'] ?? '' );
		if ( 'angebote' === $section_id && '' !== trim( $original_html ) ) {
			$offers_html = volks_normalize_html_fragment( $original_html );
			$html .= apply_filters( 'volks_offers_showcase_html', $offers_html );
		} elseif ( 'html_section' === $layout ) {
			$html .= volks_render_html_section( $section );
		} elseif ( function_exists( 'volks_render_structured_section' ) ) {
			$html .= volks_render_structured_section( $layout, $section );
		}
	}

	return $html;
}

/**
 * Render a regular WordPress page that is not part of the imported schema.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function volks_render_generic_page_content( $post_id ) {
	$title   = get_the_title( $post_id );
	$content = apply_filters( 'the_content', (string) get_post_field( 'post_content', $post_id ) );

	ob_start();
	?>
	<section class="section legal-content-section">
		<div class="container">
			<article class="legal-prose">
				<?php if ( '' !== trim( (string) $title ) ) : ?>
					<h1 class="legal-prose__title"><?php echo esc_html( $title ); ?></h1>
				<?php endif; ?>
				<div class="legal-prose__body">
					<?php echo wp_kses_post( $content ); ?>
				</div>
			</article>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render the current page body (sections from Leadwerk Fields).
 *
 * @param int $post_id Post ID.
 * @return string
 */
function volks_render_page_content( $post_id = 0 ) {
	static $render_depth = 0;
	$t0 = microtime( true );

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) ) {
		volks_agent_debug_log(
			'B',
			'render.php:volks_render_page_content',
			'page_render_enter',
			array(
				'render_depth' => $render_depth,
				'post_id_arg'  => (int) $post_id,
			)
		);
	}
	// #endregion

	if ( $render_depth > 0 ) {
		// #region agent log
		if ( function_exists( 'volks_agent_debug_log' ) ) {
			volks_agent_debug_log(
				'B',
				'render.php:volks_render_page_content',
				'page_render_blocked_recursive',
				array( 'render_depth' => $render_depth )
			);
		}
		// #endregion
		return '';
	}

	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	if ( ! $post_id ) {
		return '';
	}
	if ( ! class_exists( 'Leadwerk_Content_Schema' ) ) {
		return volks_render_generic_page_content( $post_id );
	}

	++$render_depth;

	if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
		Leadwerk_Volks_Media::warm_path_cache();
	}

	$group = Leadwerk_Content_Schema::get_group_for_post( $post_id );
	if ( ! $group || empty( $group['field_name'] ) ) {
		--$render_depth;
		return volks_render_generic_page_content( $post_id );
	}

	$field_name = (string) $group['field_name'];
	$value      = function_exists( 'volks_get_stored_field' )
		? volks_get_stored_field( $field_name, $post_id )
		: ( function_exists( 'get_field' ) ? get_field( $field_name, $post_id ) : null );

	if ( empty( $group['layouts'] ) ) {
		$html = volks_render_legal_page( $value );
		--$render_depth;
		return $html;
	}

	$sections = is_array( $value ) ? array_values( $value ) : array();
	if ( empty( $sections ) ) {
		--$render_depth;
		return '<div class="container"><p class="runtime-notice">Inhalt noch nicht importiert.</p></div>';
	}

	$html = '';
	foreach ( $sections as $section ) {
		if ( function_exists( 'volks_prepare_section_for_render' ) ) {
			$section = volks_prepare_section_for_render( $section );
		}
		if ( ! is_array( $section ) ) {
			continue;
		}
		$layout = (string) ( $section['acf_fc_layout'] ?? '' );
		$section_id = sanitize_key( (string) ( $section['section_id'] ?? '' ) );
		$original_html = (string) ( $section['original_html'] ?? '' );
		if ( 'angebote' === $section_id && '' !== trim( $original_html ) ) {
			$offers_html = volks_normalize_html_fragment( $original_html );
			$html .= apply_filters( 'volks_offers_showcase_html', $offers_html );
		} elseif ( 'html_section' === $layout ) {
			$html .= volks_render_html_section( $section );
		} elseif ( function_exists( 'volks_render_structured_section' ) ) {
			$html .= volks_render_structured_section( $layout, $section );
		}

		// #region agent log
		if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) ) {
			volks_agent_debug_log(
				'A',
				'render.php:section_loop',
				'bewerten_section_rendered',
				array(
					'layout'      => $layout,
					'section_id'  => (string) ( $section['section_id'] ?? '' ),
					'cards_count' => is_array( $section['cards'] ?? null ) ? count( $section['cards'] ) : 0,
				)
			);
		}
		// #endregion
	}

	--$render_depth;

	if ( function_exists( 'volks_page_has_faq_schema' ) && volks_page_has_faq_schema( $post_id ) && function_exists( 'volks_strip_faq_microdata_markup' ) ) {
		$html = volks_strip_faq_microdata_markup( $html );
	}

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) ) {
		volks_agent_debug_log(
			'E',
			'render.php:volks_render_page_content',
			'page_render_exit',
			array(
				'post_id'        => $post_id,
				'slug'           => (string) get_post_field( 'post_name', $post_id ),
				'section_count'  => count( $sections ),
				'html_len'       => strlen( $html ),
				'duration_ms'    => (int) round( ( microtime( true ) - $t0 ) * 1000 ),
				'wpforms_opt'    => function_exists( 'volks_get_wpforms_option_value' ) ? volks_get_wpforms_option_value() : '',
				'runId'          => 'post-fix',
			)
		);
	}
	// #endregion

	return $html;
}

/**
 * Render one imported HTML section.
 *
 * @param array<string,mixed> $section Section data.
 * @return string
 */
function volks_render_html_section( $section ) {
	$raw = (string) ( $section['html'] ?? '' );
	if ( '' === trim( $raw ) ) {
		return '';
	}

	return volks_normalize_html_fragment( $raw );
}

/**
 * Hero copy for legal-style utility pages.
 *
 * @param string $headline Page headline.
 * @return array{eyebrow:string,sub:string,title?:string}|null
 */
function volks_get_legal_hero_meta( $headline ) {
	$headline = trim( (string) $headline );
	$map      = array(
		'Impressum'            => array(
			'eyebrow' => 'Rechtliche Hinweise',
			'sub'     => 'Pflichtangaben nach TMG und ergänzende Transparenz zu Ansprechpartnern und externen Leistungen.',
			'title'   => 'Impressum',
		),
		'Anbieterkennzeichnung' => array(
			'eyebrow' => 'Rechtliche Hinweise',
			'sub'     => 'Pflichtangaben nach TMG und ergänzende Transparenz zu Ansprechpartnern und externen Leistungen.',
			'title'   => 'Impressum',
		),
		'Datenschutzerklärung' => array(
			'eyebrow' => 'Datenschutz',
			'sub'     => 'Transparente Informationen zur Verarbeitung personenbezogener Daten auf dieser Website – gemäß DSGVO und TDDDG.',
			'title'   => 'Datenschutzerklärung',
		),
		'1. Datenschutz auf einen Blick' => array(
			'eyebrow' => 'Datenschutz',
			'sub'     => 'Transparente Informationen zur Verarbeitung personenbezogener Daten auf dieser Website – gemäß DSGVO und TDDDG.',
			'title'   => 'Datenschutzerklärung',
		),
		'Vielen Dank!'         => array(
			'eyebrow' => 'Anfrage erhalten',
			'sub'     => 'Ihre Nachricht ist bei uns eingegangen – wir melden uns schnellstmöglich bei Ihnen.',
		),
		'Seite nicht gefunden' => array(
			'eyebrow' => 'Fehler 404',
			'sub'     => 'Die angeforderte Seite existiert nicht oder wurde verschoben.',
		),
	);

	return $map[ $headline ] ?? null;
}

/**
 * Render impressum/datenschutz/danke/404 flat fields.
 *
 * @param mixed $value Field value.
 * @return string
 */
function volks_render_legal_page( $value ) {
	$value     = is_array( $value ) ? $value : array();
	$headline  = (string) ( $value['headline'] ?? '' );
	$content   = volks_normalize_html_fragment( (string) ( $value['content'] ?? '' ) );
	$hero_meta = volks_get_legal_hero_meta( $headline );
	$hero_title = is_array( $hero_meta ) && '' !== trim( (string) ( $hero_meta['title'] ?? '' ) )
		? (string) $hero_meta['title']
		: $headline;

	ob_start();

	if ( is_array( $hero_meta ) ) {
		?>
		<section class="hero hero--legal" id="hero" aria-labelledby="volks-legal-hero-heading">
			<div class="hero-bg">
				<div class="hero-static-bg" aria-hidden="true"></div>
				<div class="hero-overlay"></div>
			</div>
			<div class="container hero-content">
				<div class="hero-text">
					<?php if ( '' !== trim( (string) ( $hero_meta['eyebrow'] ?? '' ) ) ) : ?>
						<p class="section-eyebrow reveal"><?php echo esc_html( (string) $hero_meta['eyebrow'] ); ?></p>
					<?php endif; ?>
					<h1 id="volks-legal-hero-heading" class="hero-title reveal"><?php echo esc_html( $hero_title ); ?></h1>
					<?php if ( '' !== trim( (string) ( $hero_meta['sub'] ?? '' ) ) ) : ?>
						<p class="hero-sub reveal"><?php echo esc_html( (string) $hero_meta['sub'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
	}
	?>
	<section class="section legal-content-section">
		<div class="container">
			<article class="legal-prose">
				<?php if ( '' !== trim( $headline ) ) : ?>
					<?php if ( is_array( $hero_meta ) ) : ?>
						<h2 class="legal-prose__title"><?php echo esc_html( $headline ); ?></h2>
					<?php else : ?>
						<h1 class="legal-prose__title"><?php echo esc_html( $headline ); ?></h1>
					<?php endif; ?>
				<?php endif; ?>
				<div class="legal-prose__body">
					<?php echo wp_kses_post( $content ); ?>
				</div>
			</article>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render WordPress 404 content (imported page or fallback).
 *
 * @return string
 */
function volks_render_404_page() {
	$post_id = volks_get_page_id_by_source_key( 'volks-404-v1' );
	if ( $post_id > 0 && function_exists( 'get_field' ) && class_exists( 'Leadwerk_Content_Schema' ) ) {
		return volks_render_page_content( $post_id );
	}

	$home = trailingslashit( home_url( '/' ) );

	return volks_render_legal_page(
		array(
			'headline' => 'Seite nicht gefunden',
			'content'  => sprintf(
				'<p class="legal-prose__lead">Die von Ihnen aufgerufene Adresse ist bei uns nicht verfügbar. Prüfen Sie die URL oder kehren Sie zur Startseite zurück.</p><p class="legal-prose__nav-back"><a href="%1$s" class="btn btn-primary btn-lg">Zur Startseite</a> <a href="%2$s" class="btn btn-soft btn-lg">Kontakt aufnehmen</a></p>',
				esc_url( $home ),
				esc_url( volks_home_section_url( 'kontakt-formular' ) )
			),
		)
	);
}

/**
 * Body class from imported meta.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function volks_get_page_body_class( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	$class   = (string) get_post_meta( $post_id, 'leadwerk_body_class', true );
	if ( '' !== trim( $class ) ) {
		return $class;
	}

	$key = (string) get_post_meta( $post_id, 'leadwerk_source_key', true );
	if ( 'volks-home-v1' === $key ) {
		return 'page-home';
	}
	if ( 'volks-bewerten-v1' === $key ) {
		return 'page-bewerten';
	}
	if ( 'volks-mallorca-v1' === $key ) {
		return 'page-mallorca';
	}
	if ( 'volks-ausland-v1' === $key ) {
		return 'page-mallorca page-ausland';
	}
	if ( 'volks-kaufen-v1' === $key ) {
		return 'page-kauf';
	}
	if ( 'volks-verkaufen-v1' === $key ) {
		return 'page-verkauf';
	}
	if ( 'volks-danke-v1' === $key ) {
		return 'page-legal page-danke';
	}
	if ( 'volks-404-v1' === $key ) {
		return 'page-legal page-not-found';
	}
	if ( in_array( $key, array( 'volks-impressum-v1', 'volks-datenschutz-v1' ), true ) ) {
		return 'page-legal';
	}

	return 'page-' . sanitize_html_class( get_post_field( 'post_name', $post_id ) );
}
