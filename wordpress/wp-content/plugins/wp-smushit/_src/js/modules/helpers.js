/**
 * Helpers functions.
 *
 * @since 2.9.0  Moved from admin.js
 */

( function( $ ) {
	'use strict';

	WP_Smush.helpers = {

		init: () => {},

		/**
		 * Convert bytes to human readable form.
		 *
		 * @param a  Bytes
		 * @param b  Number of digits
		 * @returns {*} Formatted Bytes
		 */
		formatBytes: ( a, b ) => {
			const thresh = 1024,
				  units  = ['KB', 'MB', 'GB', 'TB', 'PB'];

			if ( Math.abs( a ) < thresh ) {
				return a + ' B';
			}

			let u = -1;

			do {
				a /= thresh;
				++u;
			} while ( Math.abs( a ) >= thresh && u < units.length - 1 );

			return a.toFixed( b ) + ' ' + units[u];
		},

		/**
		 * Get size from a string.
		 *
		 * @param formatted_size  Formatter string
		 * @returns {*} Formatted Bytes
		 */
		getSizeFromString: ( formatted_size ) => {
			return formatted_size.replace( /[a-zA-Z]/g, '' ).trim();
		},

		/**
		 * Get type from formatted string.
		 *
		 * @param formatted_size  Formatted string
		 * @returns {*} Formatted Bytes
		 */
		getFormatFromString: ( formatted_size ) => {
			return formatted_size.replace( /[0-9.]/g, '' ).trim();
		},

		/**
		 * Stackoverflow: http://stackoverflow.com/questions/1726630/formatting-a-number-with-exactly-two-decimals-in-javascript
		 * @param num
		 * @param decimals
		 * @returns {number}
		 */
		precise_round: ( num, decimals ) => {
			const sign = num >= 0 ? 1 : -1;
			// Keep the percentage below 100.
			num = num > 100 ? 100 : num;
			return (Math.round( (num * Math.pow( 10, decimals )) + (sign * 0.001) ) / Math.pow( 10, decimals ));
		},

		/**
		 * Finds y value of given object.
		 *
		 * @param obj
		 * @returns {*[]}
		 */
		findPos: ( obj ) => {
			let cur_top = 0;

			if ( obj.offsetParent ) {
				do {
					cur_top += obj.offsetTop;
				} while ( obj = obj.offsetParent );

				return [cur_top];
			}
		},

		/**
		 * Checks for the specified param in URL.
		 *
		 * @param arg
		 * @returns {*}
		 */
		geturlparam: ( arg ) => {
			const sPageURL = window.location.search.substring( 1 );
			const sURLVariables = sPageURL.split( '&' );

			for ( let i = 0; i < sURLVariables.length; i++ ) {
				const sParameterName = sURLVariables[i].split( '=' );
				if ( sParameterName[0] === arg ) {
					return sParameterName[1];
				}
			}
		}

	};

	WP_Smush.helpers.init();

}( jQuery ));
