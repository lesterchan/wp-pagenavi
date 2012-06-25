<?php
// Template Name: WPN Debug

get_header();

query_posts( array( 'post_type' => 'page', 'paged' => get_query_var( 'paged' ) ) );
?>

<div id="primary">
	<div id="content" role="main">

	<ol>
	<?php while ( have_posts() ) : the_post(); ?>
		<li><?php the_title(); ?>
	<?php endwhile?>
	</ol>

	<?php wp_pagenavi(); ?>

	</div><!-- #content -->
</div><!-- #primary -->

<?php get_footer(); ?>
