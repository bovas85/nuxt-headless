/**
 * Modals JavaScript code.
 */

( function ( $ ) {
	'use strict';

	$( window ).on( 'load', function () {
		// If quick setup box is found, show.
		if ( $( '#smush-quick-setup-dialog' ).length > 0 ) {
			// Show the modal.
			window.SUI.dialogs['smush-quick-setup-dialog'].show();
		}
	} );

	/**
	 * Remove dismissable notices.
	 */
	$( '.sui-wrap' ).on( 'click', '.sui-notice-dismiss', function ( e ) {
		e.preventDefault();
		$( this ).parent().stop().slideUp( 'slow' );
	} );

	/**
	 * Quick Setup - Form Submit
	 */
	$( '#smush-quick-setup-submit' ).on( 'click', function () {
		const self = $( this );

		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: $( '#smush-quick-setup-form' ).serialize(),
			beforeSend: function () {
				// Disable the button.
				self.attr( 'disabled', 'disabled' );

				// Show loader.
				$( '<span class="sui-icon-loader sui-loading"></span>' ).insertAfter( self );
			},
			success: function ( data ) {
				// Enable the button.
				self.removeAttr( 'disabled' );
				// Remove the loader.
				self.parent().find( 'span.spinner' ).remove();

				// Reload the Page.
				location.reload();
			}
		} );
	} );

	/**
	 * Quick Setup - Skip button
	 */
	$( '.smush-skip-setup' ).on( 'click', function () {
		const form = $( 'form#smush-quick-setup-form' );

		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: form.serialize(),
			beforeSend: function () {
				form.find( '.button' ).attr( 'disabled', 'disabled' );
			}
		} );
	} );

}( jQuery ));