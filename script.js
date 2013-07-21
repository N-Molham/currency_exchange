(function(window){
	// jQuery document ready
	jQuery( function($) {
		// results fields objects
		var $fields = $('.results input');
		// listen for keyup and change events on amount field
		$('#amount').bind('keyup change', function(e) {
			// the amount
			var amount = parseFloat(e.target.value);
			// if not a number make it 1
			if( isNaN(amount) ) {
				amount = 1;
			}
			// loop results 
			$fields.each( function(index, element) {
				var $this = $(this),
					result_amount = 0;

				if( element.dataset ) {
					// JS modern browsers way
					result_amount = amount * prices[element.dataset.cur][element.dataset.method];
				} else if( $this.data ) {
					// jQuery modern way
					result_amount = amount * prices[$this.data('cur')][$this.data('method')];
				} else {
					// jQuery old way
					result_amount = amount * prices[$this.attr('data-cur')][$this.attr('data-method')];
				}
				// display rounded value
				$this.val(round_number(result_amount));
			});
		}).trigger('change'); // trigger change event on page load
	});
	
	// round result number to 4 decimals
	window.round_number = function( num ) {
		return Math.round(num * 10000) / 10000;
	};
	
	// console log if found
	window.trace = function( any ) {
		if( window.console && console.log ) {
			console.log(any);
		}
	};
})(window); // self-executable anonymous function