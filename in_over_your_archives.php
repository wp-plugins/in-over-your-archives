<?php
/*
Plugin Name: In Over Your Archives
Plugin URI: http://wordpress.org/extend/plugins/in-over-your-archives/
Description: This plugin will display your archive page in a nice way, just like on inoveryourhead.net
Version: 1.2
Author: stresslimit
Author URI: http://stresslimitdesign.com

Copyright 2010 stresslimit (http://stresslimitdesign.com)

*/

/*  --------------------------------------------------------------
	EXPLAIN CALL STACK
	-------------------------------------------------------------

wp action init:
ioya_init(): empty for now

Front end:

possible entry points:
* using the shortcode in a page [ioya_shortcode()], which gets the month&year from 
  either the getquerystring['m/y'] or php date("m/Y"), and passes to ioya_archive()
* calling <?php ioya_archive(); ?> from either the default ioya_archives.php or in
  a theme archive.php page

ioya_archive(): main function to display the archive
* puts css, basic html & js in place, and calls ioya_update_year() and ioya_update_month()
  to put the bits in the right places

ioya_update_year(): 


Admin:

wp action admin_init:
ioya_admin_init(): sets up options

wp action admin_menu:
ioya_admin_menu(): creates menu option

wp option-general, page=ioya:
ioya_options(): creates our admin page

*/


/*  --------------------------------------------------------------
	SETUP AND DO THE STUFF
	-------------------------------------------------------------*/

define( 'IOYA_PLUGIN_URL', path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ).'' ) );
//define( 'IOYA_PLUGIN_URL', '/wp-content/plugins/in-over-your-archives/' );
define( 'IOYA_PLUGIN_PATH', dirname( __FILE__ ) );
define( 'IOYA_OPTIONS_KEY', 'ioya_' );
define( 'IOYA_THUMBNAIL_FIELD', 'inoveryourthumb' );

// Main init function
add_action('init', 'ioya_init');

// Admin setup
add_action('admin_init', 'ioya_admin_init' );
add_action('admin_menu', 'ioya_admin_menu');

// Setup scripts and catch ajax
add_action('wp_print_scripts', 'ioya_register_scripts');
add_action('wp_print_styles', 'ioya_register_styles');
add_action('admin_print_scripts', 'ioya_admin_scripts');
add_action('init', 'ioya_ajax');

// Replace the default archive page
add_filter('archive_template', 'ioya_replacement');
// Change the query vars if needed
add_action('pre_get_posts', 'ioya_get_posts');

// Register shortcode
add_shortcode('ioya', 'ioya_shortcode');

function ioya_init( ) {	
}

// Switch template to IOYA
function ioya_replacement() {
	// Allow users to override the archive template
	$template = locate_template( 'ioya_archive.php' );
	if( !$template ) $template = IOYA_PLUGIN_PATH . '/ioya_archives.php';
	return $template;
}

// Set posts per page to unlimited (we don't support paging just yet)
function ioya_get_posts( $query ) {
	if( is_archive() )
		$query->query_vars['posts_per_page'] = -1;
		$query->query_vars['showposts'] = -1;
}

// Add scripts to head on archive pages
function ioya_register_scripts() {
	if ( is_archive() ) {
		wp_enqueue_script('ioyh', IOYA_PLUGIN_URL . '/js/in_over_your_jquery.js', array('jquery'));
		//wp_localize_script('ioyh', 'in_over_your_settings', array('year' => date('Y'), 'month' => date('m') ));
	}
}
// Add CSS to head on archive pages
function ioya_register_styles() {
	if ( is_archive() ) {
	   	wp_enqueue_style('ioya', IOYA_PLUGIN_URL . '/css/in_over_your_css.css', false, false, 'all');
		ioya_the_custom_colours();
	}
}

// Add scripts and styles needed for options page
function ioya_admin_scripts() {
	global $pagenow, $plugin_page;
	
	if( is_admin() && ($pagenow == 'options-general.php' && $plugin_page == 'ioya') ) {
		wp_enqueue_script('jquery-color-picker', IOYA_PLUGIN_URL . '/js/colorpicker.js', array('jquery'));
		wp_enqueue_script('ioya-options', IOYA_PLUGIN_URL . '/js/ioya-options.js', array('jquery'));
		?>
		<link href="<?php echo IOYA_PLUGIN_URL . '/css/colorpicker.css' ?>" rel="stylesheet" media="screen" />
		<style type="text/css">
		.ioya_color_preview { width: 35px; line-height: 1em; padding: 3px; }
		</style>
		<?php
	}
}

/*  --------------------------------------------------------------
	ADMIN STUFF
	-------------------------------------------------------------*/

// Initialize our settings
function ioya_admin_init(){
	/**
	 * NOTE: To add new settings:
	 *   - add it to the array below as a slug, e.g. enable_shortcode (when saved to the database, it will automatically, be prefixed with ioya_)
	 *   - then add an entry for it in ioya_options function. You can use ioya_options_text_field() to easily create text fields
	 *   - you can fetch option values using ioya_get_option( $slug ) where $slug is the name you entered in the array below
	 */
	$ioya_options = array(
		'colours',
		'escapes'
	);
	
	// "Register" the setting so WordPress knows about it
	foreach( $ioya_options as $option ) {
		$option_name = ioya_get_option_name( $option );
		register_setting( IOYA_OPTIONS_KEY, $option_name );
	}
}

// Create our admin menu hook
function ioya_admin_menu() {
	add_options_page(__('In Over Your Archives Settings', 'ioya'), __('In Over Your Archives', 'ioya'), 'manage_options', 'ioya', 'ioya_options');
}

function ioya_options() {
?>

	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br/></div>
		<h2><?php _e('In Over Your Archives Settings', 'ioya') ?></h2>
		
		<form method="post" action="options.php">
			
			<?php settings_fields(IOYA_OPTIONS_KEY); ?>
			
			<?php
			/*
			<h3><?php _e('Custom Templates', 'ioya') ?></h3>
			<p><?php _e('To customize the look and feel of In Over Your Archives, so the following:', 'ioya') ?></p>
			<p><?php _e('TODO: info about shortcode, custom month template, etc. goes here', 'ioya') ?></p>
			*/
			?>
			<h3><?php _e('Colors', 'ioya') ?></h3>
			
			<p><?php _e('We\'ve made it very easy for you to customize the look of your archives. Use the fields below to customize the colors.', 'ioya') ?></p>
			
			<table class="form-table">				
				<?php 
				ioya_options_colour_field( __('Current Year Text', 'ioya'), 'current_year_text', __('The colour of the current year.', 'ioya'), '#000');
				ioya_options_colour_field( __('Selected Month Background', 'ioya'), 'current_month_bg', __('The colour of the selected month and year arrows.', 'ioya'), '#aa2528');
				ioya_options_colour_field( __('Selected Month Text', 'ioya'), 'current_month_text', __('The colour of the text for the selected month.', 'ioya'), '#fff');
				ioya_options_colour_field( __('Month Link', 'ioya'), 'active_month_text', __('The colour of the month links.', 'ioya'), '#aa2528');
				ioya_options_colour_field( __('Month No Link', 'ioya'), 'inactive_month_text', __('The colour of the months with no posts.', 'ioya'), '#bebebe');
				ioya_options_colour_field( __('Moving Block', 'ioya'), 'moving_block_bg', __('The colour of the moving block.', 'ioya'), '#bebebe');
				?>
			</table>

			<h3><?php _e('Advanced', 'ioya') ?></h3>
			
			<p><?php _e('Here you have some other advanced options.', 'ioya') ?></p>
			
			<table class="form-table">				
				<?php 
				ioya_options_text_field( __('Images to Ignore', 'ioya'), 'escapes', __('Enter the names of small or unimportant images that we should ignore, separated by commas.<br/>These are regular expressions so you can use a filename, a domain, or a keyword.', 'ioya') );
				?>
			</table>

			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Save Changes', 'ioya') ?>" />
			</p>
		</form>
	</div>
<?php
}

// Spits out a text field
function ioya_options_text_field( $name, $slug, $description = '', $default_value = '') {
		$option_name = ioya_get_option_name( $slug );
		$option_value = ioya_get_option( $slug );
		if( !$option_value )
			$option_value = $default_value;
		?>
		<tr valign="top">
			<th scope="row">
				<label for="ioya_<?php echo $slug ?>"><?php echo $name ?></label>
				</th>
			<td>
				<input type="text" id="ioya_<?php echo $slug ?>" name="<?php echo $option_name ?>" value="<?php echo esc_attr($option_value) ?>" />
				<?php if( $description ) : ?>
					<br />
					<span class="description"><?php echo $description ?></span>
				<?php endif; ?>
			</td>
		</tr>
	<?php
}

// Spits out a text field to allow users to pick a colour
function ioya_options_colour_field( $name, $slug, $description = '', $default_value = '') {
		$option_name = ioya_get_option_name( 'colours' ) .'['. $slug .']';
		$option_value = ioya_get_option( 'colours' );
		$colour = ioya_get_colour($option_value[$slug], $default_value);
		?>
		<tr valign="top">
			<th scope="row">
				<label for="ioya_<?php echo $slug ?>"><?php echo $name ?></label>
				</th>
			<td>
				<input type="text" id="ioya_<?php echo $slug ?>" name="<?php echo $option_name ?>" value="<?php echo esc_attr($colour) ?>" class="colour" size="8" maxlength="7" />
				<span class="ioya_color_preview" style="background-color:<?php echo esc_attr($colour)?>">&nbsp;</span>
				<br />
				<span class="description"><?php echo $description ?></span>
			</td>
		</tr>
	<?php
}
function ioya_is_valid_colour( $colour ) {
	return preg_match('/^#?([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?$/', $colour);
}

function ioya_get_colour( $colour, $default = '' ) {
	return ( $colour && ioya_is_valid_colour( $colour )) ? $colour : $default;
}
function ioya_the_colour( $colour, $default = '' ) {
	echo ioya_get_colour( $colour, $default );
}
function ioya_the_custom_colours( ) {
	$ioya_colours = ioya_get_option( 'colours' );
	?>
	<!-- Custom CSS Styles for IOYA -->
	<style type="text/css">
		/* Month with no posts */
		div#inoveryourarchives ul li { color:<?php ioya_the_colour( $ioya_colours['inactive_month_text'], '#bebebe' ); ?>; }
		/* Month with posts */
		div#inoveryourarchives ul li a { color:<?php ioya_the_colour( $ioya_colours['active_month_text'], '#000' ); ?>; }
		/* Current month */
		div#inoveryourarchives ul li.selected, 
		div#inoveryourarchives ul li.selected a,
		div#inoveryourarchives ul li.selected a:hover {
			color: <?php ioya_the_colour( $ioya_colours['current_month_text'], '#fff' ); ?>;
			background: <?php ioya_the_colour( $ioya_colours['current_month_bg'], '#aa2528' ); ?>;
		}
		/* Year */
		div#inoveryourarchives ul li.date { color:<?php ioya_the_colour( $ioya_colours['current_year_text'], '#000' ); ?>; }
		/* Year arrows */
		div#inoveryourarchives ul li.date a { color:<?php ioya_the_colour( $ioya_colours['current_month_bg'], '#aa2528' ); ?>; }
		/* Slider */
		div#inoveryourarchives .slider { background:<?php ioya_the_colour( $ioya_colours['moving_block_bg'], '#bebebe' ); ?>; }
	</style>
	<?php
}

// Returns the name of the option as it's stored in the db
function ioya_get_option_name( $name ) {
	return IOYA_OPTIONS_KEY . $name;
}

function ioya_get_option( $name = '' ) {
	$option = get_option( ioya_get_option_name( $name ) );
	return $option;
}

/*  --------------------------------------------------------------
	TEMPLATE STUFF
	-------------------------------------------------------------*/

function ioya_update_year($current_year = false, $current_month = false, $ajax = false) {
	global $wp_query, $wpdb;

	if ( !$current_year )
		$current_year = ioya_get_queried_year();

	if ( !$current_month )
		$current_month = ioya_get_queried_month();

	// Check if we have posts from the previous year
	$prev_year_posts = new WP_Query(array('year' => ($current_year - 1)));

	// Check if we have posts from next year
	$next_year_posts = new WP_Query(array('year' => ($current_year + 1)));

	// Check to see which months for the current year have posts
	// returns an ordered array of the month number, ie (1,2,4) = posts in jan, feb, apr
	$results = $wpdb->get_col("SELECT month(post_date) as posts FROM {$wpdb->posts} WHERE post_type = 'post' and post_status = 'publish' and year(post_date) = $current_year GROUP BY month(post_date)");

	// If we don't have posts for this month, reset the month so we can find a month that does.
	$posts = $wp_query->get_posts();
	if( empty($posts) )
		$current_month = 0;
	if( $current_month == 0 )
		$current_month = ioya_get_recent_month_with_posts($current_year, $current_month, $results); // pass $results to not duplicate query

	$month_has_posts = array();
	$month_count = ($current_month) ? $current_month - 1 : 0;
	$results_count = 0;
	
	foreach( $results as $month ) {
		$month_has_posts[$month] = true;
		$results_count++;
	}

  	?>
    <div id="inoveryouryear_<?php echo esc_attr($current_year) ?>" class="inoveryouryear">
        <ul>
          <li class="date"><?php
        	 if($prev_year_posts->have_posts()) :
        	 ?><a href="<?php echo get_month_link($current_year - 1, ioya_format_month($current_month)); ?>" class="prevyear" rel="<?php echo ($current_year-1) . ioya_format_month($current_month) ?>">&lt;</a><?php
        	 endif;
        	 
        	 if($next_year_posts->have_posts()) :
        	 ?><a href="<?php echo get_month_link($current_year + 1, ioya_format_month($current_month)); ?>" class="nextyear" rel="<?php echo ($current_year+1) . ioya_format_month($current_month) ?>">&gt;</a><?php
        	 endif;
        	 ?><span><?php echo $current_year; ?></span></li>
    			
          <?php
          for($month = 12; $month >= 1; $month--) :
          $selected = ($current_month == $month) ? ' class="selected"' : '';
          if( isset( $month_has_posts[$month] ) && $month_has_posts[$month] ) : ?>
          <li<?php echo $selected; ?>><a href="<?php echo get_month_link($current_year, $month) ?>" rel="<?php echo $current_year . ioya_format_month($month) ?>"><?php echo ioya_month_string($month); ?></a></li>
          <?php else : ?>
          <li<?php echo $selected; ?>><span rel="<?php echo $current_year . ioya_format_month($month) ?>"><?php echo ioya_month_string($month); ?></span></li>
          <?php endif; ?>
          <?php endfor; ?>
        </ul>
      </div>

<?php if( !$ajax ) : ?>
<script type="text/javascript">
var in_over_your_settings = { year: <?php echo esc_js($current_year) ?>, month: <?php echo esc_js($current_month) ?> };
</script>
<?php endif; ?>
<?php
}

function ioya_month_string($monthnum) {
	return date("M",mktime(1,1,1,$monthnum));
}

function ioya_update_month($current_year = false, $current_month = false) {
	global $wp_query;

	if ( !$current_year )
		$current_year = ioya_get_queried_year();
	
	if ( !$current_month )
		$current_month = ioya_get_queried_month();

	// Grab a list of the posts for this month
	$posts =  $wp_query->get_posts( array(
		'posts_per_page' => -1
		, 'showposts' => -1
		
	) );

	if( empty($posts) ) {
		$current_month = ioya_get_recent_month_with_posts($current_year, $current_month);
		$wp_query = new WP_Query( array(
			'year' => $current_year
			, 'monthnum' => $current_month
			, 'posts_per_page' => -1
			, 'showposts' => -1
			
		) );
		$posts =  $wp_query->get_posts();
	}

	// Allow users to add custom month template in their theme
	$template = locate_template( 'ioya_month.php' );
	?>
  	<div id="inoveryourmonth_<?php echo esc_attr($current_year) . esc_attr(ioya_format_month($current_month)); ?>" class="inoveryourpostswrapper inoveryourmonth">
	<?php
		if( $template ) {
			require_once( $template );
		} else {
			require_once( 'ioya_month.php' );
		}
	?>
  </div>
	<?php
}

function ioya_shortcode() {
	global $wp_query;
	
	// This WP_Query bit is a bit of hack.
	// Save a temp copy of the original query
	$tmp_query = $wp_query;
	
	// Get the year and month vars
	$year = ioya_get_queried_year();
	$month = ioya_get_queried_month();
	
	// Create the new query
	$wp_query = new WP_Query( array(
		'year' => $year
		, 'monthnum' => $month
		, 'posts_per_page' => -1
		, 'showposts' => -1
		
	) );
	
	// Show the archive
	ioya_archive($year, $month, true);
	
	// Revert back to the original query
	$wp_query = $tmp_query;
}

/**
 * Displays IOYA archive
 * @param $year
 * @param $month
 * @param $load_scripts is used for shortcode pages to load necessary CSS and JS
 */
function ioya_archive( $year = false, $month = false, $load_scripts = false ) {
	?>
	
	<?php if( $load_scripts ) : ?>
		<?php // Yes, we know the stylesheet below breaks validation, but stop whining :P This is the best way to do it without loading the stylesheet across all pages. Plues, HTML5 is on our side anyway :) ?>
		<link rel="stylesheet" type="text/css" href="<?php echo IOYA_PLUGIN_URL . '/css/in_over_your_css.css' ?>" media="screen" />
		<?php ioya_the_custom_colours() ?>
	<?php endif; ?>

	<div id="inoveryourarchives">
    	<div id="inoveryouryears">
    		<?php ioya_update_year($year, $month); ?>
    	</div>
    	<div id="inoveryourmonths">
    		<?php ioya_update_month($year, $month); ?>
    	</div>
 	</div>

	<?php if( $load_scripts ) : ?>
		<script type="text/javascript">
			// Loads necessary scripts if needed
			if( typeof(jQuery) === 'undefined' )
				document.write('<scr'+'ipt type="text/javascript" src="<?php bloginfo('siteurl') ?>/wp-admin/load-scripts.php?c=1&load=jquery"></scr'+'ipt>');
			if( typeof(ioya_js_loaded) === 'undefined' )
				document.write('<scr'+'ipt type="text/javascript" src="<?php echo IOYA_PLUGIN_URL . '/js/in_over_your_jquery.js' ?>"></scr'+'ipt>');
		</script>
	<?php endif; ?>
	
	<?php
}

/*  --------------------------------------------------------------
	HELPER FUNCTIONS
	-------------------------------------------------------------*/

/*  catch  ajax calls  */

function ioya_ajax() {
	if(isset( $_POST["ioyh"]))
		add_action('wp', 'ioya_archive_ajax');
}

function ioya_archive_ajax() {
	$yr = isset( $_REQUEST["yr"] ) ? intval( $_REQUEST["yr"] ) : 0;
	$mth = isset( $_REQUEST["mth"] ) ? intval( $_REQUEST["mth"] ) : 0;
	
	if ( $_POST["ioyh"] == 'y' )
		ioya_update_year( $yr, $mth, true );
	else if ( $_POST["ioyh"] == 'm' )
		ioya_update_month( $yr, $mth );
	
    exit();
} 

function ioya_get_queried_year() {
	global $wp_query;
	
	if( $wp_query->query_vars['m'] )
		return substr( $wp_query->query_vars['m'], 0, 4);
	else if ( $wp_query->query_vars['year'] )
		return $wp_query->query_vars['year'];
	else
		return date('Y');
}

function ioya_get_queried_month() {
	global $wp_query;
	
	if( $wp_query->query_vars['m'] )
		return substr( $wp_query->query_vars['m'], 4, 6);
	else if ( $wp_query->query_vars['monthnum'] ) 
		return $wp_query->query_vars['monthnum'];
	else
		return date('m');
}

function ioya_format_month( $month ) {
	return str_pad($month, 2, "0", STR_PAD_LEFT);
}

function ioya_get_recent_month_with_posts( $current_year, $current_month, $results = false ) {
	global $wpdb;
	
	// Check to see which months for the current year have posts, if we're not already doing the same query
	if(!$results) {
		$results = $wpdb->get_col("SELECT month(post_date) as posts FROM {$wpdb->posts} WHERE post_type = 'post' and post_status = 'publish' and year(post_date) = $current_year GROUP BY month(post_date)");
	}

	$results_count = 0;
	foreach( $results as $month ) {
		// If the month isn't set, set it to the latest month with posts
		if( !$current_month && ( ($results_count + 1) == count($results) ) ) {
			$month_with_posts = $month;
			break;
		}
		$results_count++;
	}
	return $month_with_posts;
}


/*  get post images */

function ioya_get_images( $posts, $num = 8, $size = 'medium' ) {

	$usedefault = false;
	$i = 0;
	$images = '';

	// randomize images
	shuffle($posts);
	
	foreach( $posts as $post ) {
		
		if( $i >= $num ) break;
		
		$img_out = '';
		$post_id = $post->ID;

		// Yeah, I'm using an if-elseif-else block here; SUE ME!
		if ( $custom_img = ioya_get_custom_img( $post_id ) ) {
			// Image inside custom field
		} else if ( $post_img = ioya_get_attached_img($post_id, $size) ) {
			// Post_thumbnail and attached image
		} else if ( $post_img = ioya_get_inline_img($post) ) {
			// Inline images inside post content
		} else {
			// Fail and try another post
			continue;
		}

		// If custom_meta image, display that
		if( $custom_img ) {
			$img_out = '<img title="'.$post->ID.'" src="'.$custom_img.'" width="44" height="44" alt="'.$post->post_title.'" />';
		} else if ( !empty($post_img) ) {
			// Check if we're getting a custom size
			if( is_array($size) ) {
				// make auto-thumb of the image
				$post_img = ioya_resize_img($post_img['url'], $post_img['path'], $size);
				$width = $size[0];
				$height = $size[1];
			}
			// Something went wrong with the resizing, so skip to the next one
			if( !$post_img ) continue;
			$img_out = '<img title="'.__('Permanent link to ', 'ioya') . $post->post_title.'" src="'.$post_img.'" width="44" height="44" alt="'.$post->post_title.'" />'."";
		}
		
		if( $img_out ) {
			$images .= '<a href="'. get_permalink($post_id) .'">';
			$images .= $img_out;
			$images .= '</a>';
		}

		$i++;
	}
	return $images;
}

function ioya_the_images( $posts, $num = 8, $size = 'medium' ) {
	echo ioya_get_images( $posts, $num, $size );
}

function ioya_get_custom_img( $post_id ) {
	
	if( function_exists('get_metadata') )
		$custom_thumb = get_metadata('post', $post_id, IOYA_THUMBNAIL_FIELD, true);
	else 
		$custom_thumb = get_post_meta($post_id, IOYA_THUMBNAIL_FIELD, true);
	
	$custom_thumb_path = '/images/thumbs/';
	if ( isset( $custom_thumb ) && $custom_thumb != '' && is_file(TEMPLATEPATH.$custom_thumb_path.$custom_thumb) )
		return get_bloginfo('template_url').$custom_thumb_path.$custom_thumb;
	
	return false;
}

function ioya_get_attached_img( $post_id, $size ) {
	// Check for a post thumbnail first
	if( function_exists('get_post_thumbnail_id') )
		$attached_img_id = get_post_thumbnail_id($post_id);

	// Didn't find a post thumbnail, so check for attached images
	if( ! $attached_img_id ) {
		$img_attachments = get_children(
			array( 
				'post_parent' => $post_id,
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'numberposts' => 1,
				'order' => 'ASC',
				'orderby' => 'menu_order ID'
			)
		);
		
		if ( ! empty( $img_attachments ) ) {
			$image = array_pop( $img_attachments ); // $img_attachments should only have one image in it, due to numberposts=1
			$attached_img_id = $image->ID;
		}
	}

	if( $attached_img_id ) {
		$img_src = wp_get_attachment_image_src( $attached_img_id, $size );
		$img_path = get_attached_file( $attached_img_id );
		if( ioya_is_valid_img( $img_src[0] ) && @file_exists($img_path) ) return array( 'url' => $img_src[0], 'path' => $img_path);
	}
	return false;
}

function ioya_get_inline_img( $post ) {
	// Search for image tags and pull the source
	preg_match_all( '|<img.*?src=[\'"](.*?)[\'"].*?>|i', $post->post_content, $matches );
	// Make sure it's a valid image
	if ( isset( $matches[1][0] ) && ioya_is_valid_img( $matches[1][0] ) ) {

		$img_src = $matches[1][0];

		// Okay, now let's download the image
		$upload_dir = wp_upload_dir();
		if( !$upload_dir || !$upload_dir['path'] ) {
			$dir_url = IOYA_PLUGIN_URL . '/cache/';
			$dir_path = IOYA_PLUGIN_PATH . '/cache/';
			@chmod( $dir_path, 0777 );
		} else {
			$dir_url = $upload_dir['url'] . '/';
			$dir_path = $upload_dir['path'] . '/';
		}
		$filename = explode('/', $img_src);
		$filename = $filename[count($filename) - 1];
		$img_path =  $dir_path . $filename;

		if ( !@file_exists($img_path) ) {
			// Download image and save it
			$request = new WP_Http;
			$result = $request->request( $img_src );
			if( !is_wp_error( $result ) && $result['body'] ) {
				if( ($fh = @fopen($img_path, 'w')) === FALSE) return false;
				$img = $result['body'];
				if( @fwrite($fh, $img) === FALSE )
					return false;
				@fclose($fh);
			} else {
				return false;
			}
		}
		if(!getimagesize($img_path)) return false;
		return array( 'path' => $img_path, 'url' => $dir_url . $filename );
	}
	return false;
}

function ioya_is_valid_img( $img_src ) {
	// don't want script to scrape the following images:
	$escapes = ioya_get_option('escapes');
	if(!$escapes)
		return true;
	$escapes = explode(',', $escapes);
	$escapes = array_map('ioya_escapes_format', $escapes);
	// $escapes = array(
	// 	'/odeo\.com/',
	// 	'/subs_itunes/',
	// 	'/headernewlogo\.gif/',
	// 	'/godaddy\.jpg/',
	// 	'/feed-icon/'
	// );
	
	// see if found image is one we don't want
	foreach( $escapes as $v ) {
		if( preg_match( $v, $img_src ) ) return false;
	}
	return true;
}

function ioya_escapes_format($e) {
	return '/'.str_replace(array('/','.'),array('\/','\.'),trim($e)).'/';
}

function ioya_resize_img( $url, $path, $size ) {

	// Include WordPress Image functions | needed to resize
	if( !function_exists('load_image') )
		require_once(ABSPATH.'/wp-admin/includes/image.php');

	$suffix = "{$size[0]}x{$size[1]}";
	$info = pathinfo($path);
	$dir = $info['dirname'];
	$ext = $info['extension'];
	$name = basename($path, ".{$ext}");
	$img_file = "{$name}-{$suffix}.{$ext}";
	$img_path = "{$dir}/{$img_file}";

	if ( !@file_exists($img_path) ) {
		// Crop & Resize the image
		$img_resized = image_make_intermediate_size( $path, $size[0], $size[1], true );
		if( $img_resized ) {
			// Get the new image URL
			return path_join( dirname($url), $img_resized['file'] );
		}
	} else {

		return path_join( dirname($url), $img_file );
	}

	return false;
}
