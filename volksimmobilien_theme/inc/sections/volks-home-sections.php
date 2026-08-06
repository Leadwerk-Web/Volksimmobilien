<?php
/**
 * Structured homepage section renderers.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string               $layout  Layout key.
 * @param array<string,mixed>  $section Section data.
 * @return string
 */
function volks_render_structured_section( $layout, $section ) {
	if ( ! is_array( $section ) ) {
		return '';
	}

	switch ( (string) $layout ) {
		case 'volks_editable_html_section':
			return volks_render_editable_html_section( $section );
		case 'volks_hero':
			return volks_render_section_hero( $section );
		case 'volks_intro':
			return volks_render_section_intro( $section );
		case 'volks_valuation':
			return volks_render_section_valuation( $section );
		case 'volks_process':
			return volks_render_section_process( $section );
		case 'volks_region':
			return volks_render_section_region( $section );
		case 'volks_weil_slider':
			return volks_render_section_weil_slider( $section );
		case 'volks_trust':
			return volks_render_section_trust( $section );
		case 'volks_cta':
			return volks_render_section_cta( $section );
		case 'volks_contact':
			return volks_render_section_contact( $section );
		default:
			return '';
	}
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_hero( $s ) {
	$id       = volks_esc_text( $s['section_id'] ?? 'hero' );
	$video    = volks_video_source_url( $s['video'] ?? 0 );
	$modifier = trim( (string) ( $s['hero_modifier'] ?? '' ) );
	$classes  = 'hero' . ( '' !== $modifier ? ' ' . sanitize_html_class( $modifier ) : '' );
	$bg_url   = volks_attachment_url( (int) ( $s['background_image'] ?? 0 ) );

	ob_start();
	?>
	<section class="<?php echo esc_attr( $classes ); ?>" id="<?php echo esc_attr( $id ); ?>">
		<div class="hero-bg">
			<?php if ( '' !== $video ) : ?>
			<video class="hero-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
				<source src="<?php echo esc_url( $video ); ?>" type="video/mp4">
			</video>
			<?php elseif ( '' !== $bg_url ) : ?>
			<div class="hero-static-bg" aria-hidden="true" style="background-image:url(<?php echo esc_url( $bg_url ); ?>);"></div>
			<?php else : ?>
			<div class="hero-static-bg" aria-hidden="true"></div>
			<?php endif; ?>
			<div class="hero-overlay"></div>
		</div>
		<div class="container hero-content">
			<div class="hero-text">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?>
				<p class="section-eyebrow reveal"><?php echo volks_esc_text( $s['eyebrow'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $s['title'] ) ) : ?>
				<h1 class="hero-title reveal"><?php echo volks_heading_inner( $s['title'] ); ?></h1>
				<?php endif; ?>
				<?php if ( ! empty( $s['subtitle'] ) ) : ?>
				<p class="hero-sub reveal"><?php echo volks_esc_text( $s['subtitle'] ); ?></p>
				<?php endif; ?>
				<div class="hero-actions reveal">
					<?php echo volks_btn_link( $s['btn_primary_url'] ?? '', $s['btn_primary_label'] ?? '', 'btn btn-primary btn-lg' ); ?>
					<?php echo volks_btn_link( $s['btn_secondary_url'] ?? '', $s['btn_secondary_label'] ?? '', 'btn btn-outline-light btn-lg' ); ?>
				</div>
			</div>
		</div>
		<?php
		$tiles = is_array( $s['intent_tiles'] ?? null ) ? $s['intent_tiles'] : array();
		if ( ! empty( $tiles ) ) :
		?>
		<div class="intent-hub-wrapper">
			<div class="container">
				<div class="intent-hub reveal">
					<?php
					$tiles = is_array( $s['intent_tiles'] ?? null ) ? $s['intent_tiles'] : array();
					foreach ( $tiles as $tile ) :
						if ( ! is_array( $tile ) ) {
							continue;
						}
						$modifier = trim( (string) ( $tile['style_modifier'] ?? '' ) );
						$class    = 'intent-tile' . ( '' !== $modifier ? ' ' . sanitize_html_class( $modifier ) : '' );
						$href     = function_exists( 'volks_resolve_href' ) ? volks_resolve_href( (string) ( $tile['link_url'] ?? '' ) ) : esc_url( (string) ( $tile['link_url'] ?? '' ) );
						$target   = ( 0 === strpos( $href, 'http' ) && false === strpos( $href, home_url() ) ) ? ' target="_blank" rel="noopener"' : '';
						?>
					<a href="<?php echo esc_url( $href ); ?>" class="<?php echo esc_attr( $class ); ?>"<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<div class="intent-tile-icon">
							<?php echo volks_img_tag( (int) ( $tile['icon'] ?? 0 ), 'medium', array( 'aria-hidden' => 'true' ) ); ?>
						</div>
						<div class="intent-tile-body">
							<span class="intent-tile-label"><?php echo volks_esc_text( $tile['label'] ?? '' ); ?></span>
							<span class="intent-tile-desc"><?php echo volks_esc_text( $tile['description'] ?? '' ); ?></span>
						</div>
						<svg class="intent-tile-arrow" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
					</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_intro( $s ) {
	$id              = volks_esc_text( $s['section_id'] ?? 'einleitung' );
	$video_poster    = volks_attachment_url( (int) ( $s['video_poster'] ?? 0 ) );
	$video           = volks_video_source_url( $s['video'] ?? 0 );
	$has_intro_video = '' !== $video_poster && '' !== $video;
	$video_cues      = array(
		array( 'start' => 0, 'end' => 2.58, 'text' => 'Eine Immobilie ist mehr als nur vier Wände.' ),
		array( 'start' => 2.68, 'end' => 5.5, 'text' => 'Sie ist der Ort, an dem Erinnerungen entstehen,' ),
		array( 'start' => 5.5, 'end' => 8.56, 'text' => 'an dem Familien wachsen, an dem Zukunft beginnt.' ),
		array( 'start' => 8.76, 'end' => 13.36, 'text' => "Genau deshalb beginnt unsere Arbeit nicht nur\nmit einem Exposé, sondern mit dem Zuhören." ),
		array( 'start' => 13.54, 'end' => 19.92, 'text' => "Wir nehmen uns Zeit für Ihre Wünsche und für Ihre Ziele\nund für die Geschichte hinter jeder Immobilie." ),
		array( 'start' => 20.06, 'end' => 25.18, 'text' => "Denn kein Zuhause ist wie das andere –\nund kein Mensch hat dieselben Vorstellungen vom Wohnen." ),
		array( 'start' => 25.18, 'end' => 29.2, 'text' => 'Mit regionaler Marktkenntnis, mit einer persönlichen Betreuung' ),
		array( 'start' => 29.2, 'end' => 32.76, 'text' => "und einem Service, der oft einen Schritt weitergeht,\nbegleiten wir Sie auf dem kompletten Weg." ),
		array( 'start' => 33.02, 'end' => 39.26, 'text' => "Vom ersten Gespräch über jede wichtige Entscheidung\nbis zu dem Moment, wo aus einer Immobilie ein neues Kapitel wird." ),
		array( 'start' => 39.6, 'end' => 45.74, 'text' => "Unser Anspruch ist dabei einfach:\nnicht nur Ihre Erwartungen zu erfüllen, sondern mehr zu tun, als Sie erwarten." ),
		array( 'start' => 46.64, 'end' => 49.86, 'text' => 'Volksimmobilien – persönlich, verlässlich und an Ihrer Seite.' ),
	);
	ob_start();
	?>
	<section class="section intro-section" id="<?php echo esc_attr( $id ); ?>" aria-labelledby="intro-heading">
		<div class="container">
			<div class="intro-grid">
				<div class="intro-text reveal">
					<?php if ( ! empty( $s['eyebrow'] ) ) : ?><span class="section-eyebrow"><?php echo volks_esc_text( $s['eyebrow'] ); ?></span><?php endif; ?>
					<?php if ( ! empty( $s['title'] ) ) : ?><h2 id="intro-heading" class="intro-title"><?php echo volks_esc_text( $s['title'] ); ?></h2><?php endif; ?>
					<?php if ( ! empty( $s['lead'] ) ) : ?><p class="intro-lead"><?php echo volks_esc_text( $s['lead'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $s['body'] ) ) : ?><p><?php echo volks_esc_text( $s['body'] ); ?></p><?php endif; ?>
					<div class="section-cta-row intro-cta reveal">
						<?php echo volks_btn_link( $s['cta_primary_url'] ?? '', $s['cta_primary_label'] ?? '', 'btn btn-primary btn-lg' ); ?>
						<?php echo volks_btn_link( $s['cta_secondary_url'] ?? '', $s['cta_secondary_label'] ?? '', 'btn btn-outline btn-lg' ); ?>
					</div>
				</div>
				<aside class="intro-trust<?php echo $has_intro_video ? ' intro-trust--video' : ' reveal'; ?>" aria-label="Vertrauenshinweise">
					<?php if ( $has_intro_video ) : ?>
					<div class="intro-video-stack">
						<div class="intro-video-frame">
							<button type="button" class="intro-video-teaser" data-video-lightbox="videoLightbox" aria-haspopup="dialog" aria-controls="videoLightbox" aria-label="Imagefilm abspielen">
								<img class="intro-video-teaser__poster" src="<?php echo esc_url( $video_poster ); ?>" alt="" width="411" height="617" loading="lazy" decoding="async">
								<span class="intro-video-teaser__overlay" aria-hidden="true"></span>
							</button>
							<div class="intro-video-info">
								<div class="trust-stat-banner trust-stat-banner--solo" role="note">
									<span class="trust-stat-banner__value"><?php echo volks_inline_html( $s['stat_value'] ?? '' ); ?></span>
									<span class="trust-stat-banner__body">
										<span class="trust-stat-banner__title"><?php echo volks_esc_text( $s['stat_title'] ?? '' ); ?></span>
										<span class="trust-stat-banner__desc"><?php echo volks_esc_text( $s['stat_desc'] ?? '' ); ?></span>
									</span>
									<?php echo volks_btn_link( $s['stat_cta_url'] ?? '', $s['stat_cta_label'] ?? '', 'btn btn-primary btn-lg trust-stat-banner__cta' ); ?>
								</div>
								<button type="button" class="intro-video-play" data-video-lightbox="videoLightbox" aria-haspopup="dialog" aria-controls="videoLightbox" aria-label="Imagefilm abspielen">
									<svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5.14v13.72L19 12 8 5.14z"/></svg>
								</button>
							</div>
						</div>
					</div>
					<?php else : ?>
					<div class="trust-panel-stack trust-panel-stack--solo">
						<div class="trust-stat-banner trust-stat-banner--solo" role="note">
							<span class="trust-stat-banner__value"><?php echo volks_inline_html( $s['stat_value'] ?? '' ); ?></span>
							<span class="trust-stat-banner__body">
								<span class="trust-stat-banner__title"><?php echo volks_esc_text( $s['stat_title'] ?? '' ); ?></span>
								<span class="trust-stat-banner__desc"><?php echo volks_esc_text( $s['stat_desc'] ?? '' ); ?></span>
							</span>
							<?php echo volks_btn_link( $s['stat_cta_url'] ?? '', $s['stat_cta_label'] ?? '', 'btn btn-primary btn-lg trust-stat-banner__cta' ); ?>
						</div>
					</div>
					<?php endif; ?>
				</aside>
			</div>
		</div>
	</section>
	<?php if ( $has_intro_video ) : ?>
	<dialog class="video-lightbox" id="videoLightbox" aria-label="Imagefilm">
		<div class="video-lightbox__panel">
			<button type="button" class="video-lightbox__close" data-video-lightbox-close aria-label="Video schließen">
				<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg>
			</button>
			<div class="video-lightbox__media">
				<video class="video-lightbox__video" controls playsinline preload="metadata">
					<source src="<?php echo esc_url( $video ); ?>" type="video/mp4">
				</video>
				<div class="video-lightbox__captions" data-video-captions aria-live="polite" hidden></div>
			</div>
		</div>
	</dialog>
	<script type="application/json" id="videoLightboxCues"><?php echo wp_json_encode( $video_cues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
	<?php endif; ?>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_valuation( $s ) {
	$id = volks_esc_text( $s['section_id'] ?? 'wertermittlung' );
	$section_class = 'section valuation-options-section reveal';
	if ( function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) ) {
		$section_class .= ' valuation-options-section--bewerten';
	}
	ob_start();
	?>
	<section class="<?php echo esc_attr( $section_class ); ?>" id="<?php echo esc_attr( $id ); ?>">
		<div class="container">
			<div class="section-header reveal">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?><span class="section-eyebrow"><?php echo volks_esc_text( $s['eyebrow'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $s['title'] ) ) : ?><h2 class="section-title"><?php echo volks_esc_text( $s['title'] ); ?></h2><?php endif; ?>
				<?php if ( ! empty( $s['subtitle'] ) ) : ?><p class="section-sub"><?php echo volks_esc_text( $s['subtitle'] ); ?></p><?php endif; ?>
			</div>
			<div class="valuation-options-grid reveal">
				<?php
				$cards = is_array( $s['cards'] ?? null ) ? $s['cards'] : array();
				foreach ( $cards as $card ) :
					if ( ! is_array( $card ) ) {
						continue;
					}
					$featured = ! empty( $card['is_featured'] );
					$card_class = 'valuation-option-card' . ( $featured ? ' valuation-option-card--featured' : '' );
					?>
				<article class="<?php echo esc_attr( $card_class ); ?>">
					<?php if ( ! empty( $card['badge'] ) ) : ?>
					<span class="valuation-option-card__badge<?php echo $featured ? ' valuation-option-card__badge--featured' : ''; ?>"><?php echo volks_esc_text( $card['badge'] ); ?></span>
					<?php endif; ?>
					<?php if ( $featured && ! empty( $card['promo_amount'] ) ) : ?>
					<a href="<?php echo esc_url( function_exists( 'volks_resolve_href' ) ? volks_resolve_href( (string) ( $card['promo_url'] ?? '#kontakt-formular' ) ) : '#' ); ?>" class="valuation-promo-badge">
						<span class="valuation-promo-badge__inner">
							<span class="valuation-promo-badge__amount"><?php echo volks_inline_html( $card['promo_amount'] ); ?></span>
							<span class="valuation-promo-badge__title"><?php echo volks_esc_text( $card['promo_title'] ?? '' ); ?></span>
							<span class="valuation-promo-badge__sub"><?php echo volks_esc_text( $card['promo_sub'] ?? '' ); ?></span>
						</span>
					</a>
					<?php endif; ?>
					<div class="valuation-option-card__head">
						<span class="valuation-option-card__icon" aria-hidden="true"><?php echo function_exists( 'volks_valuation_option_icon_html' ) ? volks_valuation_option_icon_html( $card ) : volks_img_tag( (int) ( $card['icon'] ?? 0 ), 'medium' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="valuation-option-card__kicker"><?php echo volks_esc_text( $card['kicker'] ?? '' ); ?></span>
					</div>
					<p class="valuation-option-card__price">
						<?php if ( ! empty( $card['price_strike'] ) ) : ?><span class="valuation-option-card__price-strike"><?php echo volks_inline_html( $card['price_strike'] ); ?></span><?php endif; ?>
						<?php if ( $featured ) : ?>
						<span class="valuation-option-card__price-arrow" aria-hidden="true"><svg width="36" height="24" viewBox="0 0 36 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h22M25 6l8 6-8 6"/></svg></span>
						<?php endif; ?>
						<span class="valuation-option-card__price-main"><?php echo volks_inline_html( $card['price_main'] ?? '' ); ?></span>
						<span class="valuation-option-card__price-sub"><?php echo volks_esc_text( $card['price_sub'] ?? '' ); ?></span>
					</p>
					<?php echo volks_btn_link( $card['cta_url'] ?? '', $card['cta_label'] ?? '', 'btn btn-lg ' . ( $featured ? 'btn-primary' : 'btn-soft' ) . ' valuation-option-card__cta' ); ?>
					<hr class="valuation-option-card__divider">
					<?php echo volks_features_list_html( $card['features'] ?? '', $featured ); ?>
				</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	$out = (string) ob_get_clean();

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) ) {
		$cards = is_array( $s['cards'] ?? null ) ? $s['cards'] : array();
		volks_agent_debug_log(
			'C',
			'volks-home-sections.php:volks_render_section_valuation',
			'valuation_section_output',
			array(
				'section_id'        => (string) ( $s['section_id'] ?? '' ),
				'cards_count'       => count( $cards ),
				'promo_in_html'     => substr_count( $out, 'valuation-promo-badge' ),
				'featured_in_html'  => substr_count( $out, 'valuation-option-card--featured' ),
				'icon_img_in_html'  => substr_count( $out, 'valuation-option-card__icon' ),
				'html_len'          => strlen( $out ),
			)
		);
	}
	// #endregion

	return $out;
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_process( $s ) {
	$id = volks_esc_text( $s['section_id'] ?? 'prozess' );
	ob_start();
	?>
	<section class="section process-section" id="<?php echo esc_attr( $id ); ?>">
		<div class="container">
			<div class="section-header reveal">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?><span class="section-eyebrow"><?php echo volks_esc_text( $s['eyebrow'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $s['title'] ) ) : ?><h2 class="section-title"><?php echo volks_esc_text( $s['title'] ); ?></h2><?php endif; ?>
				<?php if ( ! empty( $s['subtitle'] ) ) : ?><p class="section-sub"><?php echo volks_esc_text( $s['subtitle'] ); ?></p><?php endif; ?>
			</div>
			<div class="process-timeline">
				<?php
				$steps = is_array( $s['steps'] ?? null ) ? $s['steps'] : array();
				$last  = count( $steps ) - 1;
				foreach ( $steps as $i => $step ) :
					if ( ! is_array( $step ) ) {
						continue;
					}
					?>
				<div class="process-step reveal">
					<div class="step-marker">
						<span class="step-number"><?php echo volks_esc_text( $step['number'] ?? (string) ( $i + 1 ) ); ?></span>
						<?php if ( $i < $last ) : ?><div class="step-line"></div><?php endif; ?>
					</div>
					<div class="step-content">
						<h3><?php echo volks_esc_text( $step['title'] ?? '' ); ?></h3>
						<p><?php echo volks_esc_text( $step['text'] ?? '' ); ?></p>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
			<div class="section-cta-row reveal">
				<?php echo volks_btn_link( $s['cta_primary_url'] ?? '', $s['cta_primary_label'] ?? '', 'btn btn-primary btn-lg' ); ?>
				<?php echo volks_btn_link( $s['cta_secondary_url'] ?? '', $s['cta_secondary_label'] ?? '', 'btn btn-outline btn-lg' ); ?>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_region( $s ) {
	$id = volks_esc_text( $s['section_id'] ?? 'regionen' );
	ob_start();
	?>
	<section class="section region-section region-section--marktgebiet reveal" id="<?php echo esc_attr( $id ); ?>">
		<div class="container">
			<div class="region-layout">
				<div class="region-left">
					<?php if ( ! empty( $s['eyebrow'] ) ) : ?><span class="section-eyebrow"><?php echo volks_esc_text( $s['eyebrow'] ); ?></span><?php endif; ?>
					<?php if ( ! empty( $s['title'] ) ) : ?><h2 class="region-title"><?php echo volks_heading_inner( $s['title'] ); ?></h2><?php endif; ?>
					<div class="region-intro">
						<?php if ( ! empty( $s['paragraph_1'] ) ) : ?><p><?php echo volks_esc_text( $s['paragraph_1'] ); ?></p><?php endif; ?>
						<?php if ( ! empty( $s['paragraph_2'] ) ) : ?><p><?php echo volks_esc_text( $s['paragraph_2'] ); ?></p><?php endif; ?>
					</div>
					<div class="region-stat-row">
						<?php
						$stats = function_exists( 'volks_sanitize_region_stats' )
							? volks_sanitize_region_stats( $s['stats'] ?? array() )
							: ( is_array( $s['stats'] ?? null ) ? $s['stats'] : array() );
						foreach ( $stats as $stat ) :
							if ( ! is_array( $stat ) ) {
								continue;
							}
							?>
						<div class="region-stat">
							<span class="region-stat-number"><?php echo volks_inline_html( $stat['number'] ?? '' ); ?></span>
							<span class="region-stat-label"><?php echo volks_esc_text( $stat['label'] ?? '' ); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="region-right region-right--kreis-map">
					<div class="kreis-map-stage kreis-map-stage--embed" data-kreis-map>
						<div class="kreis-map-svg-host" id="kreisMapSvgHost" role="group" aria-label="Karte des Kerngebiets zwischen Baden-Baden und Heidelberg">
							<?php
							$map_svg = function_exists( 'volks_normalize_svg_markup' )
								? volks_normalize_svg_markup( (string) ( $s['map_svg'] ?? '' ) )
								: (string) ( $s['map_svg'] ?? '' );
							echo $map_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin SVG field
							?>
						</div>
						<div class="kreis-map-tooltip" id="kreisMapTooltip" role="status" aria-live="polite" hidden>
							<span class="kreis-map-tooltip-type"></span>
							<span class="kreis-map-tooltip-name"></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_weil_slider( $s ) {
	$id = volks_esc_text( $s['section_id'] ?? 'haltung' );
	ob_start();
	?>
	<section class="weil-slider" id="<?php echo esc_attr( $id ); ?>">
		<div class="weil-slider-track">
			<?php
			$slides = function_exists( 'volks_sanitize_weil_slides' )
				? volks_sanitize_weil_slides( $s['slides'] ?? array() )
				: ( is_array( $s['slides'] ?? null ) ? $s['slides'] : array() );
			foreach ( $slides as $i => $slide ) :
				if ( ! is_array( $slide ) ) {
					continue;
				}
				$active = 0 === (int) $i ? ' active' : '';
				$style  = volks_bg_image_style( (int) ( $slide['image'] ?? 0 ) );
				?>
			<div class="weil-slide<?php echo esc_attr( $active ); ?>">
				<div class="weil-slide-media"<?php echo '' !== $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?> aria-hidden="true"></div>
				<div class="weil-slide-overlay"></div>
				<div class="weil-slide-content">
					<svg class="weil-slide-quote" width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M3 21c3-3 4-6 4-10H3V3h8v8c0 5-3 9-8 10zm12 0c3-3 4-6 4-10h-4V3h8v8c0 5-3 9-8 10z" fill="currentColor"/></svg>
					<h3 class="weil-slide-claim"><?php echo volks_esc_text( $slide['claim'] ?? '' ); ?></h3>
					<p><?php echo volks_esc_text( $slide['text'] ?? '' ); ?></p>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<div class="weil-slider-controls">
			<div class="weil-slider-dots"></div>
			<div class="weil-slider-progress"><div class="weil-slider-progress-bar"></div></div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_trust( $s ) {
	$id = volks_esc_text( $s['section_id'] ?? 'vertrauen' );
	ob_start();
	?>
	<section class="section trust-section" id="<?php echo esc_attr( $id ); ?>">
		<div class="container">
			<div class="section-header reveal">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?><span class="section-eyebrow"><?php echo volks_esc_text( $s['eyebrow'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $s['title'] ) ) : ?><h2 class="section-title"><?php echo volks_esc_text( $s['title'] ); ?></h2><?php endif; ?>
			</div>
			<div class="trust-bento reveal">
				<div class="trust-bento-image">
					<?php
					$alt = (string) ( $s['hero_image_alt'] ?? '' );
					echo volks_img_tag( (int) ( $s['hero_image'] ?? 0 ), 'large', array( 'loading' => 'lazy', 'alt' => $alt ) );
					?>
					<div class="trust-badge-group">
						<div class="trust-badge trust-badge--dark">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
							<div class="trust-badge-text">
								<span class="trust-badge-label"><?php echo volks_esc_text( $s['badge_24h_label'] ?? '' ); ?></span>
								<span class="trust-badge-sub"><?php echo volks_esc_text( $s['badge_24h_sub'] ?? '' ); ?></span>
							</div>
						</div>
						<div class="trust-badge trust-badge--accent">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
							<span class="trust-badge-label"><?php echo volks_esc_text( $s['badge_cert_label'] ?? '' ); ?></span>
						</div>
						<div class="trust-badge trust-badge--stat">
							<span class="trust-badge-number"><?php echo volks_inline_html( $s['badge_stat_number'] ?? '' ); ?></span>
							<span class="trust-badge-sub"><?php echo volks_esc_text( $s['badge_stat_label'] ?? '' ); ?></span>
						</div>
					</div>
				</div>
				<?php
				$cards = is_array( $s['cards'] ?? null ) ? $s['cards'] : array();
				$icons = array(
					'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
					'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>',
					'<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>',
				);
				foreach ( $cards as $i => $card ) :
					if ( ! is_array( $card ) ) {
						continue;
					}
					?>
				<div class="trust-bento-card">
					<div class="trust-bento-icon"><?php echo $icons[ $i ] ?? $icons[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<h3><?php echo volks_esc_text( $card['title'] ?? '' ); ?></h3>
					<p><?php echo volks_esc_text( $card['text'] ?? '' ); ?></p>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_cta( $s ) {
	$id = volks_esc_text( $s['section_id'] ?? 'kontakt-cta' );
	ob_start();
	?>
	<section class="section cta-section" id="<?php echo esc_attr( $id ); ?>">
		<div class="container">
			<div class="cta-panel reveal">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?><span class="section-eyebrow"><?php echo volks_esc_text( $s['eyebrow'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $s['title'] ) ) : ?><h2><?php echo volks_esc_text( $s['title'] ); ?></h2><?php endif; ?>
				<?php if ( ! empty( $s['lead'] ) ) : ?><p class="cta-lead"><?php echo volks_esc_text( $s['lead'] ); ?></p><?php endif; ?>
				<div class="cta-actions cta-actions--main">
					<?php echo volks_btn_link( $s['btn_main_url'] ?? '', $s['btn_main_label'] ?? '', 'btn btn-primary btn-lg' ); ?>
					<?php echo volks_btn_link( $s['btn_secondary_url'] ?? '', $s['btn_secondary_label'] ?? '', 'btn btn-outline-light btn-lg' ); ?>
				</div>
				<?php if ( ! empty( $s['divider_text'] ) ) : ?><p class="cta-divider"><span><?php echo volks_esc_text( $s['divider_text'] ); ?></span></p><?php endif; ?>
				<div class="cta-actions cta-actions--secondary">
					<?php echo volks_btn_link( $s['btn_tertiary_url'] ?? '', $s['btn_tertiary_label'] ?? '', 'btn btn-outline-accent btn-lg' ); ?>
					<?php echo volks_btn_link( $s['btn_email_url'] ?? '', $s['btn_email_label'] ?? '', 'btn btn-ghost-light btn-lg' ); ?>
				</div>
				<?php if ( ! empty( $s['microcopy'] ) ) : ?><p class="cta-micro"><?php echo volks_esc_text( $s['microcopy'] ); ?></p><?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * @param array<string,mixed> $s Section.
 * @return string
 */
function volks_render_section_contact( $s ) {
	$id = volks_esc_text( $s['section_id'] ?? 'kontakt-formular' );

	$wpforms = function_exists( 'volks_render_wpforms_markup' ) ? volks_render_wpforms_markup() : '';
	if ( '' !== $wpforms ) {
		$form = $wpforms;
	} else {
		$form = function_exists( 'volks_normalize_html_fragment' )
			? volks_normalize_html_fragment( (string) ( $s['form_html'] ?? '' ) )
			: (string) ( $s['form_html'] ?? '' );
	}

	ob_start();
	?>
	<section class="section home-contact-section" id="<?php echo esc_attr( $id ); ?>" aria-labelledby="home-contact-heading">
		<div class="container">
			<div class="section-header reveal">
				<?php if ( ! empty( $s['eyebrow'] ) ) : ?><span class="section-eyebrow"><?php echo volks_esc_text( $s['eyebrow'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $s['title'] ) ) : ?><h2 id="home-contact-heading" class="section-title"><?php echo volks_esc_text( $s['title'] ); ?></h2><?php endif; ?>
				<?php if ( ! empty( $s['subtitle'] ) ) : ?><p class="section-sub"><?php echo volks_esc_text( $s['subtitle'] ); ?></p><?php endif; ?>
			</div>
			<div class="home-contact-grid reveal">
				<div class="home-contact-form-card">
					<?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( '' === $wpforms && ! empty( $s['form_hint'] ) ) : ?>
					<p class="home-contact-hint" id="home-contact-hint"><?php echo volks_esc_text( $s['form_hint'] ); ?></p>
					<?php endif; ?>
				</div>
				<aside class="home-contact-aside" aria-label="Kontaktdaten">
					<?php
					$cards = is_array( $s['info_cards'] ?? null ) ? $s['info_cards'] : array();
					foreach ( $cards as $card ) :
						if ( ! is_array( $card ) ) {
							continue;
						}
						?>
					<div class="home-contact-info-card">
						<span class="home-contact-info-card__icon" aria-hidden="true"><?php echo function_exists( 'volks_render_contact_info_icon_html' ) ? volks_render_contact_info_icon_html( $card ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<h3 class="home-contact-info-card__title"><?php echo volks_esc_text( $card['title'] ?? '' ); ?></h3>
						<div class="home-contact-info-card__text"><?php echo wp_kses_post( (string) ( $card['text_html'] ?? '' ) ); ?></div>
						<?php if ( ! empty( $card['note'] ) ) : ?><p class="home-contact-info-card__note"><?php echo volks_esc_text( $card['note'] ); ?></p><?php endif; ?>
					</div>
					<?php endforeach; ?>
				</aside>
			</div>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Restore tel/mailto links in contact aside when field-merge flattened card text.
 *
 * @param string $html Section HTML fragment.
 * @return string
 */
function volks_repair_home_contact_aside_html( $html ) {
	if ( false === strpos( $html, 'home-contact-info-card__text' ) || ! class_exists( 'DOMDocument' ) ) {
		return $html;
	}

	$previous = libxml_use_internal_errors( true );
	$doc      = new DOMDocument();
	$loaded   = $doc->loadHTML(
		'<?xml encoding="utf-8" ?><div id="volks-contact-repair-root">' . $html . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded ) {
		return $html;
	}

	$xpath = new DOMXPath( $doc );
	$nodes = $xpath->query( '//*[contains(@class,"home-contact-info-card__text")]' );
	if ( ! $nodes instanceof DOMNodeList ) {
		return $html;
	}

	$repaired = 0;

	foreach ( $nodes as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		if ( $xpath->query( './/a', $node )->length > 0 ) {
			continue;
		}

		$text = trim( preg_replace( '/\s+/u', ' ', (string) $node->textContent ) );
		if ( '' === $text ) {
			continue;
		}

		$inner = '';

		if ( preg_match( '/post@volks\.immobilien/i', $text ) && preg_match( '/\+49[\d\s]+/u', $text, $phone_match ) ) {
			$phone_display = trim( $phone_match[0] );
			$digits        = preg_replace( '/[^\d+]/', '', $phone_display );
			$tel_href      = 'tel:' . $digits;
			$inner         = sprintf(
				'<a href="%1$s">%2$s</a><br><a href="mailto:info@volksimmobilien.eu">info@volksimmobilien.eu</a>',
				esc_attr( $tel_href ),
				esc_html( $phone_display )
			);
		} elseif ( false !== stripos( $text, 'Würmersheimer' ) || false !== stripos( $text, 'Wuermersheimer' ) ) {
			$parts = preg_split( '/\s+(?=Würmersheimer|Würmersheimer|\d{5}\s)/u', $text );
			if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
				$parts = array(
					'volksimmobilien km GmbH',
					'Würmersheimer Straße 6',
					'76474 Au am Rhein',
				);
			}
			$inner = implode( '<br>', array_map( 'esc_html', array_filter( array_map( 'trim', $parts ) ) ) );
		} else {
			continue;
		}

		if ( '' !== $inner ) {
			volks_set_node_inner_html( $doc, $node, $inner );
			++$repaired;
		}
	}

	$root = $doc->getElementById( 'volks-contact-repair-root' );
	if ( ! $root instanceof DOMElement ) {
		return $html;
	}

	$out = '';
	foreach ( $root->childNodes as $child ) {
		$out .= $doc->saveHTML( $child );
	}

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) && $repaired > 0 ) {
		volks_agent_debug_log(
			'G',
			'volks-home-sections.php:volks_repair_home_contact_aside_html',
			'contact_aside_repaired',
			array( 'repaired_nodes' => $repaired )
		);
	}
	// #endregion

	return $out;
}

/**
 * Render a generic imported section after applying individual admin fields to its HTML shell.
 *
 * @param array<string,mixed> $s Section data.
 * @return string
 */
function volks_render_editable_html_section( $s ) {
	$html = (string) ( $s['original_html'] ?? $s['html'] ?? '' );
	if ( '' === trim( $html ) ) {
		return '';
	}

	$apply_field_merge = (bool) apply_filters( 'volks_apply_editable_html_field_merge', true, $s );
	if ( ! $apply_field_merge ) {
		$html = volks_repair_home_contact_aside_html( $html );

		return function_exists( 'volks_normalize_html_fragment' )
			? volks_normalize_html_fragment( $html )
			: wp_kses_post( $html );
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return function_exists( 'volks_normalize_html_fragment' )
			? volks_normalize_html_fragment( $html )
			: wp_kses_post( $html );
	}

	$previous = libxml_use_internal_errors( true );
	$doc      = new DOMDocument();
	$loaded   = $doc->loadHTML( '<?xml encoding="utf-8" ?><div id="volks-editable-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded ) {
		return function_exists( 'volks_normalize_html_fragment' )
			? volks_normalize_html_fragment( $html )
			: wp_kses_post( $html );
	}

	$xpath = new DOMXPath( $doc );
	volks_replace_indexed_heading_nodes( $doc, $xpath, is_array( $s['headings'] ?? null ) ? $s['headings'] : array() );
	volks_replace_indexed_text_nodes( $xpath, './/p[normalize-space()][not(contains(@class,"section-eyebrow"))]', is_array( $s['paragraphs'] ?? null ) ? $s['paragraphs'] : array(), 'text' );
	volks_replace_indexed_links( $xpath, is_array( $s['links'] ?? null ) ? $s['links'] : array() );
	volks_replace_indexed_images( $xpath, is_array( $s['images'] ?? null ) ? $s['images'] : array() );
	$bg_rows           = is_array( $s['background_images'] ?? null ) ? $s['background_images'] : array();
	$inline_bg_rows    = volks_filter_inline_background_image_rows( $bg_rows );
	$original_bg_paths = volks_extract_inline_background_urls_from_html( $html );
	$inline_bg_nodes   = $xpath->query( './/*[@style][contains(@style,"url(")]' );
	$inline_bg_count   = ( $inline_bg_nodes instanceof DOMNodeList ) ? $inline_bg_nodes->length : 0;
	$section_id        = (string) ( $s['section_id'] ?? '' );
	$source_key        = function_exists( 'volks_get_current_source_key' ) ? volks_get_current_source_key() : '';
	$canonical_paths   = array();
	if ( in_array( $section_id, array( 'hero', 'prozess', 'ablauf' ), true ) && '' !== $source_key && function_exists( 'volks_get_canonical_section_background_paths' ) ) {
		$canonical_paths = volks_get_canonical_section_background_paths( $source_key, $section_id );
	}
	$path_source       = $original_bg_paths;
	if ( count( $canonical_paths ) === $inline_bg_count && $inline_bg_count > 0 ) {
		$path_source       = $canonical_paths;
		$use_path_fallback = true;
	} else {
		$use_path_fallback = volks_should_use_background_path_fallback( $inline_bg_count, $inline_bg_rows, $path_source );
		if ( ! $use_path_fallback && count( $canonical_paths ) === $inline_bg_count && volks_inline_background_paths_differ( $original_bg_paths, $canonical_paths ) ) {
			$path_source       = $canonical_paths;
			$use_path_fallback = true;
		}
	}

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) && in_array( $section_id, array( 'hero', 'prozess', 'ablauf' ), true ) ) {
		volks_agent_debug_log(
			'A',
			'volks-home-sections.php:volks_render_editable_html_section',
			'inline_background_merge',
			array(
				'section_id'        => $section_id,
				'source_key'        => $source_key,
				'node_count'        => $inline_bg_count,
				'row_count'         => count( $inline_bg_rows ),
				'original_paths'    => array_slice( array_map( 'volks_normalize_media_basename', $original_bg_paths ), 0, 8 ),
				'canonical_paths'   => array_slice( array_map( 'volks_normalize_media_basename', $canonical_paths ), 0, 8 ),
				'use_path_fallback' => $use_path_fallback,
			)
		);
	}
	// #endregion

	if ( $use_path_fallback ) {
		volks_replace_background_images_from_source_paths( $xpath, $path_source );
	} else {
		volks_replace_indexed_background_images( $xpath, $inline_bg_rows );
	}
	volks_replace_hero_static_backgrounds( $xpath, $inline_bg_rows );

	$root = $doc->getElementById( 'volks-editable-root' );
	if ( ! $root instanceof DOMElement ) {
		return '';
	}

	$out = '';
	foreach ( $root->childNodes as $child ) {
		$out .= $doc->saveHTML( $child );
	}

	$out = volks_repair_home_contact_aside_html( $out );

	$out = function_exists( 'volks_apply_section_css_background_variables' )
		? volks_apply_section_css_background_variables(
			$out,
			$bg_rows,
			(int) ( $s['css_section_background'] ?? 0 ),
			(string) ( $s['css_section_background_class'] ?? '' )
		)
		: $out;

	$out = function_exists( 'volks_normalize_html_fragment' )
		? volks_normalize_html_fragment( $out )
		: wp_kses_post( $out );

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) ) {
		volks_agent_debug_log(
			'F',
			'volks-home-sections.php:volks_render_editable_html_section',
			'editable_section_output',
			array(
				'section_id'    => (string) ( $s['section_id'] ?? '' ),
				'promo_inner'   => false !== strpos( $out, 'valuation-promo-badge__inner' ),
				'price_main'    => false !== strpos( $out, 'valuation-option-card__price-main' ),
				'has_detail'    => false !== strpos( $out, 'valuation-personal-detail-band' ),
				'contact_tel'   => false !== strpos( $out, 'href="tel:' ),
				'contact_mail'  => false !== strpos( $out, 'href="mailto:info@volksimmobilien.eu"' ),
			)
		);
	}
	// #endregion

	return $out;
}

/**
 * @param DOMDocument $doc Document.
 * @param DOMXPath    $xpath XPath.
 * @param array<int,mixed> $rows Heading rows.
 * @return void
 */
function volks_replace_indexed_heading_nodes( $doc, $xpath, $rows ) {
	$nodes = $xpath->query( './/*[self::h1 or self::h2 or self::h3]' );
	if ( ! $nodes instanceof DOMNodeList ) {
		return;
	}

	foreach ( $rows as $index => $row ) {
		$node = $nodes->item( (int) $index );
		if ( ! $node instanceof DOMElement || ! is_array( $row ) ) {
			continue;
		}
		volks_set_node_inner_html( $doc, $node, (string) ( $row['html'] ?? '' ) );
	}
}

/**
 * Skip field-merge replacements that would flatten structured card/wizard markup.
 *
 * @param DOMNode $node Candidate node.
 * @return bool
 */
function volks_dom_node_is_merge_protected( $node ) {
	if ( ! $node instanceof DOMNode ) {
		return false;
	}

	$protected = array(
		'valuation-option-card',
		'valuation-promo-badge',
		'valuation-wizard-section',
		'valuation-wizard',
		'wizard-option',
		'wizard-choice',
		'home-contact-aside',
		'home-contact-info-card',
		'intent-tile',
		'dual-intent',
		'hero-intent',
	);

	for ( $current = $node; $current instanceof DOMElement; $current = $current->parentNode ) {
		$class = (string) $current->getAttribute( 'class' );
		foreach ( $protected as $fragment ) {
			if ( false !== strpos( $class, $fragment ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * @param DOMXPath $xpath XPath.
 * @param string $query Query.
 * @param array<int,mixed> $rows Rows.
 * @param string $key Value key.
 * @return void
 */
function volks_replace_indexed_text_nodes( $xpath, $query, $rows, $key ) {
	$nodes = $xpath->query( $query );
	if ( ! $nodes instanceof DOMNodeList ) {
		return;
	}

	$applied = 0;
	$skipped = 0;

	foreach ( $rows as $index => $row ) {
		$node = $nodes->item( (int) $index );
		if ( ! $node instanceof DOMNode || ! is_array( $row ) ) {
			continue;
		}
		if ( volks_dom_node_is_merge_protected( $node ) ) {
			++$skipped;
			continue;
		}
		$node->nodeValue = (string) ( $row[ $key ] ?? '' );
		++$applied;
	}

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) && ( $applied > 0 || $skipped > 0 ) ) {
		volks_agent_debug_log(
			'F',
			'volks-home-sections.php:volks_replace_indexed_text_nodes',
			'text_merge_stats',
			array(
				'query'   => $query,
				'applied' => $applied,
				'skipped' => $skipped,
			)
		);
	}
	// #endregion
}

/**
 * @param DOMXPath $xpath XPath.
 * @param array<int,mixed> $rows Link rows.
 * @return void
 */
function volks_replace_indexed_links( $xpath, $rows ) {
	$nodes = $xpath->query( './/a[@href]' );
	if ( ! $nodes instanceof DOMNodeList ) {
		return;
	}

	$label_applied = 0;
	$label_skipped = 0;

	foreach ( $rows as $index => $row ) {
		$node = $nodes->item( (int) $index );
		if ( ! $node instanceof DOMElement || ! is_array( $row ) ) {
			continue;
		}
		$class = (string) $node->getAttribute( 'class' );
		if ( false !== strpos( $class, 'intent-tile' ) ) {
			continue;
		}
		$url = (string) ( $row['url'] ?? '' );
		if ( '' !== trim( $url ) ) {
			$node->setAttribute( 'href', function_exists( 'volks_resolve_href' ) ? volks_resolve_href( $url ) : $url );
		}
		$label = (string) ( $row['label'] ?? '' );
		if ( '' !== trim( $label ) ) {
			if ( volks_dom_node_is_merge_protected( $node ) ) {
				++$label_skipped;
				continue;
			}
			$node->nodeValue = $label;
			++$label_applied;
		}
	}

	// #region agent log
	if ( function_exists( 'volks_agent_debug_log' ) && function_exists( 'volks_is_source_key' ) && volks_is_source_key( 'volks-bewerten-v1' ) && ( $label_applied > 0 || $label_skipped > 0 ) ) {
		volks_agent_debug_log(
			'F',
			'volks-home-sections.php:volks_replace_indexed_links',
			'link_merge_stats',
			array(
				'label_applied' => $label_applied,
				'label_skipped' => $label_skipped,
			)
		);
	}
	// #endregion
}

/**
 * @param DOMXPath $xpath XPath.
 * @param array<int,mixed> $rows Image rows.
 * @return void
 */
function volks_replace_indexed_images( $xpath, $rows ) {
	$nodes = $xpath->query( './/img[@src]' );
	if ( ! $nodes instanceof DOMNodeList ) {
		return;
	}

	foreach ( $rows as $index => $row ) {
		$node = $nodes->item( (int) $index );
		if ( ! $node instanceof DOMElement || ! is_array( $row ) ) {
			continue;
		}
		$url = volks_attachment_url( (int) ( $row['image'] ?? 0 ) );
		if ( '' !== $url ) {
			$node->setAttribute( 'src', $url );
			$node->removeAttribute( 'srcset' );
			$node->removeAttribute( 'sizes' );
		}
		if ( array_key_exists( 'alt', $row ) ) {
			$node->setAttribute( 'alt', (string) $row['alt'] );
		}
	}
}

/**
 * Repeater rows for inline style backgrounds only (exclude CSS-class section targets).
 *
 * @param array<int,mixed> $rows Background image rows.
 * @return array<int,mixed>
 */
function volks_filter_inline_background_image_rows( $rows ) {
	$out = array();
	foreach ( (array) $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		if ( '' !== trim( (string) ( $row['css_class'] ?? '' ) ) ) {
			continue;
		}
		$out[] = $row;
	}

	return $out;
}

/**
 * Extract relative background-image paths from inline styles (document order).
 *
 * @param string $html HTML fragment.
 * @return string[]
 */
function volks_extract_inline_background_urls_from_html( $html ) {
	$html = trim( (string) $html );
	if ( '' === $html || ! class_exists( 'DOMDocument' ) ) {
		return array();
	}

	$previous = libxml_use_internal_errors( true );
	$doc      = new DOMDocument();
	$loaded   = $doc->loadHTML( '<?xml encoding="utf-8" ?><div id="volks-bg-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $loaded ) {
		return array();
	}

	$xpath = new DOMXPath( $doc );
	$nodes = $xpath->query( './/*[@style][contains(@style,"url(")]' );
	if ( ! $nodes instanceof DOMNodeList ) {
		return array();
	}

	$paths = array();
	foreach ( $nodes as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		$path = volks_extract_style_background_path( (string) $node->getAttribute( 'style' ) );
		if ( '' !== $path ) {
			$paths[] = $path;
		}
	}

	return $paths;
}

/**
 * @param string $style Inline style attribute.
 * @return string Relative source path or absolute media URL from original HTML.
 */
function volks_extract_style_background_path( $style ) {
	if ( ! preg_match( '/url\\([\'"]?([^\'")]+)[\'"]?\\)/i', (string) $style, $matches ) ) {
		return '';
	}

	$path = trim( html_entity_decode( (string) $matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	if ( '' === $path ) {
		return '';
	}

	if ( preg_match( '#^https?://#i', $path ) ) {
		return $path;
	}

	if ( class_exists( 'Leadwerk_Volks_Media' ) ) {
		return Leadwerk_Volks_Media::normalize_path( $path );
	}

	return ltrim( rawurldecode( str_replace( '\\', '/', $path ) ), '/' );
}

/**
 * Use original HTML paths when stored background rows are misaligned (import gaps).
 *
 * @param int              $node_count        Inline background nodes in DOM.
 * @param array<int,mixed> $inline_bg_rows    Filtered repeater rows.
 * @param string[]         $original_bg_paths Paths from original_html.
 * @return bool
 */
function volks_should_use_background_path_fallback( $node_count, $inline_bg_rows, $original_bg_paths ) {
	$node_count = (int) $node_count;
	if ( $node_count < 1 || empty( $original_bg_paths ) ) {
		return false;
	}

	if ( count( $original_bg_paths ) !== $node_count ) {
		return false;
	}

	if ( count( $inline_bg_rows ) !== $node_count ) {
		return true;
	}

	$resolved_rows = 0;
	foreach ( $inline_bg_rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		if ( (int) ( $row['image'] ?? 0 ) > 0 ) {
			++$resolved_rows;
		}
	}

	if ( $resolved_rows !== $node_count ) {
		return true;
	}

	return ! volks_inline_background_rows_match_original( $inline_bg_rows, $original_bg_paths );
}

/**
 * Compare repeater attachment basenames with original/canonical background paths.
 *
 * @param array<int,mixed> $inline_bg_rows    Filtered repeater rows.
 * @param string[]         $original_bg_paths Paths from original/canonical HTML.
 * @return bool
 */
function volks_inline_background_rows_match_original( $inline_bg_rows, $original_bg_paths ) {
	if ( empty( $original_bg_paths ) ) {
		return true;
	}

	foreach ( $original_bg_paths as $index => $original_path ) {
		$row = isset( $inline_bg_rows[ $index ] ) && is_array( $inline_bg_rows[ $index ] ) ? $inline_bg_rows[ $index ] : null;
		if ( ! $row ) {
			return false;
		}

		$row_url = volks_attachment_url( (int) ( $row['image'] ?? 0 ) );
		if ( '' === $row_url ) {
			return false;
		}

		if ( volks_normalize_media_basename( $row_url ) !== volks_normalize_media_basename( (string) $original_path ) ) {
			return false;
		}
	}

	return true;
}

/**
 * @param string[] $paths_a First path list.
 * @param string[] $paths_b Second path list.
 * @return bool
 */
function volks_inline_background_paths_differ( $paths_a, $paths_b ) {
	if ( count( $paths_a ) !== count( $paths_b ) ) {
		return true;
	}

	foreach ( $paths_a as $index => $path_a ) {
		$path_b = isset( $paths_b[ $index ] ) ? (string) $paths_b[ $index ] : '';
		if ( volks_normalize_media_basename( (string) $path_a ) !== volks_normalize_media_basename( $path_b ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Resolve backgrounds from original HTML source paths (process-reel / hero slides).
 *
 * @param DOMXPath $xpath XPath.
 * @param string[] $paths Relative source paths in DOM order.
 * @return void
 */
function volks_replace_background_images_from_source_paths( $xpath, $paths ) {
	$nodes = $xpath->query( './/*[@style][contains(@style,"url(")]' );
	if ( ! $nodes instanceof DOMNodeList ) {
		return;
	}

	foreach ( $paths as $index => $path ) {
		$node = $nodes->item( (int) $index );
		if ( ! $node instanceof DOMElement || ! is_string( $path ) || '' === trim( $path ) ) {
			continue;
		}

		if ( preg_match( '#^https?://#i', $path ) ) {
			$url = $path;
		} else {
			$url = function_exists( 'volks_resolve_media_url' ) ? volks_resolve_media_url( $path ) : '';
		}

		if ( '' === $url ) {
			continue;
		}

		$style = (string) $node->getAttribute( 'style' );
		$style = preg_replace( '/url\((?:&quot;|\'|")?.*?(?:&quot;|\'|")?\)/i', 'url(' . esc_url_raw( $url ) . ')', $style, 1 );
		$node->setAttribute( 'style', (string) $style );
	}
}

/**
 * @param DOMXPath $xpath XPath.
 * @param array<int,mixed> $rows Background image rows.
 * @return void
 */
function volks_replace_indexed_background_images( $xpath, $rows ) {
	$nodes = $xpath->query( './/*[@style][contains(@style,"url(")]' );
	if ( ! $nodes instanceof DOMNodeList ) {
		return;
	}

	foreach ( $rows as $index => $row ) {
		$node = $nodes->item( (int) $index );
		if ( ! $node instanceof DOMElement || ! is_array( $row ) ) {
			continue;
		}
		$url = volks_attachment_url( (int) ( $row['image'] ?? 0 ) );
		if ( '' === $url ) {
			continue;
		}
		$style = (string) $node->getAttribute( 'style' );
		$style = preg_replace( '/url\((?:&quot;|\'|")?.*?(?:&quot;|\'|")?\)/i', 'url(' . esc_url_raw( $url ) . ')', $style, 1 );
		$node->setAttribute( 'style', (string) $style );
	}
}

/**
 * Apply uploaded images to CSS-based hero backgrounds (.hero-static-bg without inline style).
 *
 * @param DOMXPath $xpath XPath.
 * @param array<int,mixed> $rows Background image rows.
 * @return void
 */
function volks_replace_hero_static_backgrounds( $xpath, $rows ) {
	$nodes = $xpath->query( './/div[contains(@class,"hero-static-bg")]' );
	if ( ! $nodes instanceof DOMNodeList ) {
		return;
	}

	$index = 0;
	foreach ( $nodes as $node ) {
		if ( ! $node instanceof DOMElement ) {
			continue;
		}
		$row = isset( $rows[ $index ] ) && is_array( $rows[ $index ] ) ? $rows[ $index ] : null;
		++$index;
		if ( ! $row ) {
			continue;
		}
		$url = volks_attachment_url( (int) ( $row['image'] ?? 0 ) );
		if ( '' === $url ) {
			continue;
		}
		$node->setAttribute(
			'style',
			'background-image:url(' . esc_url_raw( $url ) . ');background-size:cover;background-position:center center;background-repeat:no-repeat;'
		);
	}
}

/**
 * @param DOMDocument $doc Document.
 * @param DOMElement  $node Target node.
 * @param string      $html Inner HTML.
 * @return void
 */
function volks_set_node_inner_html( $doc, $node, $html ) {
	while ( $node->firstChild ) {
		$node->removeChild( $node->firstChild );
	}

	$html = trim( (string) $html );
	if ( '' === $html ) {
		return;
	}

	$previous = libxml_use_internal_errors( true );
	$tmp      = new DOMDocument();
	$loaded   = $tmp->loadHTML( '<?xml encoding="utf-8" ?><div id="volks-heading-fragment">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( $loaded ) {
		$wrapper = $tmp->getElementById( 'volks-heading-fragment' );
		if ( $wrapper instanceof DOMElement ) {
			foreach ( iterator_to_array( $wrapper->childNodes ) as $child ) {
				$node->appendChild( $doc->importNode( $child, true ) );
			}
			return;
		}
	}

	$node->appendChild( $doc->createTextNode( wp_strip_all_tags( (string) $html ) ) );
}
