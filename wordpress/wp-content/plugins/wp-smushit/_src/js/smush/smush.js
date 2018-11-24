/**
 * Smush class.
 *
 * @since 2.9.0  Moved from admin.js into a dedicated ES6 class.
 */

class Smush {

	/**
	 * Class constructor.
	 *
	 * @param {object}  button  Button object that made the call.
	 * @param {boolean} bulk    Bulk smush or not.
	 * @param {string}  type    Accepts: 'nextgen', 'media'.
	 */
	constructor( button, bulk, type = 'media' ) {
		// TODO: errors will reset after bulk smush limit is reached and user clicks continue. Might be
		this.errors  = [];
		// Smushed and total we take from the progress bar... I don't like this :-(
		const progressBar = jQuery( '.bulk-smush-wrapper .sui-progress-state-text' );
		this.smushed = parseInt( progressBar.find( 'span:first-child' ).html() );
		this.total = parseInt( progressBar.find( 'span:last-child' ).html() );

		//If smush attribute is not defined, Need not skip re-Smush IDs.
		this.skip_resmush = ! ( 'undefined' === typeof button.data( 'smush' ) || ! button.data( 'smush' ) );

		this.button          = jQuery( button[0] );
		this.is_bulk         = typeof bulk ? bulk : false;
		this.url             = ajaxurl;
		this.log             = jQuery( '.smush-final-log' );
		this.deferred        = jQuery.Deferred();
		this.deferred.errors = [];

		const ids = 0 < wp_smushit_data.resmush.length && ! this.skip_resmush ? ( wp_smushit_data.unsmushed.length > 0 ? wp_smushit_data.resmush.concat( wp_smushit_data.unsmushed ) : wp_smushit_data.resmush ) : wp_smushit_data.unsmushed;
		if ( 'object' === typeof ids ) {
			// If button has re-Smush class, and we do have ids that needs to re-Smushed, put them in the list.
			this.ids = ids.filter( function ( itm, i, a ) {
				return i === a.indexOf( itm );
			} );
		} else {
			this.ids = ids;
		}

		this.is_bulk_resmush = 0 < wp_smushit_data.resmush.length && ! this.skip_resmush;

		this.status = this.button.parent().find( '.smush-status' );

		// Added for NextGen support.
		this.smush_type         = type;
		this.single_ajax_suffix = 'nextgen' === this.smush_type ? 'smush_manual_nextgen' : 'wp_smushit_manual';
		this.bulk_ajax_suffix   = 'nextgen' === this.smush_type ? 'wp_smushit_nextgen_bulk' : 'wp_smushit_bulk';
		this.url = this.is_bulk ? Smush.smushAddParams( this.url, { action: this.bulk_ajax_suffix } ) : Smush.smushAddParams( this.url, { action: this.single_ajax_suffix } );

		this.start();
		this.run();
		this.bind_deferred_events();

		// Handle cancel ajax.
		this.cancel_ajax();

		return this.deferred;
	}

	/**
	 * Add params to the URL.
	 *
	 * @param {string} url   URL to add the params to.
	 * @param {object} data  Object with params.
	 * @returns {*}
	 */
	static smushAddParams( url, data ) {
		if ( ! jQuery.isEmptyObject( data ) ) {
			url += ( url.indexOf( '?' ) >= 0 ? '&' : '?' ) + jQuery.param( data );
		}

		return url;
	}

	/**
	 * Check membership validity.
	 *
	 * @param data
	 * @param {int} data.show_warning
	 */
	static membership_validity( data ) {
		const member_validity_notice = jQuery( '#wp-smush-invalid-member' );

		// Check for membership warning.
		if ( 'undefined' !== typeof ( data ) && 'undefined' !== typeof ( data.show_warning ) && member_validity_notice.length > 0 ) {
			if ( data.show_warning ) {
				member_validity_notice.show();
			} else {
				member_validity_notice.hide();
			}
		}
	};

	/**
	 * Send Ajax request for Smushing the image.
	 *
	 * @param {boolean} is_bulk_resmush
	 * @param {int}     id
	 * @param {string}  send_url
	 * @param {string}  nonce
	 * @returns {*|jQuery.promise|void}
	 */
	static ajax( is_bulk_resmush, id, send_url, nonce ) {
		const param = jQuery.param({
			is_bulk_resmush: is_bulk_resmush,
			attachment_id: id,
			_nonce: nonce
		});

		return jQuery.ajax( {
			type: 'GET',
			data: param,
			url: send_url,
			/** @var {array} wp_smushit_data */
			timeout: wp_smushit_data.timeout,
			dataType: 'json'
		} );
	};

	/**
	 * Show loader in button for single and bulk Smush.
	 */
	start() {
		this.button.attr( 'disabled', 'disabled' );
		this.button.addClass( 'wp-smush-started' );

		this.bulk_start();
		this.single_start();
	};

	/**
	 * Start bulk Smush.
	 */
	bulk_start() {
		if ( ! this.is_bulk ) return;

		// Hide the bulk div.
		jQuery( '.wp-smush-bulk-wrapper' ).hide();

		// Remove any global notices if there.
		jQuery( '.sui-notice-top' ).remove();

		// Hide the bulk limit message.
		jQuery( '.wp-smush-bulk-progress-bar-wrapper .sui-notice-warning' ).hide();

		// Hide parent wrapper, if there are no other messages.
		if ( 0 >= jQuery( 'div.smush-final-log .smush-bulk-error-row' ).length ) {
			jQuery( 'div.smush-final-log' ).hide();
		}

		// Show the progress bar.
		jQuery( '.bulk-smush-wrapper .wp-smush-bulk-progress-bar-wrapper' ).show();
	};

	/**
	 * Start single image Smush.
	 */
	single_start() {
		if ( this.is_bulk ) return;
		this.show_loader();
		this.status.removeClass( 'error' );
	};

	/**
	 * Enable button.
	 */
	enable_button() {
		this.button.prop( 'disabled', false );
		// For bulk process, enable other buttons.
		jQuery( 'button.wp-smush-all' ).removeAttr( 'disabled' );
		jQuery( 'button.wp-smush-scan, a.wp-smush-lossy-enable, button.wp-smush-resize-enable, input#wp-smush-save-settings' ).removeAttr( 'disabled' );
	};

	/**
	 * Show loader.
	 *
	 * @var {string} wp_smush_msgs.smushing
	 */
	show_loader() {
		Smush.progress_bar( this.button, wp_smush_msgs.smushing, 'show' );
	};

	/**
	 * Hide loader.
	 *
	 * @var {string} wp_smush_msgs.smushing
	 */
	hide_loader() {
		Smush.progress_bar( this.button, wp_smush_msgs.smushing, 'hide' );
	};

	/**
	 * Show/hide the progress bar for Smushing/Restore/SuperSmush.
	 *
	 * @param cur_ele
	 * @param txt Message to be displayed
	 * @param {string} state show/hide
	 */
	static progress_bar( cur_ele, txt, state ) {
		// Update progress bar text and show it.
		const progress_button = cur_ele.parents().eq( 1 ).find( '.wp-smush-progress' );

		if ( 'show' === state ) {
			progress_button.html( txt );
		} else {
			/** @var {string} wp_smush_msgs.all_done */
			progress_button.html( wp_smush_msgs.all_done );
		}

		progress_button.toggleClass( 'visible' );
	};

	/**
	 * Finish single image Smush.
	 */
	single_done() {
		if ( this.is_bulk ) return;

		this.hide_loader();

		const self = this;

		this.request.done( function ( response ) {
			if ( 'undefined' !== typeof response.data ) {

				// Check if stats div exists.
				const parent    = self.status.parent(),
					stats_div = parent.find( '.smush-stats-wrapper' );

				// If we've updated status, replace the content.
				if ( response.data.status ) {
					//remove Links
					parent.find( '.smush-status-links' ).remove();
					self.status.replaceWith( response.data.status );
				}

				// Check whether to show membership validity notice or not.
				Smush.membership_validity( response.data );

				if ( response.success && 'Not processed' !== response.data ) {
					self.status.removeClass( 'sui-hidden' );
					self.button.parent().removeClass( 'unsmushed' ).addClass( 'smushed' );
					self.button.remove();
				} else {
					self.status.addClass( 'error' );
					/** @var {string} response.data.error_msg */
					self.status.html( response.data.error_msg );
					self.status.show();
				}

				//if ( 'undefined' !== stats_div && stats_div.length ) {
				//	stats_div.replaceWith( response.data.stats );
				//} else {
					parent.append( response.data.stats );
				//}

				/**
				 * Update image size in attachment info panel.
				 * @var {string|int} response.data.new_size
				 */
				Smush.update_image_stats( response.data.new_size );
			}
			self.enable_button();
		} ).error( function ( response ) {
			self.status.html( response.data );
			self.status.addClass( 'error' );
			self.enable_button();
		} );
	};

	/**
	 * Set pro savings stats if not premium user.
	 *
	 * For non-premium users, show expected avarage savings based
	 * on the free version savings.
	 */
	static set_pro_savings() {
		// Default values.
		let savings       = wp_smushit_data.savings_percent > 0 ? wp_smushit_data.savings_percent : 0,
			savings_bytes = wp_smushit_data.savings_bytes > 0 ? wp_smushit_data.savings_bytes : 0,
			orig_diff     = 2.22058824;

		if ( savings > 49 ) {
			orig_diff = 1.22054412;
		}

		// Calculate Pro savings.
		if ( savings > 0 ) {
			savings       = orig_diff * savings;
			savings_bytes = orig_diff * savings_bytes;
		}

		wp_smushit_data.pro_savings = {
			'percent': WP_Smush.helpers.precise_round( savings, 1 ),
			'savings_bytes': WP_Smush.helpers.formatBytes( savings_bytes, 1 )
		}
	};

	/**
	 * Update all stats sections based on the response.
	 *
	 * @param scan_type Current scan type.
	 */
	static update_stats( scan_type ) {
		const is_nextgen = 'undefined' !== typeof scan_type && 'nextgen' === scan_type;
		let super_savings = 0;

		// Calculate updated savings in bytes.
		wp_smushit_data.savings_bytes = parseInt( wp_smushit_data.size_before ) - parseInt( wp_smushit_data.size_after );

		const formatted_size = WP_Smush.helpers.formatBytes( wp_smushit_data.savings_bytes, 1 );
		const statsHuman     = jQuery( '.wp-smush-savings .wp-smush-stats-human' );

		if ( is_nextgen ) {
			statsHuman.html( formatted_size );
		} else {
			statsHuman.html( WP_Smush.helpers.getFormatFromString( formatted_size ) );
			jQuery( '.sui-summary-large.wp-smush-stats-human' ).html( WP_Smush.helpers.getSizeFromString( formatted_size ) );
		}

		// Update the savings percent.
		wp_smushit_data.savings_percent = WP_Smush.helpers.precise_round( ( parseInt( wp_smushit_data.savings_bytes ) / parseInt( wp_smushit_data.size_before ) ) * 100, 1 );
		if ( ! isNaN( wp_smushit_data.savings_percent ) ) {
			jQuery( '.wp-smush-savings .wp-smush-stats-percent' ).html( wp_smushit_data.savings_percent );
		}

		// Super-Smush savings.
		if ( 'undefined' !== typeof wp_smushit_data.savings_bytes && 'undefined' !== typeof wp_smushit_data.savings_resize ) {
			super_savings = parseInt( wp_smushit_data.savings_bytes ) - parseInt( wp_smushit_data.savings_resize );
			if ( super_savings > 0 ) {
				jQuery( 'li.super-smush-attachments span.smushed-savings' ).html( WP_Smush.helpers.formatBytes( super_savings, 1 ) );
			}
		}

		// Update image count.
		if ( is_nextgen ) {
			jQuery( '.sui-summary-details span.wp-smush-total-optimised' ).html( wp_smushit_data.count_images );
		} else {
			jQuery( 'span.smushed-items-count span.wp-smush-count-total span.wp-smush-total-optimised' ).html( wp_smushit_data.count_images );
		}

		// Update resize image count.
		jQuery( 'span.smushed-items-count span.wp-smush-count-resize-total span.wp-smush-total-optimised' ).html( wp_smushit_data.count_resize );

		// Update super-Smushed image count.
		const smushedCountDiv = jQuery( 'li.super-smush-attachments .smushed-count' );
		if ( smushedCountDiv.length && 'undefined' !== typeof wp_smushit_data.count_supersmushed ) {
			smushedCountDiv.html( wp_smushit_data.count_supersmushed );
		}

		// Update conversion savings.
		const smush_conversion_savings = jQuery( '.smush-conversion-savings' );
		if ( smush_conversion_savings.length > 0 && 'undefined' !== typeof ( wp_smushit_data.savings_conversion ) && wp_smushit_data.savings_conversion != '' ) {
			const conversion_savings = smush_conversion_savings.find( '.wp-smush-stats' );
			if ( conversion_savings.length > 0 ) {
				conversion_savings.html( WP_Smush.helpers.formatBytes( wp_smushit_data.savings_conversion, 1 ) );
			}
		}

		// Update resize savings.
		const smush_resize_savings = jQuery( '.smush-resize-savings' );
		if ( smush_resize_savings.length > 0 && 'undefined' !== typeof ( wp_smushit_data.savings_resize ) && wp_smushit_data.savings_resize != '' ) {
			// Get the resize savings in number.
			const savings_value = parseInt( wp_smushit_data.savings_resize );
			const resize_savings = smush_resize_savings.find( '.wp-smush-stats' );
			const resize_message = smush_resize_savings.find( '.wp-smush-stats-label-message' );
			// Replace only if value is grater than 0.
			if ( savings_value > 0 && resize_savings.length > 0 ) {
				// Hide message.
				if ( resize_message.length > 0 ) {
					resize_message.hide();
				}
				resize_savings.html( WP_Smush.helpers.formatBytes( wp_smushit_data.savings_resize, 1 ) );
			}
		}

		//Update pro Savings
		Smush.set_pro_savings();

		// Updating pro savings stats.
		if ( 'undefined' !== typeof wp_smushit_data.pro_savings ) {
			// Pro stats section.
			const smush_pro_savings = jQuery( '.smush-avg-pro-savings' );
			if ( smush_pro_savings.length > 0 ) {
				const pro_savings_percent = smush_pro_savings.find( '.wp-smush-stats-percent' );
				const pro_savings_bytes = smush_pro_savings.find( '.wp-smush-stats-human' );
				if ( pro_savings_percent.length > 0 && 'undefined' !== typeof wp_smushit_data.pro_savings.percent && wp_smushit_data.pro_savings.percent != '' ) {
					pro_savings_percent.html( wp_smushit_data.pro_savings.percent );
				}
				if ( pro_savings_bytes.length > 0 && 'undefined' !== typeof wp_smushit_data.pro_savings.savings_bytes && wp_smushit_data.pro_savings.savings_bytes != '' ) {
					pro_savings_bytes.html( wp_smushit_data.pro_savings.savings_bytes );
				}
			}
		}

		// Update remaining count.
		// Update sidebar count.
		const sidenavCountDiv = jQuery( '.smush-sidenav .wp-smush-remaining-count' );
		if ( sidenavCountDiv.length && 'undefined' !== typeof wp_smushit_data.resmush ) {
			if ( wp_smushit_data.resmush.length > 0 ) {
				sidenavCountDiv.html( wp_smushit_data.resmush.length );
			} else {
				jQuery( '.sui-summary-smush .smush-stats-icon' ).addClass( 'sui-hidden' );
				sidenavCountDiv.removeClass( 'sui-tag sui-tag-warning' ).html( '' );
			}
		}
	}

	/**
	 * Update image size in attachment info panel.
	 *
	 * @since 2.8
	 *
	 * @param {int} new_size
	 */
	static update_image_stats( new_size ) {
		if ( 0 === new_size ) {
			return;
		}

		const attachmentSize = jQuery( '.attachment-info .file-size' );
		const currentSize = attachmentSize.contents().filter( function () {
			return this.nodeType === 3;
		} ).text();

		// There is a space before the size.
		if ( currentSize !== ( ' ' + new_size ) ) {
			const sizeStrongEl = attachmentSize.contents().filter( function () {
				return this.nodeType === 1;
			} ).text();
			attachmentSize.html( '<strong>' + sizeStrongEl + '</strong> ' + new_size );
		}
	}

	/**
	 * Sync stats.
	 */
	sync_stats() {
		const message_holder = jQuery( 'div.wp-smush-bulk-progress-bar-wrapper div.wp-smush-count.tc' );
		// Store the existing content in a variable.
		const progress_message = message_holder.html();
		/** @var {string} wp_smush_msgs.sync_stats */
		message_holder.html( wp_smush_msgs.sync_stats );

		// Send ajax.
		jQuery.ajax( {
			type: 'GET',
			url: this.url,
			data: {
				'action': 'get_stats'
			},
			success: function ( response ) {
				if ( response && 'undefined' !== typeof response ) {
					response = response.data;
					jQuery.extend( wp_smushit_data, {
						count_images: response.count_images,
						count_smushed: response.count_smushed,
						count_total: response.count_total,
						count_resize: response.count_resize,
						count_supersmushed: response.count_supersmushed,
						savings_bytes: response.savings_bytes,
						savings_conversion: response.savings_conversion,
						savings_resize: response.savings_resize,
						size_before: response.size_before,
						size_after: response.size_after
					} );
					// Got the stats, update it.
					Smush.update_stats( this.smush_type );
				}
			}
		} ).always( () => message_holder.html( progress_message ) );
	};

	/**
	 * After the bulk Smushing has been finished.
	 */
	bulk_done() {
		if ( ! this.is_bulk ) return;

		// Enable the button.
		this.enable_button();

		const statusIcon = jQuery( '.sui-summary-smush .smush-stats-icon' );

		// Show notice.
		if ( 0 === this.ids.length ) {
			statusIcon.addClass( 'sui-hidden' );
			jQuery( '.bulk-smush-wrapper .wp-smush-all-done, .wp-smush-pagespeed-recommendation' ).show();
			jQuery( '.wp-smush-bulk-wrapper' ).hide();
			// Hide the progress bar if scan is finished.
			jQuery( '.wp-smush-bulk-progress-bar-wrapper' ).hide();
		} else {
			// Show loader.
			statusIcon.removeClass( 'sui-icon-loader sui-loading sui-hidden' ).addClass( 'sui-icon-info sui-warning' );

			const notice = jQuery( '.bulk-smush-wrapper .wp-smush-resmush-notice' );

			if ( notice.length > 0 ) {
				notice.show();
			} else {
				jQuery( '.bulk-smush-wrapper .wp-smush-remaining' ).show();
			}
		}

		// Enable re-Smush and scan button.
		jQuery( '.wp-resmush.wp-smush-action, .wp-smush-scan' ).removeAttr( 'disabled' );
	};

	is_resolved() {
		return 'resolved' === this.deferred.state();
	};

	/**
	 * Free Smush limit exceeded.
	 */
	free_exceeded() {
		if ( this.ids.length > 0 ) {
			const progress = jQuery( '.wp-smush-bulk-progress-bar-wrapper' );
			progress.addClass( 'wp-smush-exceed-limit' );
			progress.find( '.sui-progress-block .wp-smush-cancel-bulk' ).addClass('sui-hidden');
			progress.find( '.sui-progress-block .wp-smush-all' ).removeClass('sui-hidden');
			progress.find( '.sui-box-body.sui-no-padding-right' ).removeClass('sui-hidden');
		} else {
			jQuery( '.wp-smush-notice.wp-smush-all-done, .wp-smush-pagespeed-recommendation' ).show();
		}
	};

	/**
	 * Update remaining count.
	 */
	update_remaining_count() {
		if ( this.is_bulk_resmush ) {
			// Re-Smush notice.
			const resumeCountDiv = jQuery( '.wp-smush-resmush-notice .wp-smush-remaining-count' );
			if ( resumeCountDiv.length && 'undefined' !== typeof this.ids ) {
				resumeCountDiv.html( this.ids.length );
			}
		} else {
			// Smush notice.
			const wrapperCountDiv = jQuery( '.bulk-smush-wrapper .wp-smush-remaining-count' );
			if ( wrapperCountDiv.length && 'undefined' !== typeof this.ids ) {
				wrapperCountDiv.html( this.ids.length );
			}
		}

		// Update sidebar count.
		const sidenavCountDiv = jQuery( '.smush-sidenav .wp-smush-remaining-count' );
		if ( sidenavCountDiv.length && 'undefined' !== typeof this.ids ) {
			if ( this.ids.length > 0 ) {
				sidenavCountDiv.html( this.ids.length );
			} else {
				jQuery( '.sui-summary-smush .smush-stats-icon' ).addClass( 'sui-hidden' );
				sidenavCountDiv.removeClass( 'sui-tag sui-tag-warning' ).html( '' );
			}
		}
	};

	/**
	 * Adds the stats for the current image to existing stats.
	 *
	 * @param {array}   image_stats
	 * @param {string}  image_stats.count
	 * @param {boolean} image_stats.is_lossy
	 * @param {array}   image_stats.savings_resize
	 * @param {array}   image_stats.savings_conversion
	 * @param {string}  image_stats.size_before
	 * @param {string}  image_stats.size_after
	 * @param {string}  type
	 */
	static update_localized_stats( image_stats, type ) {
		// Increase the Smush count.
		if ( 'undefined' === typeof wp_smushit_data ) return;

		// No need to increase attachment count, resize, conversion savings for directory Smush.
		if ( 'media' === type ) {
			// Increase Smushed image count.
			wp_smushit_data.count_images = parseInt( wp_smushit_data.count_images ) + parseInt( image_stats.count );

			// Increase super Smush count, if applicable.
			if ( image_stats.is_lossy ) {
				wp_smushit_data.count_supersmushed = parseInt( wp_smushit_data.count_supersmushed ) + 1;
			}

			// Add to resize savings.
			wp_smushit_data.savings_resize = 'undefined' !== typeof image_stats.savings_resize.bytes ? parseInt( wp_smushit_data.savings_resize ) + parseInt( image_stats.savings_resize.bytes ) : parseInt( wp_smushit_data.savings_resize );

			// Update resize count.
			wp_smushit_data.count_resize = 'undefined' !== typeof image_stats.savings_resize.bytes ? parseInt( wp_smushit_data.count_resize ) + 1 : wp_smushit_data.count_resize;

			// Add to conversion savings.
			wp_smushit_data.savings_conversion = 'undefined' !== typeof image_stats.savings_conversion && 'undefined' !== typeof image_stats.savings_conversion.bytes ? parseInt( wp_smushit_data.savings_conversion ) + parseInt( image_stats.savings_conversion.bytes ) : parseInt( wp_smushit_data.savings_conversion );
		} else if ( 'directory_smush' === type ) {
			//Increase smushed image count
			wp_smushit_data.count_images = parseInt( wp_smushit_data.count_images ) + 1;
		} else if ( 'nextgen' === type ) {
			wp_smushit_data.count_supersmushed = parseInt( wp_smushit_data.count_supersmushed ) + 1;

			// Increase Smushed image count.
			wp_smushit_data.count_images = parseInt( wp_smushit_data.count_images ) + parseInt( image_stats.count );
		}

		// If we have savings. Update savings.
		if ( image_stats.size_before > image_stats.size_after ) {
			wp_smushit_data.size_before = 'undefined' !== typeof image_stats.size_before ? parseInt( wp_smushit_data.size_before ) + parseInt( image_stats.size_before ) : parseInt( wp_smushit_data.size_before );
			wp_smushit_data.size_after = 'undefined' !== typeof image_stats.size_after ? parseInt( wp_smushit_data.size_after ) + parseInt( image_stats.size_after ) : parseInt( wp_smushit_data.size_after );
		}

		// Add stats for resizing. Update savings.
		if ( 'undefined' !== typeof image_stats.savings_resize ) {
			wp_smushit_data.size_before = 'undefined' !== typeof image_stats.savings_resize.size_before ? parseInt( wp_smushit_data.size_before ) + parseInt( image_stats.savings_resize.size_before ) : parseInt( wp_smushit_data.size_before );
			wp_smushit_data.size_after = 'undefined' !== typeof image_stats.savings_resize.size_after ? parseInt( wp_smushit_data.size_after ) + parseInt( image_stats.savings_resize.size_after ) : parseInt( wp_smushit_data.size_after );
		}

		// Add stats for conversion. Update savings.
		if ( 'undefined' !== typeof image_stats.savings_conversion ) {
			wp_smushit_data.size_before = 'undefined' !== typeof image_stats.savings_conversion.size_before ? parseInt( wp_smushit_data.size_before ) + parseInt( image_stats.savings_conversion.size_before ) : parseInt( wp_smushit_data.size_before );
			wp_smushit_data.size_after = 'undefined' !== typeof image_stats.savings_conversion.size_after ? parseInt( wp_smushit_data.size_after ) + parseInt( image_stats.savings_conversion.size_after ) : parseInt( wp_smushit_data.size_after );
		}
	};

	/**
	 * Update progress.
	 *
	 * @param _res
	 */
	update_progress( _res ) {
		if ( ! this.is_bulk_resmush && ! this.is_bulk ) return;

		let progress = '';

		// Update localized stats.
		if ( _res && ( 'undefined' !== typeof _res.data && 'undefined' !== typeof _res.data.stats ) ) {
			Smush.update_localized_stats( _res.data.stats, this.smush_type );
		}

		if ( ! this.is_bulk_resmush ) {
			// Handle progress for normal bulk smush.
			progress = ( ( this.smushed + this.errors.length ) / this.total ) * 100;
		} else {
			// If the request was successful, update the progress bar.
			if ( _res.success ) {
				// Handle progress for super Smush progress bar.
				if ( wp_smushit_data.resmush.length > 0 ) {
					// Update the count.
					jQuery( '.wp-smush-images-remaining' ).html( wp_smushit_data.resmush.length );
				} else if ( 0 === wp_smushit_data.resmush.length && 0 === this.ids.length ) {
					// If all images are re-Smushed, show the All Smushed message.
					jQuery( '.bulk-resmush-wrapper .wp-smush-all-done, .wp-smush-pagespeed-recommendation' ).removeClass( 'sui-hidden' );

					// Hide everything else.
					jQuery( '.wp-smush-resmush-wrap, .wp-smush-bulk-progress-bar-wrapper' ).hide();
				}
			}

			// Handle progress for normal bulk Smush. Set progress bar width.
			if ( 'undefined' !== typeof this.ids && 'undefined' !== typeof this.total && this.total > 0 ) {
				progress = ( ( this.smushed + this.errors.length ) / this.total ) * 100;
			}
		}

		// No more images left. Show bulk wrapper and Smush notice.
		if ( 0 === this.ids.length ) {
			// Sync stats for bulk Smush media library ( skip for Nextgen ).
			if ( 'nextgen' !== this.smush_type ) {
				this.sync_stats();
			}
			jQuery( '.bulk-smush-wrapper .wp-smush-all-done, .wp-smush-pagespeed-recommendation' ).show();
			jQuery( '.wp-smush-bulk-wrapper' ).hide();
		}

		// Update remaining count.
		this.update_remaining_count();

		// Increase the progress bar and counter.
		this._update_progress( this.smushed + this.errors.length, WP_Smush.helpers.precise_round( progress, 1 ) );

		// Update stats and counts.
		Smush.update_stats( this.smush_type );
	};

	/**
	 * Update progress.
	 *
	 * @param {int}    count  Number of images Smushed.
	 * @param {string} width  Percentage complete.
	 * @private
	 */
	_update_progress( count, width ) {
		if ( ! this.is_bulk && ! this.is_bulk_resmush ) return;

		// Progress bar label.
		jQuery( 'span.wp-smush-images-percent' ).html( width );
		// Progress bar.
		jQuery( '.bulk-smush-wrapper .wp-smush-progress-inner' ).css( 'width', width + '%' );

		// Progress bar status.
		jQuery( '.bulk-smush-wrapper .sui-progress-state-text' )
			.find( 'span:first-child' ).html( count )
			.find( 'span:last-child' ).html( this.total );
	};

	/**
	 * Whether to send the ajax requests further or not.
	 *
	 * @returns {*|boolean}
	 */
	continue() {
		let continue_smush = this.button.attr( 'continue_smush' );

		if ( 'undefined' === typeof continue_smush ) {
			continue_smush = true;
		}

		if ( 'false' === continue_smush || ! continue_smush ) {
			continue_smush = false;
		}

		return continue_smush && this.ids.length > 0 && this.is_bulk;
	};

	/**
	 * Add image ID to the errors array.
	 *
	 * @param {int} id
	 */
	increment_errors( id ) {
		this.errors.push( id );
	};

	/**
	 * Add image ID to smushed array.
	 *
	 * @param {int} id
	 */
	increment_smushed( id ) {
		this.smushed = this.smushed + 1;
	}

	/**
	 * Send ajax request for Smushing single and bulk, call update_progress on ajax response.
	 *
	 * @returns {*|{}}
	 */
	call_ajax() {
		let nonce_value = '';
		// Remove from array while processing so we can continue where left off.
		this.current_id = this.is_bulk ? this.ids.shift() : this.button.data( 'id' );

		// Remove the ID from respective variable as well.
		Smush.update_smush_ids( this.current_id );

		const nonce_field = this.button.parent().find( '#_wp_smush_nonce' );
		if ( nonce_field ) {
			nonce_value = nonce_field.val();
		}

		const self = this;

		this.request = Smush.ajax( this.is_bulk_resmush, this.current_id, this.url, nonce_value )
			.done( function ( res ) {
				// If no response or success is false, do not process further. Increase the error count except if bulk request limit exceeded.
				if ( 'undefined' === typeof res.success || ( 'undefined' !== typeof res.success && false === res.success && 'undefined' !== typeof res.data && 'limit_exceeded' !== res.data.error ) ) {
					self.increment_errors( self.current_id );

					/** @var {string} res.data.file_name */
					const error_msg = Smush.prepare_error_row( res.data.error_message, res.data.file_name, res.data.thumbnail, self.current_id );

					self.log.show();

					if ( self.errors.length > 5 ) {
						$('.smush-bulk-errors-actions').removeClass('sui-hidden');
					} else {
						// Print the error on screen.
						self.log.find( '.smush-bulk-errors' ).append( error_msg );
					}

				} else if ( 'undefined' !== typeof res.success && res.success ) {
					// Increment the smushed count if image smushed without errors.
					self.increment_smushed( self.current_id );
				}

				// Check whether to show the warning notice or not.
				Smush.membership_validity( res.data );

				/**
				 * Bulk Smush limit exceeded: Stop ajax requests, remove progress bar, append the last image ID
				 * back to Smush variable, and reset variables to allow the user to continue bulk Smush.
				 */
				if ( 'undefined' !== typeof res.data && 'limit_exceeded' === res.data.error && ! self.is_resolved() ) {
					// Show error message.
					const bulk_error_message = jQuery( '.wp-smush-bulk-progress-bar-wrapper' );
					/** @var {string} res.data.error_message */
					bulk_error_message.find( '.sui-notice-warning' )
						.html( '<p>' + res.data.error_message + '</p>' )
						.show();

					// Add a data attribute to the Smush button, to stop sending ajax.
					self.button.attr( 'continue_smush', false );

					self.free_exceeded();

					// Reinsert the current ID.
					wp_smushit_data.unsmushed.unshift( self.current_id );
				} else if ( self.is_bulk ) {
					self.update_progress( res );
				} else if ( 0 === self.ids.length ) {
					// Sync stats anyway.
					self.sync_stats();
				}

				self.single_done();
			} )
			.complete( function () {
				if ( ! self.continue() || ! self.is_bulk ) {
					// Calls deferred.done()
					self.deferred.resolve();
				} else {
					self.call_ajax();
				}
			} );

		this.deferred.errors = this.errors;
		return this.deferred;
	};

	/**
	 * Prepare error row.
	 *
	 * @since 1.9.0
	 *
	 * @param {string} errorMsg   Error message.
	 * @param {string} fileName   File name.
	 * @param {string} thumbnail  Thumbnail for image (if available).
	 * @param {int}    id         Image ID.
	 *
	 * @returns {string}
	 */
	static prepare_error_row( errorMsg, fileName, thumbnail, id ) {
		const thumbDiv = ( 'undefined' === typeof thumbnail ) ? '<i class="sui-icon-photo-picture" aria-hidden="true"></i>' : thumbnail;
		const fileLink = ( 'undefined' === fileName || 'undefined' === typeof fileName ) ? 'undefined' : fileName;

		return '<div class="smush-bulk-error-row">' +
				'<div class="smush-bulk-image-data">' + thumbDiv +
					'<span class="smush-image-name">' + fileLink + '</span>' +
					'<span class="smush-image-error">' + errorMsg + '</span>' +
				'</div>' +
			/*
				'<div class="smush-bulk-image-actions">' +
					'<button type="button" class="sui-button-icon sui-tooltip sui-tooltip-constrained sui-tooltip-top-left smush-ignore-image" data-tooltip="Ignore this image from bulk smushing" data-id="' + id + '">' +
						'<i class="sui-icon-eye-hide" aria-hidden="true"></i>' +
					'</button>' +
				'</div>' +
			*/
			'</div>';
	};

	/**
	 * Send ajax request for single and bulk Smushing.
	 */
	run() {
		// If bulk and we have a definite number of IDs.
		if ( this.is_bulk && this.ids.length > 0 )
			this.call_ajax();

		if ( ! this.is_bulk )
			this.call_ajax();
	};

	/**
	 * Show bulk Smush errors, and disable bulk Smush button on completion.
	 */
	bind_deferred_events() {
		const self = this;

		this.deferred.done( function () {
			self.button.removeAttr( 'continue_smush' );

			if ( self.errors.length ) {
				/** @var {string} wp_smush_msgs.error_in_bulk */
				let msg = wp_smush_msgs.error_in_bulk
					.replace( "{{errors}}", self.errors.length )
					.replace( "{{total}}", self.total )
					.replace( "{{smushed}}", self.smushed );

				jQuery( '.wp-smush-all-done' )
					.addClass( 'sui-notice-warning' )
					.removeClass( 'sui-notice-success' )
					.find( 'p' ).html( msg );
			}

			self.bulk_done();

			// Re-enable the buttons.
			jQuery( '.wp-smush-all:not(.wp-smush-finished), .wp-smush-scan' ).removeAttr( 'disabled' );
		} );
	};

	/**
	 * Handles the cancel button click.
	 * Update the UI, and enable the bulk Smush button.
	 */
	cancel_ajax() {
		const self = this;

		jQuery( '.wp-smush-cancel-bulk' ).on( 'click', function () {
			// Add a data attribute to the Smush button, to stop sending ajax.
			self.button.attr( 'continue_smush', false );
			// Sync and update stats.
			self.sync_stats();
			Smush.update_stats( this.smush_type );

			self.request.abort();
			self.enable_button();
			self.button.removeClass( 'wp-smush-started' );
			wp_smushit_data.unsmushed.unshift( self.current_id );
			jQuery( '.wp-smush-bulk-wrapper' ).show();

			// Hide the progress bar.
			jQuery( '.wp-smush-bulk-progress-bar-wrapper' ).hide();
		} );
	};

	/**
	 * Remove the current ID from the unSmushed/re-Smush variable.
	 *
	 * @param current_id
	 */
	static update_smush_ids( current_id ) {
		if ( 'undefined' !== typeof wp_smushit_data.unsmushed && wp_smushit_data.unsmushed.length > 0 ) {
			const u_index = wp_smushit_data.unsmushed.indexOf( current_id );
			if ( u_index > -1 ) {
				wp_smushit_data.unsmushed.splice( u_index, 1 );
			}
		}

		// Remove from the re-Smush list.
		if ( 'undefined' !== typeof wp_smushit_data.resmush && wp_smushit_data.resmush.length > 0 ) {
			const index = wp_smushit_data.resmush.indexOf( current_id );
			if ( index > -1 ) {
				wp_smushit_data.resmush.splice( index, 1 );
			}
		}
	};

}

export default Smush;