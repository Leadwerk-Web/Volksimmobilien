<?php
/**
 * Theme header.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_url    = volks_get_page_url( 'volks-home-v1', home_url( '/' ) );
$bewerten_url = volks_get_page_url( 'volks-bewerten-v1', home_url( '/bewerten/' ) );
$kontakt_url = volks_section_url( 'kontakt-formular' );
$logo_url    = volks_get_logo_url();
$nav_items   = volks_get_main_nav_items();
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a href="#main" class="skip-link">Zum Inhalt springen</a>

<header class="site-header" id="siteHeader">
	<div class="header-inner">
		<a href="<?php echo esc_url( $home_url ); ?>" class="logo" aria-label="volksimmobilien Startseite">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="volksimmobilien Logo" class="logo-img" width="609" height="53">
		</a>

		<nav class="main-nav" id="mainNav" aria-label="Hauptnavigation">
			<ul class="nav-list">
				<?php foreach ( $nav_items as $item ) : ?>
					<?php
					$url    = volks_nav_item_url( $item );
					$active = volks_is_nav_item_active( $item ) ? ' active' : '';
					?>
					<li><a href="<?php echo esc_url( $url ); ?>" class="nav-link<?php echo esc_attr( $active ); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item['label'] ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="header-actions">
			<a href="<?php echo esc_url( $kontakt_url ); ?>" class="btn btn-primary btn-sm header-cta">Kontakt aufnehmen</a>
			<button class="hamburger" id="hamburger" aria-label="Menü öffnen" aria-expanded="false">
				<span></span><span></span><span></span>
			</button>
		</div>
	</div>
</header>

<div class="mobile-overlay" id="mobileOverlay">
	<nav aria-label="Mobile Navigation">
		<ul class="mobile-nav-list">
			<?php foreach ( $nav_items as $item ) : ?>
				<?php $active = volks_is_nav_item_active( $item ) ? ' class="active"' : ''; ?>
				<li><a href="<?php echo esc_url( volks_nav_item_url( $item ) ); ?>"<?php echo $active; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $item['label'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<div class="mobile-overlay-actions">
			<a href="tel:+491702985141" class="mobile-phone-link">+49 170 2 98 51 41</a>
			<div class="mobile-overlay-ctas">
				<a href="<?php echo esc_url( $bewerten_url ); ?>" class="btn btn-outline-accent mobile-overlay-cta">Bewerten</a>
				<a href="<?php echo esc_url( $kontakt_url ); ?>" class="btn btn-primary mobile-overlay-cta">Kontakt aufnehmen</a>
			</div>
		</div>
	</nav>
</div>
