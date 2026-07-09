<?php
/**
 * Front page template.
 *
 * @package Volksimmobilien
 */

get_header();
?>
<main id="main" class="site-main">
	<?php echo volks_render_page_content( (int) get_option( 'page_on_front' ) ?: get_queried_object_id() ); ?>
</main>
<?php
get_footer();
