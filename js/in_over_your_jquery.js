var ioya_js_loaded = true;

jQuery(document).ready(function($) {

	var slider, $ioya_years_container, $ioya_months_container, year, month;
	
	year	= parseInt(in_over_your_settings.year);
	month	= parseInt(in_over_your_settings.month);
	
	$ioya_years_container = jQuery('#inoveryouryears');
	$ioya_months_container = jQuery('#inoveryourmonths');
	
	setUpSlider();
	
	jQuery('.inoveryouryear').data('loaded', true);
	jQuery('.inoveryourmonth').data('loaded', true);
	
	// Handle clicks from the calendar thingy
	jQuery('#inoveryourarchives ul li a').live("click", function(){			// added .live() to bind to all current *and future* elements on the page
		var $this = jQuery(this);
	
		// Keep track of the year and month clicked. Year and month are stored in the link's rel attribute.
		year = $this.attr('rel').substring(0, 4);

		if ( $this.parent().hasClass('date') ) {
			// We're calling a new year
			return ioya_update_year( $this, year, month );
		} else { 
			// We're calling a month
			month = $this.attr('rel').substring(4, 6);
			return ioya_update_month( $this, year, month );
		}
	});
	
	function ioya_update_year( $link, y, m ) {
		
		var $ioya_year = get_year_block( y );
		
		// Check to see if we've previously loaded the month; if so, just display it, otherwise make the AJAX call
		if ( $ioya_year.length && $ioya_year.data('loaded') == true ) {
			animate_year($ioya_year, function() {
				// Switch to correct month
				$month_link = find_month_link($ioya_year, y, m);
				m = month = $month_link.attr('rel').substring(4, 6); // update the global month var in case it's changed
				return ioya_update_month( $month_link, y, m );
			});
		} else {
			jQuery.ajax({
				type: "POST",
				url: $link.attr('href'),
				data: "ioyh=y&yr="+y+"&mth="+m,
				success: function(result){
					var $result = jQuery(result);
					$result
						.hide()
						.css('opacity', 0)
						.data('loaded', true) // indicates we've already loaded the year
						.appendTo($ioya_years_container)
						;

					animate_year( $result, function() {
						var $month_link = find_month_link($result, y, m);
						m = month = $month_link.attr('rel').substring(4, 6); // update the global month var in case it's changed
						return ioya_update_month( $month_link, y, m );
					});
				},
				error: function(result, a) {
					// archives with no posts sometimes through errors, so we need to catch those. Same routine as the success. Could be refactored.
					var $result = jQuery(result.responseText);
					
					if($result.hasClass('.inoveryouryear')) {
						$result
							.hide()
							.css('opacity', 0)
							.data('loaded', true) // indicates we've already loaded the year
							.appendTo($ioya_years_container)
							;
	
						animate_year( $result, function() {
							var $month_link = find_month_link($result, y, m);
							m = month = $month_link.attr('rel').substring(4, 6); // update the global month var in case it's changed
							return ioya_update_month( $month_link, y, m );
						});

					}
				}
			});
		}
		return false;
	}
	
	function find_month_link( $calendar, y, m ) {
		// Switch to correct month
		$month_link = $calendar.find('a[rel="'+ y + month_format(m) +'"]')
		
		// If month doesn't have any posts, find closest month with posts
		if( !$month_link.length ) {
			var month_num = parseInt(m);
			var count = month_num - 1;
			// Go down to Jan then up to Dec starting from current post
			while( !$month_link.length ) {
				if( count <= 0 ) count = month_num + 1;
				if( count > 12 ) break;
				
				$month_link = $calendar.find('a[rel="'+ y + month_format(count) +'"]');
				
				if( count < month_num ) count--;
				else count++;
			}
			// Still don't have month? Not sure why you wouldn't, but just in case, go the month but show no posts message
			if( !$month_link.length )
				$month_link = $calendar.find('span[rel="'+ year + month_format(m) +'"]');
		}
		return $month_link;
	}
	
	function animate_year( $current, callback ) {
		jQuery('.inoveryouryear').not($current).animate({opacity: 0}, 100, function() {
			$current.stop().show().animate({ opacity: 1 }, 300, function() {
				if( typeof (callback) !== 'undefined' ) callback();
			});
		});
	}
	
	function ioya_update_month( $link, year, month ) {
		// Switch to the active month on the slider
		$('#inoveryourarchives ul li').removeClass('selected');
		
		// Fade out all visible months
		$('.inoveryourpostswrapper').fadeTo('fast', 0).hide();
		
		var $ioya_month = get_month_block(year, month);
		
		// Check to see if we've previously loaded the month; if so, just display it, otherwise make the AJAX call
		if ( $ioya_month.length && $ioya_month.data('loaded') == true ) {
			$ioya_month.fadeTo('fast', 1).show();
		} else {
			jQuery.ajax({
				type: "POST",
				url: $link.attr('href'),	// can just pass the entire URL as it is correct
				data: { ioyh: 'm', year: year, month: month },
				success: function(result) {
					var $result = jQuery(result);
					$result
						.fadeTo('fast', 1)
						.show()
						.data('loaded', true) // indicates we've already loaded the page
						.appendTo($ioya_months_container)
						;
					
					// remove grey hover, use grey slider as hover instead
					$('#inoveryouryears ul li a').hover(function() {
						$(this).css('background', 'transparent');
					});
				}
			});
		}
		// Move slider
		var newMonth = $link.parent();
		newMonth.addClass('selected');
		moveSlider( newMonth );
		return false;
	}
	
	// Returns the year block if found, creates one if not
	function get_year_block( year ) {
		return jQuery('#inoveryouryear_'+ year);
	}
	
	// Returns the month block if found
	function get_month_block( year, month ) {
		month = month_format(month);
		return jQuery('#inoveryourmonth_'+ year + month);
	}
	
	function month_format( m ) {
		if( !isNaN(m) ) m = m.toString(); // .length doesn't work on numbers, so convert to string
		if( m.length == 1 ) return '0' + m;
		else return m;
	}

/* ------------ Background Slider Animation ---------------- */

	function setUpSlider() {
		slider = jQuery('<div id="slider"></div>').appendTo('#inoveryourarchives').addClass('slider');		// our sliding thingy
		slider.width( jQuery('#inoveryourarchives ul li.selected').width() 		);
		slider.height( jQuery('#inoveryourarchives ul li.selected').height() 	);
		
		var pos = jQuery('#inoveryourarchives ul li.selected').position();
		slider.css({'position':'absolute', 'top':pos.top+'px', 'left':pos.left+'px', 'z-index':1, 'display':'block', 'margin-left':'1px'}); // margin-left to match margin on li's
		
		// remove grey hover, use grey slider as hover instead
		jQuery('#inoveryouryears ul li a').hover(function() {
			$(this).css('background', 'transparent');
		});
		
		jQuery('#inoveryourarchives ul li a').live('mouseover', function(){
			if ( ! $(this).parent().hasClass('date') ) 	moveSlider( $(this).parent() );
		}).live('mouseout', function(){
			if ( ! $(this).parent().hasClass('date') ) 	moveSlider( $('#inoveryourarchives ul li.selected') );			
		});
	}

	function moveSlider(el) {
		var vis = el.is(":visible");
		
		if ( !vis )
			el.show();  // must be visible to get .position
			
		var pos = el.position(); // where it's going
		
		if ( !vis ) 
			el.hide();
		
		slider.stop();
		
		var animateTo = {
			top: pos.top+'px',
			left: pos.left+'px'
		};
		
		slider.animate(animateTo, "slow");
	}


});