jQuery(document).ready(function(e) {

	jQuery('.swr-stars-form .swr-star').on('click', function(e) {
		
		jQuery(this).prev().trigger('click');
		
		jQuery('.swr-stars-form .swr-star').removeClass('active');
		jQuery(this).addClass('active');
		jQuery(this).nextAll('.swr-star').addClass('active');

	});

});