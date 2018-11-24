( function( $ ) {

	'use strict';

	if ( typeof flamingo === 'undefined' || flamingo === null ) {
		return;
	}

	$( function() {
		postboxes.add_postbox_toggles( flamingo.screenId );
	} );

} )( jQuery );
