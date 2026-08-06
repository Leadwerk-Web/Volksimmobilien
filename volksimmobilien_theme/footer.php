<?php
/**
 * Theme footer.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_url        = volks_get_page_url( 'volks-home-v1', home_url( '/' ) );
$bewerten_url    = volks_get_page_url( 'volks-bewerten-v1', home_url( '/bewerten/' ) );
$impressum_url   = volks_get_page_url( 'volks-impressum-v1', home_url( '/impressum/' ) );
$datenschutz_url = volks_get_page_url( 'volks-datenschutz-v1', home_url( '/datenschutz/' ) );
$cookie_url      = function_exists( 'cmplz_get_document_url' )
	? cmplz_get_document_url( 'cookie-statement', 'eu' )
	: home_url( '/cookie-richtlinie-eu/' );
$kontakt_url     = volks_section_url( 'kontakt-formular' );
$logo_url        = volks_get_logo_url();
$nav_items       = volks_get_main_nav_items();
$offers_url      = post_type_exists( 'volks_property' ) ? get_post_type_archive_link( 'volks_property' ) : home_url( '/immobilien/' );
?>

<footer class="site-footer">
	<div class="container footer-grid">
		<div class="footer-col footer-col--brand">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="volksimmobilien Logo" class="footer-logo" width="609" height="53">
			<p>Dein Immobilienmakler von Heidelberg bis Baden-Baden. Persönlich vor Ort, ehrlich bewertet, klar vermarktet.</p>
			<div class="footer-cta-row">
				<a href="<?php echo esc_url( $kontakt_url ); ?>" class="btn btn-primary btn-sm">Kontakt</a>
				<a href="<?php echo esc_url( $bewerten_url ); ?>" class="btn btn-outline-light btn-sm">Bewerten</a>
				<a href="<?php echo esc_url( $offers_url ); ?>" class="btn btn-outline-light btn-sm">Angebote</a>
			</div>
		</div>
		<div class="footer-col">
			<h4>Navigation</h4>
			<ul>
				<?php foreach ( $nav_items as $item ) : ?>
					<li><a href="<?php echo esc_url( volks_nav_item_url( $item ) ); ?>"><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="footer-col">
			<h4>Kontakt</h4>
			<ul class="footer-contact">
				<li>
					<a href="tel:+491702985141">+49 170 2 98 51 41</a>
				</li>
				<li>
					<a href="mailto:info@volksimmobilien.eu">info@volksimmobilien.eu</a>
				</li>
				<li>
					<span>Würmersheimer Str. 6<br>76474 Au am Rhein</span>
				</li>
				<li class="footer-instagram">
					<a href="https://www.instagram.com/volksimmobilien/" target="_blank" rel="noopener noreferrer" aria-label="volksimmobilien auf Instagram">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><path d="M17.5 6.5h.01"/></svg>
					</a>
				</li>
			</ul>
		</div>
	</div>
	<div class="footer-bottom">
		<div class="container footer-bottom-inner">
			<p>&copy; <span id="currentYear"></span> volksimmobilien km GmbH – Immobilienmakler von Heidelberg bis Baden-Baden</p>
			<div class="footer-legal">
				<a href="<?php echo esc_url( $impressum_url ); ?>">Impressum</a>
				<a href="<?php echo esc_url( $datenschutz_url ); ?>">Datenschutz</a>
				<a href="<?php echo esc_url( $cookie_url ); ?>">Cookie-Richtlinie</a>
			</div>
		</div>
	</div>
</footer>

<div class="mobile-sticky-cta" id="mobileStickyBar">
	<a href="tel:+491702985141" class="mobile-sticky-btn mobile-sticky-btn--phone">Anrufen</a>
	<a href="<?php echo esc_url( $kontakt_url ); ?>" class="mobile-sticky-btn mobile-sticky-btn--callback">Rückruf</a>
	<a href="<?php echo esc_url( $bewerten_url ); ?>" class="mobile-sticky-btn mobile-sticky-btn--valuation">Bewerten</a>
</div>

<button type="button" class="scroll-to-top" id="scrollToTop" aria-label="Nach oben scrollen" aria-hidden="true" tabindex="-1">
	<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 15l-6-6-6 6"/></svg>
</button>

<?php wp_footer(); ?>
</body>
</html>
