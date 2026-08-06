<?php
/**
 * Editorial gallery for properties sold through volksimmobilien.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

status_header( 200 );

$sold_query = new WP_Query(
	array(
		'post_type'              => Volks_Propstack_Post_Type::POST_TYPE,
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'meta_key'               => '_vps_updated_at',
		'orderby'                => array(
			'meta_value' => 'DESC',
			'title'      => 'ASC',
		),
		'meta_query'             => array(
			array(
				'key'     => '_vps_inventory_state',
				'value'   => 'sold',
				'compare' => '=',
			),
		),
		'update_post_meta_cache' => true,
	)
);
$sold_count = (int) $sold_query->post_count;
$sell_url   = function_exists( 'volks_get_page_url' )
	? volks_get_page_url( 'volks-verkaufen-v1', home_url( '/verkaufen/' ) )
	: home_url( '/verkaufen/' );

get_header();
?>
<main id="main" class="site-main vps-sold">
	<section class="vps-sold-hero">
		<div class="container vps-sold-hero__inner">
			<div>
				<p class="section-eyebrow">Erfolgreich vermittelt</p>
				<h1>Verkauft. Und in guten Händen angekommen.</h1>
				<p>Jede Immobilie erzählt eine eigene Geschichte. Entdecken Sie eine Auswahl der Objekte, die wir erfolgreich vermarkten und an ihre neuen Eigentümer übergeben durften.</p>
			</div>
			<div class="vps-sold-hero__stat" aria-label="<?php echo esc_attr( $sold_count . ' erfolgreich verkaufte Immobilien in dieser Galerie' ); ?>">
				<strong><?php echo esc_html( number_format_i18n( $sold_count ) ); ?></strong>
				<span><?php echo 1 === $sold_count ? 'verkaufte Immobilie' : 'verkaufte Immobilien'; ?></span>
			</div>
		</div>
	</section>

	<section class="vps-sold-intro" aria-labelledby="vps-sold-title">
		<div class="container">
			<div class="vps-sold-intro__copy">
				<div><p class="section-eyebrow">Unsere Referenzen</p><h2 id="vps-sold-title">Einblicke in erfolgreich abgeschlossene Verkäufe.</h2></div>
				<p>Öffnen Sie ein Objekt, um alle freigegebenen Aufnahmen in der Vollbildgalerie anzusehen. Preise und vertrauliche Verkaufsdetails werden bewusst nicht veröffentlicht.</p>
			</div>

			<?php if ( $sold_query->have_posts() ) : ?>
				<div class="vps-sold-grid">
					<?php
					$card_index = 0;
					while ( $sold_query->have_posts() ) :
						$sold_query->the_post();
						$post_id   = get_the_ID();
						$remote_id = Volks_Propstack_Frontend::meta( $post_id, 'id', $post_id );
						$images    = Volks_Propstack_Frontend::images( $post_id );
						$previews  = array_slice( $images, 0, 3 );
						$type      = Volks_Propstack_Frontend::meta( $post_id, 'type_label', 'Immobilie' );
						$city      = Volks_Propstack_Frontend::meta( $post_id, 'city' );
						$area      = Volks_Propstack_Frontend::meta( $post_id, 'living_space', null );
						$rooms     = Volks_Propstack_Frontend::meta( $post_id, 'rooms', null );
						$title     = get_the_title( $post_id );
						$dialog_id = 'vps-sold-lightbox-' . absint( $post_id );
						++$card_index;
						?>
						<article class="vps-sold-card" id="verkauft-<?php echo esc_attr( sanitize_html_class( (string) $remote_id ) ); ?>" data-vps-gallery>
							<div class="vps-sold-card__media">
								<?php if ( $images ) : ?>
									<button type="button" class="vps-sold-card__open vps-sold-card__open--<?php echo esc_attr( min( 3, count( $previews ) ) ); ?>" data-vps-lightbox-open aria-label="<?php echo esc_attr( 'Bildergalerie zu ' . $title . ' öffnen' ); ?>" aria-haspopup="dialog" aria-controls="<?php echo esc_attr( $dialog_id ); ?>">
										<?php foreach ( $previews as $preview_index => $preview ) : ?>
											<img src="<?php echo esc_url( $preview['url'] ); ?>" alt="<?php echo 0 === $preview_index ? esc_attr( $title ) : ''; ?>" loading="<?php echo $card_index <= 3 ? 'eager' : 'lazy'; ?>" decoding="async"<?php echo 0 === $preview_index ? ' data-vps-gallery-main' : ''; ?>>
										<?php endforeach; ?>
										<span class="vps-sold-card__shade" aria-hidden="true"></span>
										<span class="vps-sold-card__badge">Verkauft</span>
										<span class="vps-sold-card__count"><span aria-hidden="true">▧</span> <?php echo esc_html( count( $images ) ); ?> Bilder</span>
									</button>
								<?php else : ?>
									<div class="vps-card__placeholder"><span>volksimmobilien</span></div>
								<?php endif; ?>
							</div>
							<div class="vps-sold-card__body">
								<p class="vps-sold-card__type"><?php echo esc_html( $type ); ?></p>
								<h2><?php echo esc_html( $title ); ?></h2>
								<?php if ( $city ) : ?><p class="vps-sold-card__location"><?php echo esc_html( $city ); ?></p><?php endif; ?>
								<div class="vps-sold-card__facts">
									<?php if ( is_numeric( $area ) && (float) $area > 0 ) : ?><span><?php echo esc_html( number_format_i18n( (float) $area, 0 ) ); ?> m²</span><?php endif; ?>
									<?php if ( is_numeric( $rooms ) && (float) $rooms > 0 ) : ?><span><?php echo esc_html( number_format_i18n( (float) $rooms, 1 ) ); ?> Zimmer</span><?php endif; ?>
								</div>
							</div>

							<?php if ( $images ) : ?>
								<dialog class="vps-lightbox" id="<?php echo esc_attr( $dialog_id ); ?>" data-vps-lightbox aria-labelledby="<?php echo esc_attr( $dialog_id . '-title' ); ?>">
									<div class="vps-lightbox__shell">
										<header class="vps-lightbox__bar">
											<div><p class="vps-lightbox__eyebrow">Verkauft</p><h2 id="<?php echo esc_attr( $dialog_id . '-title' ); ?>"><?php echo esc_html( $title ); ?></h2></div>
											<p class="vps-lightbox__counter" data-vps-lightbox-counter aria-live="polite">Bild 1 von <?php echo esc_html( count( $images ) ); ?></p>
											<button type="button" class="vps-lightbox__close" data-vps-lightbox-close aria-label="Vollbildgalerie schließen">×</button>
										</header>
										<div class="vps-lightbox__stage" data-vps-lightbox-stage>
											<?php if ( count( $images ) > 1 ) : ?><button type="button" class="vps-lightbox__arrow vps-lightbox__arrow--prev" data-vps-lightbox-prev aria-label="Vorheriges Bild">‹</button><?php endif; ?>
											<figure class="vps-lightbox__figure"><img src="<?php echo esc_url( $images[0]['full'] ?? $images[0]['url'] ); ?>" alt="<?php echo esc_attr( $title . ' – Bild 1' ); ?>" data-vps-lightbox-image></figure>
											<?php if ( count( $images ) > 1 ) : ?><button type="button" class="vps-lightbox__arrow vps-lightbox__arrow--next" data-vps-lightbox-next aria-label="Nächstes Bild">›</button><?php endif; ?>
										</div>
										<?php if ( count( $images ) > 1 ) : ?>
											<div class="vps-lightbox__thumbs" aria-label="Alle Bilder">
												<?php foreach ( $images as $image_index => $image ) : ?>
													<button type="button" class="vps-lightbox__thumb<?php echo 0 === $image_index ? ' is-active' : ''; ?>" data-vps-lightbox-source="<?php echo esc_url( $image['full'] ?? $image['url'] ); ?>" data-vps-gallery-source="<?php echo esc_url( $image['url'] ); ?>" data-vps-lightbox-index="<?php echo esc_attr( $image_index ); ?>" aria-label="Vollbild <?php echo esc_attr( $image_index + 1 ); ?> anzeigen"<?php echo 0 === $image_index ? ' aria-current="true"' : ''; ?>><img src="<?php echo esc_url( $image['thumb'] ?: $image['url'] ); ?>" alt="" loading="lazy"></button>
												<?php endforeach; ?>
											</div>
										<?php endif; ?>
									</div>
								</dialog>
							<?php endif; ?>
						</article>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<div class="vps-empty"><h2>Die Galerie wird gerade vorbereitet.</h2><p>Sobald freigegebene Referenzen vorliegen, erscheinen sie automatisch an dieser Stelle.</p></div>
			<?php endif; ?>
		</div>
	</section>

	<section class="vps-sold-cta">
		<div class="container">
			<div><p class="section-eyebrow">Auch verkaufen?</p><h2>Wir machen den nächsten Erfolg gemeinsam möglich.</h2><p>Von der realistischen Bewertung bis zur sicheren Übergabe begleiten wir Ihren Verkauf persönlich und klar.</p></div>
			<div><a class="btn btn-primary btn-lg" href="<?php echo esc_url( $sell_url ); ?>">Immobilie verkaufen</a><a class="btn btn-outline btn-lg" href="<?php echo esc_url( home_url( '/#kontakt-formular' ) ); ?>">Kontakt aufnehmen</a></div>
		</div>
	</section>
</main>
<?php
wp_reset_postdata();
get_footer();
