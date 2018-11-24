<?php

function flamingo_htmlize( $val ) {
	$result = '';

	if ( is_array( $val ) ) {
		foreach ( $val as $v ) {
			$result .= sprintf( '<li>%s</li>', flamingo_htmlize( $v ) );
		}

		$result = sprintf( '<ul>%s</ul>', $result );
	} else {
		$result = wpautop( esc_html( (string) $val ) );
	}

	return apply_filters( 'flamingo_htmlize', $result, $val );
}
