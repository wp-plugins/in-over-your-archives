
<?php if(have_posts()) : ?>
  <div class="imagethumbs"><?php ioya_the_images( $posts, apply_filters( 'ioya_image_count', 8 ), apply_filters( 'ioya_image_size', array(44, 44) ) ); ?></div>

	<?php while (have_posts()) : the_post(); ?>
		
		<div class="post">
			<small><?php the_date(); ?></small>
			<h3><a href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
			<?php the_excerpt(); ?>
		</div>
	<?php endwhile; ?>
<?php else : ?>
	<div class="post">
		<p><?php _e('Sorry, we didn\'t find any posts for this month.', 'ioya'); ?></p>
	</div>
<?php endif; ?>
