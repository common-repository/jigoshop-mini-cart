jQuery(document).ready(function($) {
	
	var minicart = $('.jigoshop-mini-cart');

	minicart.each(function() {

		var current_cart = $(this);

		$(this).on( 'click', '.mini-cart-trigger', function() {
		
			current_cart.find('.cart-content').slideToggle('slow');

		});
	})

});