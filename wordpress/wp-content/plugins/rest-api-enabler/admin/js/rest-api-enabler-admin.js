(function( $ ) {
	'use strict';

	$( document ).ready( function() {

		var $raePostMetaSelect = $( '#rest-api-enabler\\[show_post_meta\\]' );

		// Post Types - toggle REST base setting for enabled post types on init.
		$( '.rest-api-enabler-settings input[type="checkbox"]' ).each( function() {
			raeToggleInput( $( this ) );
		});

		// Post Types - toggle REST base setting for post types on enable (click).
		$( '.rest-api-enabler-settings' ).on( 'click', 'input[type="checkbox"]', function() {
			raeToggleInput( $( this ) );
		});

		// Post Meta - check/uncheck buttons.
		$( '.rae-post-meta-check-buttons' ).on( 'click', 'a.button', function( e ) {
			e.preventDefault();
			raeCheckUncheckBoxes( $( this ) );
		});

		// Post Meta - show post meta checkboxes on init.
		raeTogglePostMetaCheckboxes( $raePostMetaSelect );

		// Post Meta - show post meta checkboxes on enable (change).
		$( '.rest-api-enabler-settings' ).on( 'change', $raePostMetaSelect, function() {
			raeTogglePostMetaCheckboxes( $( this ) );
		});

	});


	// Toggle REST base setting visibility based on post type setting.
	function raeToggleInput( $input ) {

		var $rest_base_input = $input.parents( 'td' ).find( '.rae-rest-base' );

		if ( $input.is( ':checked' ) ) {
			$rest_base_input.removeClass( 'rae-hidden' );
		} else {
			$rest_base_input.addClass( 'rae-hidden' );
		}

	}

	function raeCheckUncheckBoxes( $button ) {

		var $checkboxes = $ ( '.rae-post-meta-checkboxes input[type="checkbox"]' );

		if ( $button.hasClass( 'rae-check-all' ) ) {
			$checkboxes.attr( 'checked', true );
		} else if ( $button.hasClass( 'rae-uncheck-all' ) ) {
			$checkboxes.attr( 'checked', false )
		}

		$button.blur();

	}

	// Toggle REST base setting visibility based on post type setting.
	function raeTogglePostMetaCheckboxes( $select ) {

		var selectValue = $select.find( 'option:selected' ).val(),
			checkboxContainer = $( '.rae-post-meta-checkboxes' );

		if ( ! selectValue || 'none' == selectValue ) {
			checkboxContainer.addClass( 'rae-hidden' );
		} else {
			checkboxContainer.removeClass( 'rae-hidden' );
		}

	}

})( jQuery );
