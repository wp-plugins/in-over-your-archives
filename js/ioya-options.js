jQuery(document).ready(function() {
	// Add colour picker to colour inputs
	jQuery('input.colour')
		.keyup(function() {
			ioya_set_color(this.value, this);
		})				
		.ColorPicker({
			onBeforeShow: function () {
				jQuery(this).ColorPickerSetColor(this.value);
			},
			onShow: function (cp) {
				jQuery(cp).fadeIn(500);
				return false;
			},
			onHide: function (cp) {
				jQuery(cp).fadeOut(500);
				return false;
			},
			onChange: function (hsb, hex, rgb) {
				ioya_set_color (hex, jQuery(this).data('colorpicker').el);
			},
			onSubmit: function(hsb, hex, rgb, el) {
				ioya_set_color (hex, el);
				jQuery(el).ColorPickerHide();
			}
			
		});
});

ioya_set_color = function( color, input ) {
	if(color[0] != '#')
		color = '#' + color;
	jQuery(input).val(color);
	jQuery(input).next().css('background-color', color);
}