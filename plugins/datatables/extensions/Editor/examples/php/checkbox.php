<?php

/*
 * Example PHP implementation used for the checkbox.html example
 */

// DataTables PHP library
include( "../../php/DataTables.php" );

// Alias Editor classes so they are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate;

// Build our Editor instance and process the data coming from _POST
Editor::inst( $db, 'users' )
	->fields(
		Field::inst( 'first_name' ),
		Field::inst( 'last_name' ),
		Field::inst( 'phone' ),
		Field::inst( 'city' ),
		Field::inst( 'zip' ),
		Field::inst( 'active' )
			->setFormatter( function ( $val, $data, $opts ) {
				return ! $val ? 0 : 1;
			} )
	)
	->process( $_POST )
	->json();

