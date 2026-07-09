<?php
/**
 * 404 template (volksimmobilien).
 *
 * @package Volksimmobilien
 */

status_header( 404 );
nocache_headers();

get_header();
?>
<main id="main" class="site-main">
	<?php echo volks_render_404_page(); ?>
</main>
<?php
get_footer();
