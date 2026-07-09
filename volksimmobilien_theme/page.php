<?php
/**
 * Page template.
 *
 * @package Volksimmobilien
 */

get_header();
?>
<main id="main" class="site-main">
	<?php
	while ( have_posts() ) {
		the_post();
		echo volks_render_page_content( get_the_ID() );
	}
	?>
</main>
<?php
get_footer();
