<?php
/** Detailed public property page. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
the_post();
$post_id       = get_the_ID();
$images        = Volks_Propstack_Frontend::images( $post_id );
$type          = Volks_Propstack_Frontend::meta( $post_id, 'type_label', 'Immobilie' );
$status        = Volks_Propstack_Frontend::meta( $post_id, 'status' );
$city          = Volks_Propstack_Frontend::meta( $post_id, 'city' );
$district      = Volks_Propstack_Frontend::meta( $post_id, 'district' );
$zip           = Volks_Propstack_Frontend::meta( $post_id, 'zip_code' );
$description   = Volks_Propstack_Frontend::meta( $post_id, 'description' );
$location_note = Volks_Propstack_Frontend::meta( $post_id, 'location_note' );
$furnishing    = Volks_Propstack_Frontend::meta( $post_id, 'furnishing_note' );
$other         = Volks_Propstack_Frontend::meta( $post_id, 'other_note' );
$energy        = Volks_Propstack_Frontend::meta( $post_id, 'energy', array() );
$floorplans    = Volks_Propstack_Frontend::meta( $post_id, 'floorplans', array() );
$documents     = Volks_Propstack_Frontend::meta( $post_id, 'documents', array() );
$broker        = Volks_Propstack_Frontend::meta( $post_id, 'broker', array() );
$location      = trim( implode( ' · ', array_filter( array( $zip, $city, $district ) ) ) );
$facts         = array(
	'Wohnfläche' => array( Volks_Propstack_Frontend::meta( $post_id, 'living_space', null ), 'm²', 0 ),
	'Zimmer'     => array( Volks_Propstack_Frontend::meta( $post_id, 'rooms', null ), '', 1 ),
	'Grundstück' => array( Volks_Propstack_Frontend::meta( $post_id, 'plot_area', null ), 'm²', 0 ),
	'Baujahr'    => array( Volks_Propstack_Frontend::meta( $post_id, 'construction_year', null ), '', 0 ),
);

get_header();
?>
<main id="main" class="site-main vps-single">
	<section class="vps-single-head">
		<div class="container">
			<nav class="vps-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Start</a><span>›</span><a href="<?php echo esc_url( get_post_type_archive_link( Volks_Propstack_Post_Type::POST_TYPE ) ); ?>">Immobilien</a><span>›</span><span aria-current="page"><?php echo esc_html( get_the_title() ); ?></span></nav>
		</div>
	</section>

	<section class="vps-gallery-section">
		<div class="container">
			<?php if ( $images ) : ?>
				<div class="vps-gallery<?php echo count( $images ) > 1 ? ' vps-gallery--has-thumbs' : ''; ?>" data-vps-gallery>
					<div class="vps-gallery__main">
						<button type="button" class="vps-gallery__open" data-vps-lightbox-open aria-label="Bildergalerie im Vollbild öffnen" aria-haspopup="dialog">
							<img src="<?php echo esc_url( $images[0]['url'] ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" data-vps-gallery-main>
							<span class="vps-gallery__open-hint" aria-hidden="true"><span>↗</span> Vollbild</span>
						</button>
					</div>
					<?php if ( count( $images ) > 1 ) : ?>
						<div class="vps-gallery__thumbs" aria-label="Weitere Bilder">
							<?php foreach ( $images as $index => $image ) : ?>
								<button type="button" class="vps-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-vps-image="<?php echo esc_url( $image['url'] ); ?>" data-vps-index="<?php echo esc_attr( $index ); ?>" aria-label="Bild <?php echo esc_attr( $index + 1 ); ?> anzeigen"><img src="<?php echo esc_url( $image['thumb'] ?: $image['url'] ); ?>" alt="" loading="lazy"></button>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<dialog class="vps-lightbox" data-vps-lightbox aria-labelledby="vps-lightbox-title-<?php echo esc_attr( $post_id ); ?>">
						<div class="vps-lightbox__shell">
							<header class="vps-lightbox__bar">
								<h2 class="screen-reader-text" id="vps-lightbox-title-<?php echo esc_attr( $post_id ); ?>">Bildergalerie: <?php echo esc_html( get_the_title() ); ?></h2>
								<p class="vps-lightbox__counter" data-vps-lightbox-counter aria-live="polite">Bild 1 von <?php echo esc_html( count( $images ) ); ?></p>
								<button type="button" class="vps-lightbox__close" data-vps-lightbox-close aria-label="Vollbildgalerie schließen">×</button>
							</header>
							<div class="vps-lightbox__stage" data-vps-lightbox-stage>
								<?php if ( count( $images ) > 1 ) : ?><button type="button" class="vps-lightbox__arrow vps-lightbox__arrow--prev" data-vps-lightbox-prev aria-label="Vorheriges Bild">‹</button><?php endif; ?>
								<figure class="vps-lightbox__figure"><img src="<?php echo esc_url( $images[0]['full'] ?? $images[0]['url'] ); ?>" alt="<?php echo esc_attr( get_the_title() . ' – Bild 1' ); ?>" data-vps-lightbox-image></figure>
								<?php if ( count( $images ) > 1 ) : ?><button type="button" class="vps-lightbox__arrow vps-lightbox__arrow--next" data-vps-lightbox-next aria-label="Nächstes Bild">›</button><?php endif; ?>
							</div>
							<?php if ( count( $images ) > 1 ) : ?>
								<div class="vps-lightbox__thumbs" aria-label="Alle Bilder">
									<?php foreach ( $images as $index => $image ) : ?>
										<button type="button" class="vps-lightbox__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-vps-lightbox-source="<?php echo esc_url( $image['full'] ?? $image['url'] ); ?>" data-vps-gallery-source="<?php echo esc_url( $image['url'] ); ?>" data-vps-lightbox-index="<?php echo esc_attr( $index ); ?>" aria-label="Vollbild <?php echo esc_attr( $index + 1 ); ?> anzeigen"<?php echo 0 === $index ? ' aria-current="true"' : ''; ?>><img src="<?php echo esc_url( $image['thumb'] ?: $image['url'] ); ?>" alt="" loading="lazy"></button>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</dialog>
				</div>
			<?php else : ?><div class="vps-gallery__placeholder"><span>volksimmobilien</span><p>Bildmaterial folgt.</p></div><?php endif; ?>
			<div class="vps-single-head__grid">
				<div><p class="section-eyebrow"><?php echo esc_html( $type . ' · ' . Volks_Propstack_Frontend::marketing_label( $post_id ) ); ?></p><h1><?php the_title(); ?></h1><?php if ( $location ) : ?><p class="vps-single-location"><?php echo esc_html( $location ); ?></p><?php endif; ?></div>
				<div class="vps-single-head__price"><span><?php echo esc_html( $status ); ?></span><strong><?php echo esc_html( Volks_Propstack_Frontend::format_price( $post_id ) ); ?></strong></div>
			</div>
		</div>
	</section>

	<section class="vps-single-content">
		<div class="container vps-single-layout">
			<div class="vps-single-main">
				<div class="vps-facts" aria-label="Eckdaten">
					<?php foreach ( $facts as $label => $fact ) : ?>
						<?php
						if ( ! is_numeric( $fact[0] ) || (float) $fact[0] <= 0 ) {
							continue;
						}
						$value = 'Baujahr' === $label
							? (string) absint( $fact[0] )
							: number_format_i18n( (float) $fact[0], $fact[2] );
						?>
						<div><span><?php echo esc_html( $label ); ?></span><strong><?php echo esc_html( $value . ( $fact[1] ? ' ' . $fact[1] : '' ) ); ?></strong></div>
					<?php endforeach; ?>
				</div>

				<?php $sections = array( 'Objektbeschreibung' => $description, 'Lage' => $location_note, 'Ausstattung' => $furnishing, 'Weitere Informationen' => $other ); foreach ( $sections as $heading => $content ) : if ( ! trim( wp_strip_all_tags( $content ) ) ) continue; ?><section class="vps-copy-block"><p class="section-eyebrow"><?php echo esc_html( $heading ); ?></p><div class="vps-richtext"><?php echo wp_kses_post( wpautop( $content ) ); ?></div></section><?php endforeach; ?>

				<?php if ( is_array( $energy ) && $energy ) : ?><section class="vps-copy-block"><p class="section-eyebrow">Energie &amp; Gebäude</p><div class="vps-data-grid"><?php foreach ( $energy as $label => $value ) : ?><div><span><?php echo esc_html( $label ); ?></span><strong><?php echo esc_html( $value ); ?></strong></div><?php endforeach; ?></div></section><?php endif; ?>

				<?php if ( ( is_array( $floorplans ) && $floorplans ) || ( is_array( $documents ) && $documents ) ) : ?><section class="vps-copy-block"><p class="section-eyebrow">Unterlagen</p><div class="vps-documents"><?php foreach ( array_merge( (array) $floorplans, (array) $documents ) as $document ) : ?><a href="<?php echo esc_url( $document['url'] ); ?>" target="_blank" rel="noopener"><span aria-hidden="true">↗</span><?php echo esc_html( $document['title'] ); ?></a><?php endforeach; ?></div></section><?php endif; ?>
			</div>

			<aside class="vps-contact-card">
					<p class="section-eyebrow">Persönlich anfragen</p><h2>Interesse an dieser Immobilie?</h2><p>Wir beantworten Deine Fragen und koordinieren die nächsten Schritte persönlich.</p>
				<?php if ( is_array( $broker ) && ! empty( $broker['name'] ) ) : ?><div class="vps-broker"><?php if ( ! empty( $broker['avatar_url'] ) ) : ?><img src="<?php echo esc_url( $broker['avatar_url'] ); ?>" alt="<?php echo esc_attr( $broker['name'] ); ?>"><?php endif; ?><div><strong><?php echo esc_html( $broker['name'] ); ?></strong><?php if ( ! empty( $broker['position'] ) ) : ?><span><?php echo esc_html( $broker['position'] ); ?></span><?php endif; ?></div></div><?php endif; ?>
				<a class="btn btn-primary btn-lg" href="<?php echo esc_url( home_url( '/#kontakt-formular' ) ); ?>">Anfrage senden</a>
				<a class="vps-contact-card__phone" href="tel:+491702985141">+49 170 2 98 51 41</a>
				<p class="vps-contact-card__micro">Objekt-Nr. <?php echo esc_html( Volks_Propstack_Frontend::meta( $post_id, 'exposee_id', Volks_Propstack_Frontend::meta( $post_id, 'unit_id', $post_id ) ) ); ?></p>
			</aside>
		</div>
	</section>

	<section class="vps-single-cta"><div class="container"><div><p class="section-eyebrow">Noch nicht passend?</p><h2>Weitere Immobilien entdecken.</h2><p>Nutzen Sie unsere Filter oder hinterlegen Sie Ihren persönlichen Suchwunsch.</p></div><div><a class="btn btn-primary btn-lg" href="<?php echo esc_url( get_post_type_archive_link( Volks_Propstack_Post_Type::POST_TYPE ) ); ?>">Alle Immobilien</a><a class="btn btn-outline btn-lg" href="<?php echo esc_url( home_url( '/#kontakt-formular' ) ); ?>">Suchwunsch</a></div></div></section>
</main>
<?php get_footer(); ?>
