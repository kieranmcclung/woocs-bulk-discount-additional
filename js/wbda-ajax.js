jQuery(document).ready(function($) {
	var product_id = $('[name=add-to-cart]').val(),
		$quantity_input = $('.input-text.qty');
	
	$quantity_input.on('change', function(e) {
		var quantity = $(this).val(),
			data = {
				action: 'wbda_update_price',
				product_id: product_id,
				quantity: quantity,
				security: wbda_ajax.security
			};
		
		$.ajax({
			type: 'POST',
			url: wbda_ajax.ajaxurl,
			data: data,
			success: function(data) {
				if ( data !== false )
					$('#wbda-dynamic-price .woocommerce-Price-amount').html(data);
			}
		});
		
	});
});