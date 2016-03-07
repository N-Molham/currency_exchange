(function ( env, $ ) {
	// jQuery document ready
	$( function () {
		// currencies' titles
		var $titles = $( '.currency-title' );

		// results fields objects
		var $fields = $( '.results input' ).on( 'nbe.calcResult', function ( e, amount ) {
			var $this         = $( this ),
			    result_amount = 0,
			    element       = e.currentTarget;

			if ( element.dataset ) {
				// JS modern browsers way
				result_amount = amount * prices[ element.dataset.cur ][ element.dataset.method ];
			} else if ( $this.data ) {
				// jQuery modern way
				result_amount = amount * prices[ $this.data( 'cur' ) ][ $this.data( 'method' ) ];
			} else {
				// jQuery old way
				result_amount = amount * prices[ $this.attr( 'data-cur' ) ][ $this.attr( 'data-method' ) ];
			}

			// display rounded value
			$this.val( round_number( result_amount ) + ' EGP' );
		} );

		// listen for keyup and change events on amount field
		$( '#amount' ).on( 'keyup change nbe-change', function ( e ) {
			// the amount
			var amount = parseFloat( e.target.value );

			// if not a number make it 1
			if ( isNaN( amount ) ) {
				amount = 1;
			}

			// update title
			$titles.each( function () {
				var $this = $( this );

				$this.find( 'span' ).html( amount + ' ' + prices[ $this.data( 'cur' ) ].code );
			} );

			// update result
			$fields.trigger( 'nbe.calcResult', [ amount ] );
		} ).trigger( 'nbe-change' ); // trigger change event on page load
	} );

	// round result number to 4 decimals
	env.round_number = function ( num ) {
		return Math.round( num * 10000 ) / 10000;
	};

	// console log if found
	env.trace = function ( any ) {
		if ( env.console && console.log ) {
			console.log( any );
		}
	};
})( window, jQuery ); // self-executable anonymous function