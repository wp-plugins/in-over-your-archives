var ioya_js_loaded = true;

;(function($) {

	$(document).ready(function() {

		var slider, $ioya_years_container, $ioya_months_container, year, month;
		
		year	= parseInt(in_over_your_settings.year);
		month	= parseInt(in_over_your_settings.month);
		
		$ioya_years_container = $('#inoveryouryears');
		$ioya_months_container = $('#inoveryourmonths');
		
		setUpSlider();
		
		$('.inoveryouryear').data('loaded', true);
		$('.inoveryourmonth').data('loaded', true);
		
		// Handle clicks from the calendar thingy
		$('#inoveryourarchives ul li a').live("click", function(){
		
			// Keep track of the year and month clicked. Year and month are stored in the link's rel attribute.
			year = $(this).attr('rel').substring(0, 4);

			if ( $(this).parent().hasClass('date') ) {
				// We're calling a new year
				return ioya_update_year( $(this) );
			} else { 
				// We're calling a month
				month = $(this).attr('rel').substring(4, 6);
				return ioya_update_month( $(this) );
			}
		});
		
		function ioya_update_year( $link ) {

			var $ioya_year = $('#inoveryouryear_'+ year );
			
			// Check to see if we've previously loaded the year; if so, just display it, otherwise make the AJAX call
			if ( $ioya_year.length && $ioya_year.data('loaded') == true ) {
				animate_year( $ioya_year );
			} else {
				$.ajax({
					type: 'POST',
					url: $link.attr('href'),
					data: 'ioyh=y',
					success: function(result){
						var $result = $(result);
						$result
							.hide()
							.fadeOut()
							.data('loaded', true) // indicates we've now loaded the year
							.appendTo($ioya_years_container);

						animate_year( $result );
					},
					error: function(result, a) {
						// archives with no posts sometimes through errors, so we need to catch those. Same routine as the success. Could be refactored.
						var $result = $(result.responseText);
						
						if($result.hasClass('.inoveryouryear')) {
							$result
								.hide()
								.fadeOut()
								.data('loaded', true) // indicates we've already loaded the year
								.appendTo($ioya_years_container);
		
							animate_year( $result );
						}
					}
				});
			}
			return false;
		}
		
		function ioya_update_month( $link ) {
			// Switch to the active month on the slider
			$('#inoveryourarchives ul li').removeClass('selected');
			
			// Fade out all visible months
			$('.inoveryourpostswrapper').fadeOut('fast').hide();

			month = format_month(month);
			var $ioya_month = $('#inoveryourmonth_'+ year + month);

			// Check to see if we've previously loaded the month; if so, just display it, otherwise make the AJAX call
			if ( $ioya_month.length && $ioya_month.data('loaded') == true ) {
				$ioya_month.fadeIn('fast');
			} else {
				var cat = '';
				$.ajax({
					type: "POST",
					url: $link.attr('href'),	// can just pass the entire URL as it is correct
					data: 'ioyh=m&cat='+cat,
					success: function(result) {
						var $result = $(result);
						$result
							.fadeIn('fast')
							.data('loaded', true) // indicates we've already loaded the page
							.appendTo($ioya_months_container);
					}
				});
			}
			// Move slider
			var newMonth = $link.parent();
			newMonth.addClass('selected');
			moveSlider( newMonth );
			return false;
		}

		function find_month_link( $calendar ) {
			// Switch to correct month
			$month_link = $calendar.find('a[rel="'+ year + format_month(month) +'"]')
			
			// If month doesn't have any posts, find closest month with posts
			if( !$month_link.length ) {
				var month_num = parseInt(month);
				var count = month_num - 1;
				// Go down to Jan then up to Dec starting from current post
				while( !$month_link.length ) {
					if( count <= 0 ) count = month_num + 1;
					if( count > 12 ) break;
					
					$month_link = $calendar.find('a[rel="'+ year + format_month(count) +'"]');
					
					if( count < month_num ) count--;
					else count++;
				}
				// Still don't have month? Not sure why you wouldn't, but just in case, go the month but show no posts message
				if( !$month_link.length )
					$month_link = $calendar.find('span:eq('+month+')');
			}
			return $month_link;
		}
		
		function animate_year( $current ) {
			var year = $('.inoveryouryear').not($current);
			year.fadeOut('fast', function() {
				$(this).hide();
				$current.fadeIn('fast');

				var $month_link = find_month_link( $current );
				month = $month_link.attr('rel').substring(4, 6); // update the global month var in case it's changed
				ioya_update_month( $month_link );
			});
		}
		
		function format_month( m ) {
			if( !isNaN(m) ) m = m.toString(); // .length doesn't work on numbers, so convert to string
			if( m.length == 1 ) return '0' + m;
			else return m;
		}

	/* ------------ Background Slider Animation ---------------- */

		function setUpSlider() {
			slider = $('<div></div>').appendTo('#inoveryouryears').addClass('slider');		// our sliding thingy
			slider.width( $('#inoveryourarchives ul li.selected').width() );
			slider.height( $('#inoveryourarchives ul li.selected').height() );
			
			var pos = $('#inoveryourarchives ul li.selected').position();
			slider.css({'position':'absolute', 'top':pos.top+'px', 'left':pos.left+'px', 'z-index':1, 'display':'block', 'margin-left':'1px'}); // margin-left to match margin on li's
			
			// remove grey hover, use grey slider as hover instead
			$('#inoveryouryears ul li a').live('hover', function() {
				$(this).css('background', 'transparent');
			});
			
			$('#inoveryourarchives ul li a')
				.live('mouseover', function(){ if ( ! $(this).parent().hasClass('date') ) moveSlider( $(this).parent() ); })
				.live('mouseout', function(){ if ( ! $(this).parent().hasClass('date') ) moveSlider( $('#inoveryourarchives ul li.selected') ); });

			// if jQuery > 1.4.1
/*			$('#inoveryourarchives ul li a').live({
				'mouseover' : function(){ if ( ! $(this).parent().hasClass('date') ) moveSlider( $(this).parent() );
				'mouseout' : function(){ if ( ! $(this).parent().hasClass('date') ) moveSlider( $('#inoveryourarchives ul li.selected') );
			});
*/
		}

		function moveSlider( el ) {
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
})(jQuery);