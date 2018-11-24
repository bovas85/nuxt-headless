<?php

function flamingo_csv_row( $inputs = array() ) {
	$row = array();

	foreach ( $inputs as $input ) {
		$row[] = apply_filters( 'flamingo_csv_quotation', $input );
	}

	$separator = apply_filters( 'flamingo_csv_value_separator', ',' );

	return implode( $separator, $row );
}

add_filter( 'flamingo_csv_quotation', 'flamingo_csv_quote' );

function flamingo_csv_quote( $input ) {
	return sprintf( '"%s"', str_replace( '"', '""', $input ) );
}
