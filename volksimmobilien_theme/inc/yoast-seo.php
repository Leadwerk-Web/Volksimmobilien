<?php
/**
 * Yoast SEO integration for field-driven Volksimmobilien pages.
 *
 * @package Volksimmobilien
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Truncate a human-readable SEO title for Yoast pixel/width hints (character-based heuristic).
 *
 * @param string $title     Raw title.
 * @param int    $max_chars Maximum characters before ellipsis.
 * @return string
 */
function leadwerk_theme_truncate_seo_title_for_yoast( $title, $max_chars = 58 ) {
	$title = trim( (string) $title );
	if ( '' === $title ) {
		return '';
	}
	if ( $max_chars < 8 ) {
		$max_chars = 8;
	}
	if ( function_exists( 'mb_strlen' ) && function_exists( 'mb_substr' ) && mb_strlen( $title ) > $max_chars ) {
		return rtrim( mb_substr( $title, 0, $max_chars - 1 ) ) . '…';
	}
	if ( strlen( $title ) > $max_chars ) {
		return rtrim( substr( $title, 0, $max_chars - 1 ) ) . '…';
	}

	return $title;
}

/**
 * Build rendered page HTML for Yoast content analysis on Leadwerk field pages.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function leadwerk_theme_get_yoast_analysis_content( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! class_exists( 'Leadwerk_Content_Schema' ) ) {
		return '';
	}

	$group = Leadwerk_Content_Schema::get_group_for_post( $post_id );
	if ( ! $group || empty( $group['field_name'] ) ) {
		return '';
	}

	$field_name = (string) $group['field_name'];
	$value      = function_exists( 'volks_get_stored_field' )
		? volks_get_stored_field( $field_name, $post_id )
		: ( function_exists( 'get_field' ) ? get_field( $field_name, $post_id ) : null );

	$content = '';
	if ( function_exists( 'leadwerk_theme_render_exact_page_group' ) ) {
		$content = leadwerk_theme_render_exact_page_group( $group, $value, $post_id );
	} elseif ( function_exists( 'volks_render_page_content' ) ) {
		$content = volks_render_page_content( $post_id );
	}

	if ( '' === trim( wp_strip_all_tags( $content ) ) && false === strpos( $content, '<img' ) ) {
		return '';
	}

	$content = (string) preg_replace( '#<script[^>]*>.*?</script>#is', '', $content );
	$content = (string) preg_replace( '#<style[^>]*>.*?</style>#is', '', $content );

	$clean_content = wp_kses_post( $content );
	$clean_content = (string) str_replace( array( "\r", "\n", "\t" ), ' ', $clean_content );
	$clean_content = (string) preg_replace( '/\s+/', ' ', $clean_content );

	return trim( $clean_content );
}

/**
 * Rebuild Yoast SEO Indexable for one post (admin SEO dots, sitemap) after meta-only changes.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function leadwerk_theme_rebuild_yoast_post_indexable( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 || ! function_exists( 'YoastSEO' ) ) {
		return;
	}
	if ( ! class_exists( '\Yoast\WP\SEO\Integrations\Watchers\Indexable_Post_Watcher', false ) ) {
		return;
	}

	// Yoast intentionally skips persisting indexables in local/development
	// environments. The editor still saves its score post meta there, which
	// otherwise leaves the admin list showing a stale/grey score after Update.
	$force_local_indexable_save = function_exists( 'wp_get_environment_type' )
		&& in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	if ( $force_local_indexable_save ) {
		add_filter( 'Yoast\\WP\\SEO\\should_index_indexables', '__return_true', 999 );
		add_filter( 'wpseo_should_save_indexable', '__return_true', 999 );
	}

	try {
		$yoast = YoastSEO();
		if ( ! is_object( $yoast ) || ! isset( $yoast->classes ) || ! is_object( $yoast->classes ) || ! method_exists( $yoast->classes, 'get' ) ) {
			return;
		}

		$watcher = $yoast->classes->get( \Yoast\WP\SEO\Integrations\Watchers\Indexable_Post_Watcher::class );
		if ( is_object( $watcher ) && method_exists( $watcher, 'build_indexable' ) ) {
			$watcher->build_indexable( $post_id );
		}
	} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		return;
	} finally {
		if ( $force_local_indexable_save ) {
			remove_filter( 'Yoast\\WP\\SEO\\should_index_indexables', '__return_true', 999 );
			remove_filter( 'wpseo_should_save_indexable', '__return_true', 999 );
		}
	}
}

/**
 * After saving a Leadwerk-managed page, refresh Yoast indexables.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @param bool    $update  Whether this is an existing post.
 * @return void
 */
function leadwerk_theme_leadwerk_page_yoast_indexable_touch( $post_id, $post, $update ) {
	unset( $update );
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'page', 'volks_property' ), true ) ) {
		return;
	}
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$is_local_environment = function_exists( 'wp_get_environment_type' )
		&& in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	$is_leadwerk_page = 'page' === $post->post_type
		&& '' !== (string) get_post_meta( $post_id, 'leadwerk_source_key', true );
	if ( ! $is_local_environment && ! $is_leadwerk_page ) {
		return;
	}

	leadwerk_theme_rebuild_yoast_post_indexable( $post_id );
}
// Yoast stores its classic-editor score meta on wp_insert_post (priority 10),
// which runs after save_post. Rebuild only after those values are available.
add_action( 'wp_insert_post', 'leadwerk_theme_leadwerk_page_yoast_indexable_touch', 99, 3 );

/**
 * Refresh the indexable after Gutenberg/REST has stored its registered meta.
 *
 * @param WP_Post $post     Saved post.
 * @param mixed   $request  REST request (unused).
 * @param bool    $creating Whether the post is being created.
 * @return void
 */
function leadwerk_theme_rest_yoast_indexable_touch( $post, $request, $creating ) {
	unset( $request );
	if ( ! $post instanceof WP_Post ) {
		return;
	}

	leadwerk_theme_leadwerk_page_yoast_indexable_touch( $post->ID, $post, ! $creating );
}
add_action( 'rest_after_insert_page', 'leadwerk_theme_rest_yoast_indexable_touch', 99, 3 );
add_action( 'rest_after_insert_volks_property', 'leadwerk_theme_rest_yoast_indexable_touch', 99, 3 );

/**
 * Feed rendered Volks page content into Yoast's readability/SEO analysis in the editor.
 *
 * @param string $hook_suffix Current admin hook.
 * @return void
 */
function leadwerk_theme_enqueue_admin_yoast_analysis( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || ! class_exists( 'WPSEO_Options' ) || ! function_exists( 'get_current_screen' ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'post' !== $screen->base ) {
		return;
	}

	$post_id = 0;
	if ( isset( $_GET['post'] ) ) {
		$post_id = (int) $_GET['post'];
	} elseif ( isset( $_POST['post_ID'] ) ) {
		$post_id = (int) $_POST['post_ID'];
	}

	if ( $post_id <= 0 ) {
		return;
	}

	if ( '' === (string) get_post_meta( $post_id, 'leadwerk_source_key', true ) ) {
		return;
	}

	$analysis_content = leadwerk_theme_get_yoast_analysis_content( $post_id );
	if ( '' === $analysis_content ) {
		return;
	}

	$max_bytes = (int) apply_filters( 'leadwerk_yoast_analysis_inline_max_bytes', 350000 );
	if ( $max_bytes > 0 && strlen( $analysis_content ) > $max_bytes ) {
		$analysis_content = substr( $analysis_content, 0, $max_bytes );
	}

	$payload = array(
		'postId'          => $post_id,
		'renderedContent' => $analysis_content,
	);
	$json    = wp_json_encode(
		$payload,
		JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	);
	if ( false === $json ) {
		$payload['renderedContent'] = substr( wp_strip_all_tags( $analysis_content ), 0, 60000 );
		$json                       = wp_json_encode(
			$payload,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
	}
	if ( false === $json ) {
		return;
	}

	wp_enqueue_script(
		'leadwerk-admin-yoast-analysis',
		VOLKS_THEME_URI . '/assets/js/admin-yoast-analysis.js',
		array(),
		VOLKS_THEME_VERSION,
		true
	);

	wp_add_inline_script(
		'leadwerk-admin-yoast-analysis',
		'window.leadwerkYoastAnalysis = ' . $json . ';',
		'before'
	);
}
add_action( 'admin_enqueue_scripts', 'leadwerk_theme_enqueue_admin_yoast_analysis', 100 );
