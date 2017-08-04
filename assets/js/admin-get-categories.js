jQuery( document ).ready( function( $ ) {

	var doing_get_categories = false;

	$( 'body' ).on( 'click', '#fairfax-update-categories', function( e ) {

		e.preventDefault();

		var $this = $( this );

		$this.blur();

		if ( true === doing_get_categories ) {
			return;
		}

		doing_get_categories = true;

		$this.addClass( 'disabled' );
		$this.html( 'Working&hellip;' );

		$( '.fairfax-update-categories-console' ).empty();
		$( '.fairfax-update-categories-console' ).hide();
		$( '.fairfax-update-categories-console' ).removeClass( 'fairfax-error fairfax-success' );

		var ajax_data = {
			action : 'fairfax_sync_categories',
			nonce  : fairfax.nonce,
		}

		$.ajax( {
			url      : fairfax.ajax_url,
			method   : 'POST',
			dataType : 'json',
			data     : ajax_data,
		} )
		.done( function( response, textStatus, jqXHR ) {

			// AJAX request completed but returned a malformed response.
			if ( 'undefined' === typeof response.success ) {
				$( '.fairfax-update-categories-console' ).html( 'An unknown error occurred, please try again later.' );
				$( '.fairfax-update-categories-console' ).addClass( 'fairfax-error' );
				$( '.fairfax-update-categories-console' ).show();
				return;
			}

			// AJAX request completed but return a failed response.
			if ( false === response.success ) {
				$( '.fairfax-update-categories-console' ).html( response.data.msg );
				$( '.fairfax-update-categories-console' ).addClass( 'fairfax-error' );
				$( '.fairfax-update-categories-console' ).show();
				return;
			}

			var success_message;

			if ( 0 === response.data.created && 0 === response.data.updated ) {
				success_message = 'Categories are up-to-date.';
			} else {
				success_message = 'Categories successfully synced. ' + response.data.created + ' created and ' + response.data.updated + ' updated.';
			}

			// Success.
			$( '.fairfax-update-categories-console' ).html( 'Success! ' + success_message );
			$( '.fairfax-update-categories-console' ).addClass( 'fairfax-success' );
			$( '.fairfax-update-categories-console' ).show();
		} )
		.fail( function( jqXHR, textStatus, errorThrown ) {
			$( '.fairfax-update-categories-console' ).html( 'An unknown error occurred, please try again later.' );
			$( '.fairfax-update-categories-console' ).addClass( 'fairfax-error' );
			$( '.fairfax-update-categories-console' ).show();
		} )
		.always( function() {
			doing_get_categories = false;
			$this.removeClass( 'disabled' );
			$this.html( 'Update categories now' );
		} );
	} );

} );
