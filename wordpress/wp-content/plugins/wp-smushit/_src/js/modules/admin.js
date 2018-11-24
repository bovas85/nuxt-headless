import Smush from '../smush/smush';

let remove_element = function ( el, timeout ) {
	if ( typeof timeout === 'undefined' ) {
		timeout = 100;
	}
	el.fadeTo( timeout, 0, function () {
		el.slideUp( timeout, function () {
			el.remove();
		} );
	} );
};

jQuery( function ( $ ) {
	'use strict';

	/**
	 * Remove the quick setup dialog
	 */
	function remove_dialog() {
		$( 'dialog#smush-quick-setup' ).remove();
	}

	// Show the Quick Setup dialog.
	if ( $( '#smush-quick-setup' ).size() > 0 ) {
		/** @var {string} wp_smush_msgs.quick_setup_title */
		WDP.showOverlay( "#smush-quick-setup", {
			title: wp_smush_msgs.quick_setup_title,
			class: 'no-close wp-smush-overlay wp-smush-quick-setup'
		} );
		remove_dialog();
	}

	/** Disable the action links **/
	var disable_links = function ( c_element ) {

		var parent = c_element.parent();
		//reduce parent opacity
		parent.css( {'opacity': '0.5'} );
		//Disable Links
		parent.find( 'a' ).attr( 'disabled', 'disabled' );
	};

	/** Enable the Action Links **/
	var enable_links = function ( c_element ) {

		var parent = c_element.parent();

		//reduce parent opacity
		parent.css( {'opacity': '1'} );
		//Disable Links
		parent.find( 'a' ).removeAttr( 'disabled' );
	};
	/**
	 * Restore image request with a specified action for Media Library / NextGen Gallery
	 * @param e
	 * @param current_button
	 * @param smush_action
	 * @returns {boolean}
	 */
	var process_smush_action = function ( e, current_button, smush_action, action ) {

		//If disabled
		if ( 'disabled' == current_button.attr( 'disabled' ) ) {
			return false;
		}

		e.preventDefault();

		//Remove Error
		$( '.wp-smush-error' ).remove();

		//Hide stats
		$( '.smush-stats-wrapper' ).hide();

		var mode = 'grid';
		if ( 'smush_restore_image' == smush_action ) {
			if ( $( document ).find( 'div.media-modal.wp-core-ui' ).length > 0 ) {
				mode = 'grid';
			} else {
				mode = window.location.search.indexOf( 'item' ) > -1 ? 'grid' : 'list';
			}
		}

		//Get the image ID and nonce
		var params = {
			action: smush_action,
			attachment_id: current_button.data( 'id' ),
			mode: mode,
			_nonce: current_button.data( 'nonce' )
		};

		//Reduce the opacity of stats and disable the click
		disable_links( current_button );

		Smush.progress_bar( current_button, wp_smush_msgs[action], 'show' );

		//Restore the image
		$.post( ajaxurl, params, function ( r ) {

			Smush.progress_bar( current_button, wp_smush_msgs[action], 'hide' );

			//reset all functionality
			enable_links( current_button );

			if ( r.success && 'undefined' != typeof(r.data.button) ) {
				//Replace in immediate parent for nextgen
				if ( 'undefined' != typeof (this.data) && this.data.indexOf( 'nextgen' ) > -1 ) {
					//Show the smush button, and remove stats and restore option
					current_button.parent().html( r.data.button );
				} else {
					//Show the smush button, and remove stats and restore option
					current_button.parents().eq( 1 ).html( r.data.button );
				}

				if ( 'undefined' != typeof (r.data) && 'restore' === action ) {
					Smush.update_image_stats( r.data.new_size );
				}
			} else {
				if ( r.data.message ) {
					//show error
					current_button.parent().append( r.data.message );
				}
			}
		} )
	};

	/**
	 * Validates the Resize Width and Height against the Largest Thumbnail Width and Height
	 *
	 * @param wrapper_div jQuery object for the whole setting row wrapper div
	 * @param width_only Whether to validate only width
	 * @param height_only Validate only Height
	 * @returns {boolean} All Good or not
	 *
	 */
	var validate_resize_settings = function ( wrapper_div, width_only, height_only ) {
		var resize_checkbox = wrapper_div.find( '#wp-smush-resize, #wp-smush-resize-quick-setup' );

		if ( !height_only ) {
			var width_input = wrapper_div.find( '#wp-smush-resize_width, #quick-setup-resize_width' );
			var width_error_note = wrapper_div.find( '.sui-notice-info.wp-smush-update-width' );
		}
		if ( !width_only ) {
			var height_input = wrapper_div.find( '#wp-smush-resize_height, #quick-setup-resize_height' );
			var height_error_note = wrapper_div.find( '.sui-notice-info.wp-smush-update-height' );
		}

		var width_error = false;
		var height_error = false;

		//If resize settings is not enabled, return true
		if ( !resize_checkbox.is( ':checked' ) ) {
			return true;
		}

		//Check if we have localised width and height
		if ( 'undefined' == typeof (wp_smushit_data.resize_sizes) || 'undefined' == typeof (wp_smushit_data.resize_sizes.width) ) {
			//Rely on server validation
			return true;
		}

		//Check for width
		if ( !height_only && 'undefined' != typeof width_input && parseInt( wp_smushit_data.resize_sizes.width ) > parseInt( width_input.val() ) ) {
			width_input.parent().addClass( 'sui-form-field-error' );
			width_error_note.show( 'slow' );
			width_error = true;
		} else {
			//Remove error class
			width_input.parent().removeClass( 'sui-form-field-error' );
			width_error_note.hide();
			if ( height_input.hasClass( 'error' ) ) {
				height_error_note.show( 'slow' );
			}
		}

		//Check for height
		if ( !width_only && 'undefined' != typeof height_input && parseInt( wp_smushit_data.resize_sizes.height ) > parseInt( height_input.val() ) ) {
			height_input.parent().addClass( 'sui-form-field-error' );
			//If we are not showing the width error already
			if ( !width_error ) {
				height_error_note.show( 'slow' );
			}
			height_error = true;
		} else {
			//Remove error class
			height_input.parent().removeClass( 'sui-form-field-error' );
			height_error_note.hide();
			if ( width_input.hasClass( 'error' ) ) {
				width_error_note.show( 'slow' );
			}
		}

		if ( width_error || height_error ) {
			return false;
		}
		return true;

	};

	/**
	 * Update the progress bar width if we have images that needs to be resmushed
	 * @param unsmushed_count
	 * @returns {boolean}
	 */
	var update_progress_bar_resmush = function ( unsmushed_count ) {

		if ( 'undefined' == typeof unsmushed_count ) {
			return false;
		}

		var smushed_count = wp_smushit_data.count_total - unsmushed_count;

		//Update the Progress Bar Width
		// get the progress bar
		var $progress_bar = jQuery( '.bulk-smush-wrapper .wp-smush-progress-inner' );
		if ( $progress_bar.length < 1 ) {
			return;
		}

		var width = ( smushed_count / wp_smushit_data.count_total ) * 100;

		// increase progress
		$progress_bar.css( 'width', width + '%' );
	};

	var run_re_check = function ( button, process_settings ) {

		// Empty the button text and add loader class.
		button.text( '' ).addClass( 'sui-button-onload sui-icon-loader sui-loading' ).blur();

		//Check if type is set in data attributes
		var scan_type = button.data( 'type' );
		scan_type = 'undefined' == typeof scan_type ? 'media' : scan_type;

		//Remove the Skip resmush attribute from button
		$( 'button.wp-smush-all' ).removeAttr( 'data-smush' );

		//remove notices
		var el = $( '.sui-notice-top.sui-notice-success' );
		el.slideUp( 100, function () {
			el.remove();
		} );

		//Disable Bulk smush button and itself
		$( '.wp-smush-all' ).attr( 'disabled', 'disabled' );

		//Hide Settings changed Notice
		$( '.wp-smush-settings-changed' ).hide();

		//Ajax Params
		var params = {
			action: 'scan_for_resmush',
			type: scan_type,
			get_ui: true,
			process_settings: process_settings,
			wp_smush_options_nonce: jQuery( '#wp_smush_options_nonce' ).val()
		};

		//Send ajax request and get ids if any
		$.get( ajaxurl, params, function ( r ) {
			//Check if we have the ids,  initialize the local variable
			if ( 'undefined' != typeof r.data ) {
				//Update Resmush id list
				if ( 'undefined' != typeof r.data.resmush_ids ) {
					wp_smushit_data.resmush = r.data.resmush_ids;

					//Update wp_smushit_data ( Smushed count, Smushed Percent, Image count, Super smush count, resize savings, conversion savings )
					if ( 'undefinied' != typeof wp_smushit_data ) {
						wp_smushit_data.count_smushed = 'undefined' != typeof r.data.count_smushed ? r.data.count_smushed : wp_smushit_data.count_smushed;
						wp_smushit_data.count_supersmushed = 'undefined' != typeof r.data.count_supersmushed ? r.data.count_supersmushed : wp_smushit_data.count_supersmushed;
						wp_smushit_data.count_images = 'undefined' != typeof r.data.count_image ? r.data.count_image : wp_smushit_data.count_images;
						wp_smushit_data.size_before = 'undefined' != typeof r.data.size_before ? r.data.size_before : wp_smushit_data.size_before;
						wp_smushit_data.size_after = 'undefined' != typeof r.data.size_after ? r.data.size_after : wp_smushit_data.size_after;
						wp_smushit_data.savings_resize = 'undefined' != typeof r.data.savings_resize ? r.data.savings_resize : wp_smushit_data.savings_resize;
						wp_smushit_data.savings_conversion = 'undefined' != typeof r.data.savings_conversion ? r.data.savings_conversion : wp_smushit_data.savings_conversion;
						wp_smushit_data.count_resize = 'undefined' != typeof r.data.count_resize ? r.data.count_resize : wp_smushit_data.count_resize;
					}

					if ( 'nextgen' == scan_type ) {
						wp_smushit_data.bytes = parseInt( wp_smushit_data.size_before ) - parseInt( wp_smushit_data.size_after )
					}

					var smush_percent = ( wp_smushit_data.count_smushed / wp_smushit_data.count_total ) * 100;
					smush_percent = WP_Smush.helpers.precise_round( smush_percent, 1 );

					//Update it in stats bar
					$( '.wp-smush-images-percent' ).html( smush_percent );

					//Hide the Existing wrapper
					var notices = $( '.bulk-smush-wrapper .sui-notice' );
					if ( notices.length > 0 ) {
						notices.hide();
						$( '.wp-smush-pagespeed-recommendation' ).hide();
					}
					//remove existing Re-Smush notices
					$( '.wp-smush-resmush-notice' ).remove();

					//Show Bulk wrapper
					$( '.wp-smush-bulk-wrapper' ).show();

					if ( 'undefined' !== typeof r.data.count ) {
						//Update progress bar
						update_progress_bar_resmush( r.data.count );
					}
				}
				//If content is received, Prepend it
				if ( 'undefined' != typeof r.data.content ) {
					$( '.bulk-smush-wrapper .sui-box-body' ).prepend( r.data.content );
				}
				//If we have any notice to show
				if ( 'undefined' != typeof r.data.notice ) {
					$( '.wp-smush-page-header' ).after( r.data.notice );
				}
				//Hide errors
				$( 'div.smush-final-log' ).hide();

				//Hide Super Smush notice if it's enabled in media settings
				if ( 'undefined' != typeof r.data.super_smush && r.data.super_smush ) {
					var enable_lossy = jQuery( '.wp-smush-enable-lossy' );
					if ( enable_lossy.length > 0 ) {
						enable_lossy.remove();
					}
					if ( 'undefined' !== r.data.super_smush_stats ) {
						$( '.super-smush-attachments .wp-smush-stats' ).html( r.data.super_smush_stats );
					}
				}
				Smush.update_stats( scan_type );
			}

		} ).always( function () {

			//Hide the progress bar
			jQuery( '.bulk-smush-wrapper .wp-smush-bulk-progress-bar-wrapper' ).hide();

			// Add check complete status to button.
			button.text( wp_smush_msgs.resmush_complete )
				.removeClass( 'sui-button-onload sui-icon-loader sui-loading' )
				.addClass( 'smush-button-check-success' );

			// Remove success message from button.
			setTimeout( function () {
				button.removeClass( 'smush-button-check-success' )
					.text( wp_smush_msgs.resmush_check );
			}, 2000 );

			$( '.wp-smush-all' ).removeAttr( 'disabled' );

			//If wp-smush-re-check-message is there, remove it
			if ( $( '.wp-smush-re-check-message' ).length ) {
				remove_element( $( '.wp-smush-re-check-message' ) );
			}
		} );
	};

	// Scroll the element to top of the page.
	var goToByScroll = function ( selector ) {
		// Scroll if element found.
		if ( $( selector ).length > 0 ) {
			$( 'html, body' ).animate( {
					scrollTop: $( selector ).offset().top - 100
				}, 'slow'
			);
		}
	};

	var update_cummulative_stats = function ( stats ) {
		//Update Directory Smush Stats
		if ( 'undefined' != typeof ( stats.dir_smush ) ) {
			var stats_human = $( 'li.smush-dir-savings span.wp-smush-stats span.wp-smush-stats-human' );
			var stats_percent = $( 'li.smush-dir-savings span.wp-smush-stats span.wp-smush-stats-percent' );

			// Do not replace if 0 savings.
			if ( stats.dir_smush.bytes > 0 ) {
				// Hide selector.
				$( 'li.smush-dir-savings .wp-smush-stats-label-message' ).hide();
				//Update Savings in bytes
				if ( stats_human.length > 0 ) {
					stats_human.html( stats.dir_smush.human );
				} else {
					var span = '<span class="wp-smush-stats-human">' + stats.dir_smush.bytes + '</span>';
				}

				//Percentage section
				if ( stats.dir_smush.percent > 0 ) {
					// Show size and percentage separator.
					$( 'li.smush-dir-savings span.wp-smush-stats span.wp-smush-stats-sep' ).removeClass( 'sui-hidden' );
					//Update Optimisation percentage
					if ( stats_percent.length > 0 ) {
						stats_percent.html( stats.dir_smush.percent + '%' );
					} else {
						var span = '<span class="wp-smush-stats-percent">' + stats.dir_smush.percent + '%' + '</span>';
					}
				}
			}
		}

		//Update Combined stats
		if ( 'undefined' != typeof ( stats.combined_stats ) && stats.combined_stats.length > 0 ) {
			var c_stats = stats.combined_stats;

			var smush_percent = ( c_stats.smushed / c_stats.total_count ) * 100;
			smush_percent = WP_Smush.helpers.precise_round( smush_percent, 1 );

			//Smushed Percent
			if ( smush_percent ) {
				$( 'div.wp-smush-count-total span.wp-smush-images-percent' ).html( smush_percent );
			}
			//Update Total Attachment Count
			if ( c_stats.total_count ) {
				$( 'span.wp-smush-count-total span.wp-smush-total-optimised' ).html( c_stats.total_count );
			}
			//Update Savings and Percent
			if ( c_stats.savings ) {
				$( 'span.wp-smush-savings span.wp-smush-stats-human' ).html( c_stats.savings );
			}
			if ( c_stats.percent ) {
				$( 'span.wp-smush-savings span.wp-smush-stats-percent' ).html( c_stats.percent );
			}
		}
	};

	//Remove span tag from URL
	function removeSpan( url ) {
		var url = url.slice( url.indexOf( '?' ) + 1 ).split( '&' );
		for ( var i = 0; i < url.length; i++ ) {
			var urlparam = decodeURI( url[i] ).split( /=(.+)/ )[1];
			return urlparam.replace( /<(?:.|\n)*?>/gm, '' );
		}
	}

	/**
	 * Handle the Smush Stats link click
	 */
	$( 'body' ).on( 'click', 'a.smush-stats-details', function ( e ) {

		//If disabled
		if ( 'disabled' == $( this ).attr( 'disabled' ) ) {
			return false;
		}

		// prevent the default action
		e.preventDefault();
		//Replace the `+` with a `-`
		var slide_symbol = $( this ).find( '.stats-toggle' );
		$( this ).parents().eq( 1 ).find( '.smush-stats-wrapper' ).slideToggle();
		slide_symbol.text( slide_symbol.text() == '+' ? '-' : '+' );


	} );

	/** Handle smush button click **/
	$( 'body' ).on( 'click', '.wp-smush-send:not(.wp-smush-resmush)', function ( e ) {
		// prevent the default action
		e.preventDefault();
		new Smush( $( this ), false );
	} );

	/** Handle NextGen Gallery smush button click **/
	$( 'body' ).on( 'click', '.wp-smush-nextgen-send', function ( e ) {
		// prevent the default action
		e.preventDefault();
		new Smush( $( this ), false, 'nextgen' );
	} );

	/** Handle NextGen Gallery Bulk smush button click **/
	$( 'body' ).on( 'click', '.wp-smush-nextgen-bulk', function ( e ) {
		// prevent the default action
		e.preventDefault();

		//Check for ids, if there is none (Unsmushed or lossless), don't call smush function
		if ( 'undefined' === typeof wp_smushit_data ||
			( wp_smushit_data.unsmushed.length === 0 && wp_smushit_data.resmush.length === 0 )
		) {
			return false;
		}

		jQuery( '.wp-smush-all, .wp-smush-scan' ).attr( 'disabled', 'disabled' );
		$( ".wp-smush-notice.wp-smush-remaining" ).hide();
		new Smush( $( this ), true, 'nextgen' );
	} );

	/** Restore: Media Library **/
	$( 'body' ).on( 'click', '.wp-smush-action.wp-smush-restore', function ( e ) {
		const current_button = $( this );
		process_smush_action( e, current_button, 'smush_restore_image', 'restore' );
		// Change the class oa parent div ( Level 2 )
		const parent = current_button.parents().eq( 1 );
		if ( parent.hasClass( 'smushed' ) ) {
			parent.removeClass( 'smushed' ).addClass( 'unsmushed' );
		}
	} );

	/** Resmush: Media Library **/
	$( 'body' ).on( 'click', '.wp-smush-action.wp-smush-resmush', function ( e ) {
		process_smush_action( e, $( this ), 'smush_resmush_image', 'smushing' );
	} );

	/** Restore: NextGen Gallery **/
	$( 'body' ).on( 'click', '.wp-smush-action.wp-smush-nextgen-restore', function ( e ) {
		process_smush_action( e, $( this ), 'smush_restore_nextgen_image', 'restore' );
	} );

	/** Resmush: NextGen Gallery **/
	$( 'body' ).on( 'click', '.wp-smush-action.wp-smush-nextgen-resmush', function ( e ) {
		process_smush_action( e, $( this ), 'smush_resmush_nextgen_image', 'smushing' );
	} );

	//Scan For resmushing images
	$( '.wp-smush-scan' ).on( 'click', function ( e ) {
		e.preventDefault();

		//Run the Re-check
		run_re_check( $( this ), false );
	} );

	//Dismiss Welcome notice
	//@todo: Use it for popup
	$( '#wp-smush-welcome-box .smush-dismiss-welcome' ).on( 'click', function ( e ) {
		e.preventDefault();
		var $el = $( this ).parents().eq( 1 );
		remove_element( $el );

		//Send a ajax request to save the dismissed notice option
		var param = {
			action: 'dismiss_welcome_notice'
		};
		$.post( ajaxurl, param );
	} );

	//Remove Notice
	$( 'body' ).on( 'click', '.wp-smush-notice .icon-fi-close', function ( e ) {
		e.preventDefault();
		var $el = $( this ).parent();
		remove_element( $el );
	} );

	//On Click Update Settings. Check for change in settings
	$( 'input#wp-smush-save-settings' ).on( 'click', function ( e ) {
		e.preventDefault();

		var setting_type = '';
		var setting_input = $( 'input[name="setting-type"]' );
		//Check if setting type is set in the form
		if ( setting_input.length > 0 ) {
			setting_type = setting_input.val();
		}

		//Show the spinner
		var self = $( this );
		self.parent().find( 'span.sui-icon-loader.sui-loading' ).removeClass( 'sui-hidden' );

		//Save settings if in network admin
		if ( '' != setting_type && 'network' == setting_type ) {
			//Ajax param
			var param = {
				action: 'save_settings',
				nonce: $( '#wp_smush_options_nonce' ).val()
			};

			param = jQuery.param( param ) + '&' + jQuery( 'form#wp-smush-settings-form' ).serialize();

			//Send ajax, Update Settings, And Check For resmush
			jQuery.post( ajaxurl, param ).done( function () {
				jQuery( 'form#wp-smush-settings-form' ).submit();
				return true;
			} );
		} else {
			//Check for all the settings, and scan for resmush
			var wrapper_div = self.parents().eq( 1 );

			//Get all the main settings
			var strip_exif = document.getElementById( "wp-smush-strip_exif" );
			var super_smush = document.getElementById( "wp-smush-lossy" );
			var smush_original = document.getElementById( "wp-smush-original" );
			var resize_images = document.getElementById( "wp-smush-resize" );
			var smush_pngjpg = document.getElementById( "wp-smush-png_to_jpg" );

			var update_button_txt = true;

			$( '.wp-smush-hex-notice' ).hide();

			//If Preserve Exif is Checked, and all other settings are off, just save the settings
			if ( ( strip_exif === null || !strip_exif.checked )
				&& ( super_smush === null || !super_smush.checked )
				&& ( smush_original === null || !smush_original.checked )
				&& ( resize_images === null || !resize_images.checked )
				&& ( smush_pngjpg === null || !smush_pngjpg.checked )
			) {
				update_button_txt = false;
			}

			//Update text
			self.attr( 'disabled', 'disabled' ).addClass( 'button-grey' );

			if ( update_button_txt ) {
				self.val( wp_smush_msgs.checking )
			}

			//Check if type is set in data attributes
			var scan_type = self.data( 'type' );
			scan_type = 'undefined' == typeof scan_type ? 'media' : scan_type;

			//Ajax param
			var param = {
				action: 'scan_for_resmush',
				wp_smush_options_nonce: jQuery( '#wp_smush_options_nonce' ).val(),
				scan_type: scan_type
			};

			param = jQuery.param( param ) + '&' + jQuery( 'form#wp-smush-settings-form' ).serialize();

			//Send ajax, Update Settings, And Check For resmush
			jQuery.post( ajaxurl, param ).done( function () {
				jQuery( 'form#wp-smush-settings-form' ).submit();
				return true;
			} );
		}
	} );

	// On re-Smush click.
	$( 'body' ).on( 'click', '.wp-smush-skip-resmush', function ( e ) {
		e.preventDefault();

		const self = jQuery( this ),
			  container = self.parents().eq( 1 ),
			  el = self.parent();

		// Remove Parent div.
		remove_element( el );

		// Remove Settings Notice.
		$( '.sui-notice-top.sui-notice-success' ).remove();

		// Set button attribute to skip re-smush ids.
		container.find( '.wp-smush-all' ).attr( 'data-smush', 'skip_resmush' );

		// Update Smushed count.
		wp_smushit_data.count_smushed = parseInt( wp_smushit_data.count_smushed ) + wp_smushit_data.resmush.length;
		wp_smushit_data.count_supersmushed = parseInt( wp_smushit_data.count_supersmushed ) + wp_smushit_data.resmush.length;

		// Update stats.
		if ( wp_smushit_data.count_smushed === wp_smushit_data.count_total ) {
			// Show all done notice.
			$( '.wp-smush-notice.wp-smush-all-done, .wp-smush-pagespeed-recommendation' ).show();

			// Hide Smush button.
			$( '.wp-smush-bulk-wrapper ' ).hide()
		}

		// Remove re-Smush notice.
		$( '.wp-smush-resmush-notice' ).remove();

		let type = $( '.wp-smush-scan' ).data( 'type' );
		type = 'undefined' === typeof type ? 'media' : type;

		const smushed_count = 'undefined' !== typeof wp_smushit_data.count_smushed ? wp_smushit_data.count_smushed : 0;

		let smush_percent = ( smushed_count / wp_smushit_data.count_total ) * 100;
		smush_percent = WP_Smush.helpers.precise_round( smush_percent, 1 );

		$( '.wp-smush-images-percent' ).html( smush_percent );

		// Update the progress bar width. Get the progress bar.
		const progress_bar = jQuery( '.bulk-smush-wrapper .wp-smush-progress-inner' );
		if ( progress_bar.length < 1 ) {
			return;
		}

		// Increase progress.
		progress_bar.css( 'width', smush_percent + '%' );

		// Show the default bulk smush notice.
		$( '.wp-smush-bulk-wrapper' ).show();
		$( '.wp-smush-bulk-wrapper .sui-notice' ).show();

		const params = {
			action: 'delete_resmush_list',
			type: type
		};

		//Delete resmush list, @todo: update stats from the ajax response
		$.post( ajaxurl, params, function ( res ) {
			// Remove the whole li element on success
			if ( res.success && 'undefined' !== typeof res.data.stats ) {
				const stats = res.data.stats;
				// Update wp_smushit_data ( Smushed count, Smushed Percent, Image count, Super smush count, resize savings, conversion savings )
				if ( 'undefinied' != typeof wp_smushit_data ) {
					wp_smushit_data.count_images = 'undefined' !== typeof stats.count_images ? parseInt( wp_smushit_data.count_images ) + stats.count_images : wp_smushit_data.count_images;
					wp_smushit_data.size_before = 'undefined' !== typeof stats.size_before ? parseInt( wp_smushit_data.size_before ) + stats.size_before : wp_smushit_data.size_before;
					wp_smushit_data.size_after = 'undefined' !== typeof stats.size_after ? parseInt( wp_smushit_data.size_after ) + stats.size_after : wp_smushit_data.size_after;
					wp_smushit_data.savings_resize = 'undefined' !== typeof stats.savings_resize ? parseInt( wp_smushit_data.savings_resize ) + stats.savings_resize : wp_smushit_data.savings_resize;
					wp_smushit_data.savings_conversion = 'undefined' !== typeof stats.savings_conversion ? parseInt( wp_smushit_data.savings_conversion ) + stats.savings_conversion : wp_smushit_data.savings_conversion;

					// Add directory smush stats.
					if ( 'undefined' !== typeof ( wp_smushit_data.savings_dir_smush ) && 'undefined' !== typeof ( wp_smushit_data.savings_dir_smush.orig_size ) ) {
						wp_smushit_data.size_before = 'undefined' !== typeof wp_smushit_data.savings_dir_smush ? parseInt( wp_smushit_data.size_before ) + parseInt( wp_smushit_data.savings_dir_smush.orig_size ) : wp_smushit_data.size_before;
						wp_smushit_data.size_after = 'undefined' !== typeof wp_smushit_data.savings_dir_smush ? parseInt( wp_smushit_data.size_after ) + parseInt( wp_smushit_data.savings_dir_smush.image_size ) : wp_smushit_data.size_after;
					}

					wp_smushit_data.count_resize = 'undefined' !== typeof stats.count_resize ? parseInt( wp_smushit_data.count_resize ) + stats.count_resize : wp_smushit_data.count_resize;
				}
				// Smush notice.
				const remainingCountDiv = $( '.bulk-smush-wrapper .wp-smush-remaining-count' );
				if ( remainingCountDiv.length && 'undefined' !== typeof wp_smushit_data.unsmushed ) {
					remainingCountDiv.html( wp_smushit_data.unsmushed.length );
				}

				// If no images left, hide the notice, show all success notice.
				if ( 'undefined' !== typeof wp_smushit_data.unsmushed || wp_smushit_data.unsmushed.length === 0 ) {
					$( '.wp-smush-bulk-wrapper .sui-notice' ).hide();
					$( '.sui-notice-success.wp-smush-all-done' ).show();
				}

				Smush.update_stats();
			}
		} );
	} );

	/**
	 * Enable resize in settings and scroll.
	 */
	var scroll_and_enable_resize = function () {
		// Enable resize, show resize settings.
		$( '#wp-smush-resize' ).prop( 'checked', true ).focus();
		$( 'div.wp-smush-resize-settings-wrap' ).show();

		// Scroll down to settings area.
		goToByScroll( "#column-wp-smush-resize" );
	}

	/**
	 * Enable super smush in settings and scroll.
	 */
	var scroll_and_enable_lossy = function () {
		// Enable super smush.
		$( '#wp-smush-lossy' ).prop( 'checked', true ).focus();

		// Scroll down to settings area.
		goToByScroll( "#column-wp-smush-lossy" );
	}

	// Enable super smush on clicking link from stats area.
	$( 'a.wp-smush-lossy-enable' ).on( 'click', function ( e ) {
		e.preventDefault();

		scroll_and_enable_lossy();
	} );

	// Enable resize on clicking link from stats area.
	$( '.wp-smush-resize-enable' ).on( 'click', function ( e ) {
		e.preventDefault();

		scroll_and_enable_resize();
	} );

	// If settings string is found in url, enable and scroll.
	if ( window.location.hash ) {
		var setting_hash = window.location.hash.substring( 1 );
		// Enable and scroll to resize settings.
		if ( 'enable-resize' === setting_hash ) {
			scroll_and_enable_resize();
		} else if ( 'enable-lossy' === setting_hash ) {
			// Enable and scroll to lossy settings.
			scroll_and_enable_lossy();
		}
	}

	//Trigger Bulk
	$( 'body' ).on( 'click', '.wp-smush-trigger-bulk', function ( e ) {
		e.preventDefault();
		//Induce Setting button save click
		$( 'button.wp-smush-all' ).click();
		$( 'span.sui-notice-dismiss' ).click();
	} );

	//Allow the checkboxes to be Keyboard Accessible
	$( '.wp-smush-setting-row .toggle-checkbox' ).focus( function () {
		//If Space is pressed
		$( this ).keypress( function ( e ) {
			if ( e.keyCode == 32 ) {
				e.preventDefault();
				$( this ).find( '.toggle-checkbox' ).click();
			}
		} );
	} );

	// Re-Validate Resize Width And Height.
	$( 'body' ).on( 'blur', '.wp-smush-resize-input', function () {

		var self = $( this );

		var wrapper_div = self.parents().eq( 4 );

		// Initiate the check.
		validate_resize_settings( wrapper_div, false, false ); // run the validation.
	} );

	// Handle Resize Checkbox toggle, to show/hide width, height settings.
	$( 'body' ).on( 'click', '#wp-smush-resize, #wp-smush-resize-quick-setup', function () {
		var self = $( this );
		var settings_wrap = $( '.wp-smush-resize-settings-wrap' );

		if ( self.is( ':checked' ) ) {
			settings_wrap.show();
		} else {
			settings_wrap.hide();
		}
	} );

	// Handle Automatic Smush Checkbox toggle, to show/hide image size settings.
	$( 'body' ).on( 'click', '#wp-smush-auto', function () {
		var self = $( this );
		var settings_wrap = $( '.wp-smush-image-size-list' );

		if ( self.is( ':checked' ) ) {
			settings_wrap.show();
		} else {
			settings_wrap.hide();
		}
	} );

	// Handle auto detect checkbox toggle, to show/hide highlighting notice.
	$( 'body' ).on( 'click', '#wp-smush-detection', function () {
		var self = $( this );
		var notice_wrap = $( '.smush-highlighting-notice' );
		var warning_wrap = $( '.smush-highlighting-warning' );

		// Setting enabled.
		if ( self.is( ':checked' ) ) {
			// Highlighting is already active and setting not saved.
			if ( notice_wrap.length > 0 ) {
				notice_wrap.show();
			} else {
				warning_wrap.show();
			}
		} else {
			notice_wrap.hide();
			warning_wrap.hide();
		}
	} );

	// Handle PNG to JPG Checkbox toggle, to show/hide Transparent image conversion settings.
	$( '#wp-smush-png_to_jpg' ).click( function () {
		var self = $( this );
		var settings_wrap = $( '.wp-smush-png_to_jpg-wrap' );

		if ( self.is( ':checked' ) ) {
			settings_wrap.show();
		} else {
			settings_wrap.hide();
		}
	} );

	//Handle, Change event in Enable Networkwide settings
	$( '#wp-smush-networkwide' ).on( 'click', function ( e ) {
		if ( $( this ).is( ':checked' ) ) {
			$( '.network-settings-wrapper' ).show();
			$( '.sui-vertical-tabs li' ).not( '.smush-bulk' ).each( function ( n ) {
				$( this ).removeClass( 'sui-hidden' );
			} );
		} else {
			$( '.network-settings-wrapper' ).hide();
			$( '.sui-vertical-tabs li' ).not( '.smush-bulk' ).each( function ( n ) {
				$( this ).addClass( 'sui-hidden' );
			} );
		}
	} );

	//Handle Re-check button functionality
	$( "#wp-smush-revalidate-member" ).on( 'click', function ( e ) {
		e.preventDefault();
		//Ajax Params
		var params = {
			action: 'smush_show_warning',
		};
		var link = $( this );
		var parent = link.parents().eq( 1 );
		parent.addClass( 'loading-notice' );
		$.get( ajaxurl, params, function ( r ) {
			//remove the warning
			parent.removeClass( 'loading-notice' ).addClass( "loaded-notice" );
			if ( 0 == r ) {
				parent.attr( 'data-message', wp_smush_msgs.membership_valid );
				remove_element( parent, 1000 );
			} else {
				parent.attr( 'data-message', wp_smush_msgs.membership_invalid );
				setTimeout( function remove_loader() {
					parent.removeClass( 'loaded-notice' );
				}, 1000 )
			}
		} );
	} );

	//Initiate Re-check if the variable is set
	if ( 'undefined' != typeof (wp_smush_run_re_check) && 1 == wp_smush_run_re_check && $( '.wp-smush-scan' ).length > 0 ) {
		//Run the Re-check
		run_re_check( $( '.wp-smush-scan' ), false );
	}

	if ( $( 'li.smush-dir-savings' ).length > 0 ) {
		// Update Directory Smush, as soon as the page loads.
		var stats_param = {
			action: 'get_dir_smush_stats'
		};
		$.get( ajaxurl, stats_param, function ( r ) {

			//Hide the spinner
			$( 'li.smush-dir-savings .sui-icon-loader' ).hide();

			//If there are no errors, and we have a message to display
			if ( !r.success && 'undefined' != typeof ( r.data.message ) ) {
				$( 'div.wp-smush-scan-result div.content' ).prepend( r.data.message );
				return;
			}

			//If there is no value in r
			if ( 'undefined' == typeof ( r.data) || 'undefined' == typeof ( r.data.dir_smush ) ) {
				//Append the text
				$( 'li.smush-dir-savings span.wp-smush-stats' ).append( wp_smush_msgs.ajax_error );
				$( 'li.smush-dir-savings span.wp-smush-stats span' ).hide();

			} else {
				//Update the stats
				update_cummulative_stats( r.data );
			}

		} );
	}
	//Close Directory smush modal, if pressed esc
	$( document ).keyup( function ( e ) {
		if ( e.keyCode === 27 ) {
			var modal = $( 'div.dev-overlay.wp-smush-list-dialog, div.dev-overlay.wp-smush-get-pro' );
			//If the Directory dialog is not visible
			if ( !modal.is( ':visible' ) ) {
				return;
			}
			modal.find( 'div.close' ).click();

		}
	} );

	//Dismiss Smush recommendation
	$( 'span.dismiss-recommendation' ).on( 'click', function ( e ) {
		e.preventDefault();
		var parent = $( this ).parent();
		//remove div and save preference in db
		parent.hide( 'slow', function () {
			parent.remove();
		} );
		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				'action': 'hide_pagespeed_suggestion'
			}
		} );
	} )

	//Remove API message
	$( 'div.wp-smush-api-message i.icon-fi-close' ).on( 'click', function ( e ) {
		e.preventDefault();
		var parent = $( this ).parent();
		//remove div and save preference in db
		parent.hide( 'slow', function () {
			parent.remove();
		} );
		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				'action': 'hide_api_message'
			}
		} );
	} );

} );
