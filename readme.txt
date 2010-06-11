=== In Over Your Archives ===
Contributors: stresslimitdesign
Tags: inoveryourhead, julien smith, archives, archive, posts, jquery
Requires at least: 2.8
Stable tag: 1.0

This plugin will display your archive page in a nice way, just like on inoveryourhead.net

== Description ==

This plugin will display your archive page in a nice way, just like on inoveryourhead.net

== Installation ==

1. Extract the contents of the zip file into your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You can either create an archive page with a template that includes the code `<?php echo in_over_your_archives(); ?>`, or create a page with the shortcode `[ioya]`
1. Enjoy!

== Other Notes ==

= Customizing the template =

You can customize the look and feel of the archives pages.

Add a file called `ioya_month.php` to your theme directory and add a basic loop in it as follows:

`
<?php while (have_posts()) : the_post(); ?>
    
    <div class="post">    
		<div class="date">
        	<div class="date-day"><?php the_time('j') ?></div>
            <div class="date-month"><?php the_time('M') ?></div>
        </div>
        <h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
        <small>Posted by <?php the_author_link() ?> at <?php the_time('g:i A') ?></small>
        <div class="entry">
        	<?php echo $paragraph[0]; ?>
        </div>
    </div>
<?php endwhile; ?>
`

Then modify as necessary.

== Screenshots ==

1. This is the color customization in the admin section

== Changelog ==

= 1.0 =

* Initial release

== Frequently Asked Questions ==
