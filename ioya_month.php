
<?php if(have_posts()) : ?>
  <div class="imagethumbs"><?php ioya_the_images( $posts, 8, array(44, 44) ); ?></div>

	<?php while (have_posts()) : the_post(); ?>
		<?php
		$text = wpautop(get_the_content());
		preg_match('/<p>(.*)<\/p>/', $text, $paragraph);
		?>
		
		<div class="post">
			<small><?php the_date()//echo date_format($post->post_date,'l, F jS, Y') ?></small>
			<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
			<?php echo $paragraph[0];//the_excerpt(); ?>
		</div>
	<?php endwhile; ?>
<?php else : ?>
	<div class="post">
		<p><?php _e('Sorry, we didn\'t find any posts for this month.', 'ioya'); ?></p>
	</div>
<?php endif; ?>