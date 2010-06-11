<?php get_header(); ?>
	<div id="page">
		<div <?php post_class() ?>>
			<?php if (have_posts()) : ?>
				<?php ioya_archive(); ?>
			<?php else : ?>
				<h2 class="center">Not Found</h2>
				<?php include (TEMPLATEPATH . '/searchform.php'); ?>
			<?php endif; ?>
		</div>
	</div>
<?php get_sidebar(); ?>

<?php get_footer(); ?>
