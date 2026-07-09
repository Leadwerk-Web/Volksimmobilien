<?php
/**
 * volksimmobilien theme bootstrap.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VOLKS_THEME_VERSION', '1.0.0' );
define( 'VOLKS_THEME_PATH', get_template_directory() );
define( 'VOLKS_THEME_URI', get_template_directory_uri() );

require_once VOLKS_THEME_PATH . '/inc/urls.php';
require_once VOLKS_THEME_PATH . '/inc/link-rewrite.php';
require_once VOLKS_THEME_PATH . '/inc/valuation-wizard.php';
require_once VOLKS_THEME_PATH . '/inc/volks-field-output.php';
require_once VOLKS_THEME_PATH . '/inc/sections/volks-home-sections.php';
require_once VOLKS_THEME_PATH . '/inc/render.php';
require_once VOLKS_THEME_PATH . '/inc/yoast-seo.php';
require_once VOLKS_THEME_PATH . '/inc/open-graph.php';
require_once VOLKS_THEME_PATH . '/inc/structured-data.php';

/**
 * Theme setup.
 */
function volks_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus(
		array(
			'footer_extra' => __( 'Footer Zusatz', 'volksimmobilien' ),
		)
	);
}
add_action( 'after_setup_theme', 'volks_theme_setup' );

/**
 * Enqueue styles and scripts.
 */
function volks_enqueue_assets() {
	wp_enqueue_style(
		'volks-google-fonts',
		'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'volks-main',
		VOLKS_THEME_URI . '/assets/css/style.css',
		array( 'volks-google-fonts' ),
		VOLKS_THEME_VERSION
	);

	wp_enqueue_style(
		'volks-wp-overrides',
		VOLKS_THEME_URI . '/assets/css/volks-wp-overrides.css',
		array( 'volks-main' ),
		VOLKS_THEME_VERSION
	);

	$logo_watermark = VOLKS_THEME_URI . '/assets/images/Logo-Icon.svg';
	wp_add_inline_style(
		'volks-wp-overrides',
		'.intro-trust:has(.trust-panel-stack--solo)::before{background-image:url(' . esc_url( $logo_watermark ) . ') !important;}'
	);

	wp_enqueue_script(
		'volks-main',
		VOLKS_THEME_URI . '/assets/js/main.js',
		array(),
		VOLKS_THEME_VERSION,
		true
	);

}
add_action( 'wp_enqueue_scripts', 'volks_enqueue_assets' );

/**
 * Load WPForms core assets on pages that embed the contact form via theme options.
 */
function volks_enqueue_wpforms_core_assets() {
	if ( ! function_exists( 'volks_page_uses_wpforms_contact' ) || ! volks_page_uses_wpforms_contact() ) {
		return;
	}
	if ( ! function_exists( 'volks_has_wpforms_contact_form' ) || ! volks_has_wpforms_contact_form() ) {
		return;
	}
	if ( ! function_exists( 'wpforms' ) ) {
		return;
	}

	$frontend = wpforms()->frontend;
	if ( ! $frontend ) {
		return;
	}

	if ( method_exists( $frontend, 'assets_css' ) ) {
		$frontend->assets_css();
	}
	if ( method_exists( $frontend, 'assets_js' ) ) {
		$frontend->assets_js();
	}
}
add_action( 'wp_enqueue_scripts', 'volks_enqueue_wpforms_core_assets', 15 );

/**
 * WPForms contact form skin (after WPForms default CSS).
 */
function volks_enqueue_wpforms_contact_skin() {
	if ( ! function_exists( 'volks_page_uses_wpforms_contact' ) || ! volks_page_uses_wpforms_contact() ) {
		return;
	}
	if ( ! function_exists( 'volks_has_wpforms_contact_form' ) || ! volks_has_wpforms_contact_form() ) {
		return;
	}

	$deps = array( 'volks-main' );
	if ( wp_style_is( 'wpforms-full', 'registered' ) ) {
		$deps[] = 'wpforms-full';
	} elseif ( wp_style_is( 'wpforms-base', 'registered' ) ) {
		$deps[] = 'wpforms-base';
	}

	wp_enqueue_style(
		'volks-wpforms-contact',
		VOLKS_THEME_URI . '/assets/css/wpforms-contact.css',
		$deps,
		VOLKS_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'volks_enqueue_wpforms_contact_skin', 100 );

/**
 * Append imported body class.
 *
 * @param array<int,string> $classes Body classes.
 * @return array<int,string>
 */
function volks_filter_body_class( $classes ) {
	if ( is_404() ) {
		$classes[] = 'page-legal';
		$classes[] = 'page-not-found';
		$page_id   = volks_get_page_id_by_source_key( 'volks-404-v1' );
		if ( $page_id > 0 ) {
			$imported = volks_get_page_body_class( $page_id );
			if ( '' !== trim( $imported ) ) {
				foreach ( preg_split( '/\s+/', trim( $imported ) ) as $class_name ) {
					if ( '' !== $class_name ) {
						$classes[] = $class_name;
					}
				}
			}
		}
		return array_values( array_unique( $classes ) );
	}

	$imported = volks_get_page_body_class();
	if ( '' !== trim( $imported ) ) {
		foreach ( preg_split( '/\s+/', trim( $imported ) ) as $class_name ) {
			if ( '' !== $class_name ) {
				$classes[] = $class_name;
			}
		}
	}
	return $classes;
}
add_filter( 'body_class', 'volks_filter_body_class' );

/**
 * Permalink of the imported 404 utility page.
 *
 * @return string
 */
function volks_get_404_page_url() {
	$page_id = volks_get_page_id_by_source_key( 'volks-404-v1' );
	if ( $page_id <= 0 ) {
		return '';
	}

	$url = get_permalink( $page_id );

	return is_string( $url ) && '' !== $url ? $url : '';
}

/**
 * Redirect unknown URLs to the /404/ page (fallback: theme 404.php).
 *
 * @return void
 */
function volks_redirect_404_to_page() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	if ( ! is_404() ) {
		return;
	}

	$url = volks_get_404_page_url();
	if ( '' === $url ) {
		return;
	}

	wp_safe_redirect( $url, 302 );
	exit;
}
add_action( 'template_redirect', 'volks_redirect_404_to_page', 1 );

/**
 * Return HTTP 404 on the utility error page (published page, but not indexable as 200).
 *
 * @return void
 */
function volks_404_page_http_status() {
	if ( volks_is_source_key( 'volks-404-v1' ) ) {
		status_header( 404 );
		nocache_headers();
	}
}
add_action( 'wp', 'volks_404_page_http_status' );

/**
 * Favicon from theme / uploads.
 */
function volks_site_icon_link() {
	$icon = volks_resolve_media_url( 'Fotos/Volksimmobilien - favicon.png' );
	if ( '' === $icon ) {
		return;
	}
	printf(
		'<link rel="icon" type="image/png" href="%s">' . "\n" . '<link rel="shortcut icon" type="image/png" href="%s">' . "\n",
		esc_url( $icon ),
		esc_url( $icon )
	);
}
add_action( 'wp_head', 'volks_site_icon_link', 2 );

/**
 * Document meta from imported fields.
 */
function volks_output_document_meta() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	$desc    = (string) get_post_meta( $post_id, 'leadwerk_meta_description', true );
	if ( '' !== trim( $desc ) ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	}
}
add_action( 'wp_head', 'volks_output_document_meta', 3 );

/**
 * Logo URL for header/footer.
 *
 * @return string
 */
function volks_get_logo_url() {
	$theme_logo_path = VOLKS_THEME_PATH . '/assets/Fotos/volksimmobilien-logo-weiss.webp';
	if ( is_readable( $theme_logo_path ) ) {
		return VOLKS_THEME_URI . '/assets/Fotos/volksimmobilien-logo-weiss.webp';
	}

	$url = volks_resolve_media_url( 'Fotos/volksimmobilien-logo-weiss.webp' );
	return '' !== $url ? $url : VOLKS_THEME_URI . '/assets/Fotos/volksimmobilien-logo-weiss.webp';
}

/**
 * Rewrite static .html links inside WPForms output (e.g. datenschutz.html in checkbox labels).
 *
 * @param string $html Form HTML.
 * @return string
 */
function volks_fix_wpforms_html_links( $html ) {
	if ( ! is_string( $html ) || '' === $html || false === strpos( $html, '.html' ) ) {
		return $html;
	}

	return function_exists( 'volks_normalize_html_fragment' )
		? volks_normalize_html_fragment( $html )
		: $html;
}
add_filter( 'wpforms_frontend_output', 'volks_fix_wpforms_html_links', 20 );
add_filter( 'wpforms_frontend_form_data', 'volks_fix_wpforms_form_data_links', 20 );

/**
 * @param array<string,mixed> $form_data Form data.
 * @return array<string,mixed>
 */
function volks_fix_wpforms_form_data_links( $form_data ) {
	if ( ! is_array( $form_data ) || empty( $form_data['fields'] ) || ! is_array( $form_data['fields'] ) ) {
		return $form_data;
	}

	foreach ( $form_data['fields'] as $field_id => $field ) {
		if ( ! is_array( $field ) ) {
			continue;
		}
		foreach ( array( 'label', 'description', 'code' ) as $key ) {
			if ( ! empty( $field[ $key ] ) && is_string( $field[ $key ] ) && false !== strpos( $field[ $key ], '.html' ) ) {
				$form_data['fields'][ $field_id ][ $key ] = volks_fix_wpforms_html_links( $field[ $key ] );
			}
		}
	}

	return $form_data;
}
