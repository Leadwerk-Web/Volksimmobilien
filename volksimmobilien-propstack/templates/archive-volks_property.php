<?php
/** Property archive with server-side filters. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archive_url = get_post_type_archive_link( Volks_Propstack_Post_Type::POST_TYPE );
$types       = get_terms( array( 'taxonomy' => Volks_Propstack_Post_Type::TAX_TYPE, 'hide_empty' => true ) );
$locations   = get_terms( array( 'taxonomy' => Volks_Propstack_Post_Type::TAX_LOCATION, 'hide_empty' => true ) );
$result_text = 1 === (int) $wp_query->found_posts ? '1 Immobilie' : number_format_i18n( (int) $wp_query->found_posts ) . ' Immobilien';

get_header();
?>
<main id="main" class="site-main vps-archive">
	<section class="vps-archive-hero">
		<div class="container vps-archive-hero__inner">
			<div>
				<p class="section-eyebrow">Aktuelle Angebote</p>
				<h1>Immobilien, die zu Ihrem nächsten Schritt passen.</h1>
				<p>Entdecken Sie unsere aktuell verfügbaren Immobilien. Filtern Sie nach Vermarktungsart, Typ, Ort, Preis, Fläche und Zimmern.</p>
			</div>
			<div class="vps-archive-hero__signal" aria-label="Direkt aus Propstack synchronisiert">
				<span class="vps-live-dot" aria-hidden="true"></span>
				<span>Aktuell aus unserem Immobilienbestand</span>
			</div>
		</div>
	</section>

	<section class="vps-results-section" aria-labelledby="vps-results-title">
		<div class="container">
			<form class="vps-filter" method="get" action="<?php echo esc_url( $archive_url ); ?>">
				<div class="vps-filter__heading">
					<div><span class="vps-filter__eyebrow">Passend suchen</span><h2>Immobilien filtern</h2></div>
					<a href="<?php echo esc_url( $archive_url ); ?>">Filter zurücksetzen</a>
				</div>
				<div class="vps-filter__grid">
					<label><span>Vermarktung</span><select name="vermarktung"><option value="">Kauf &amp; Miete</option><option value="BUY" <?php selected( Volks_Propstack_Frontend::filter_value( 'vermarktung' ), 'BUY' ); ?>>Kauf</option><option value="RENT" <?php selected( Volks_Propstack_Frontend::filter_value( 'vermarktung' ), 'RENT' ); ?>>Miete</option></select></label>
					<label><span>Immobilientyp</span><select name="typ"><option value="">Alle Typen</option><?php foreach ( is_wp_error( $types ) ? array() : $types as $term ) : ?><option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( Volks_Propstack_Frontend::filter_value( 'typ' ), $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?></select></label>
					<label><span>Ort</span><select name="ort"><option value="">Alle Orte</option><?php foreach ( is_wp_error( $locations ) ? array() : $locations as $term ) : ?><option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( Volks_Propstack_Frontend::filter_value( 'ort' ), $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option><?php endforeach; ?></select></label>
					<label><span>Preis ab</span><input type="number" min="0" step="10000" name="preis_von" value="<?php echo esc_attr( Volks_Propstack_Frontend::filter_value( 'preis_von' ) ); ?>" placeholder="z. B. 250000"></label>
					<label><span>Preis bis</span><input type="number" min="0" step="10000" name="preis_bis" value="<?php echo esc_attr( Volks_Propstack_Frontend::filter_value( 'preis_bis' ) ); ?>" placeholder="z. B. 750000"></label>
					<label><span>Wohnfläche ab</span><input type="number" min="0" step="10" name="flaeche_von" value="<?php echo esc_attr( Volks_Propstack_Frontend::filter_value( 'flaeche_von' ) ); ?>" placeholder="m²"></label>
					<label><span>Zimmer ab</span><input type="number" min="0" step="0.5" name="zimmer_von" value="<?php echo esc_attr( Volks_Propstack_Frontend::filter_value( 'zimmer_von' ) ); ?>" placeholder="z. B. 3"></label>
					<label><span>Sortierung</span><select name="sortierung"><option value="">Neueste zuerst</option><option value="preis_asc" <?php selected( Volks_Propstack_Frontend::filter_value( 'sortierung' ), 'preis_asc' ); ?>>Preis aufsteigend</option><option value="preis_desc" <?php selected( Volks_Propstack_Frontend::filter_value( 'sortierung' ), 'preis_desc' ); ?>>Preis absteigend</option><option value="flaeche_desc" <?php selected( Volks_Propstack_Frontend::filter_value( 'sortierung' ), 'flaeche_desc' ); ?>>Größte Fläche</option></select></label>
				</div>
				<button class="btn btn-primary vps-filter__submit" type="submit"><?php echo esc_html( $result_text ); ?> anzeigen</button>
			</form>

			<div class="vps-results-head">
				<div><p class="section-eyebrow">Ergebnis</p><h2 id="vps-results-title"><?php echo esc_html( $result_text ); ?> gefunden</h2></div>
				<p>Jedes Angebot wird automatisch mit dem freigegebenen Bestand abgeglichen.</p>
			</div>

			<?php if ( have_posts() ) : ?>
				<div class="vps-grid">
					<?php while ( have_posts() ) : the_post(); Volks_Propstack_Frontend::render_card( get_the_ID() ); endwhile; ?>
				</div>
				<nav class="vps-pagination" aria-label="Immobilienseiten"><?php echo wp_kses_post( paginate_links( array( 'mid_size' => 1, 'prev_text' => '← Zurück', 'next_text' => 'Weiter →' ) ) ); ?></nav>
			<?php else : ?>
				<div class="vps-empty"><h2>Keine passende Immobilie gefunden.</h2><p>Passen Sie die Filter an oder teilen Sie uns Ihren Suchwunsch mit.</p><a class="btn btn-primary" href="<?php echo esc_url( home_url( '/#kontakt-formular' ) ); ?>">Suchwunsch hinterlegen</a></div>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>

