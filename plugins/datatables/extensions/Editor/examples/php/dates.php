<?php

/*
 * Example PHP implementation used for date examples
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

// Allow a number of different formats to be submitted for the various demos
$format = isset( $_GET['format'] ) ?
	$_GET['format'] :
	'';

if ( $format === 'custom' ) {
	$update = 'n/j/Y';
	$registered = 'l j F Y';
}
else {
	$update = Format::DATE_ISO_8601;
	$registered = Format::DATE_ISO_8601;
}

// Build our Editor instance and process the data coming from _POST
Editor::inst( $db, 'users' )
	->fields(
		Field::inst( 'first_name' ),
		Field::inst( 'last_name' ),
		Field::inst( 'updated_date' )
			->validator( 'Validate::dateFormat', array(
				'empty' => false,
				'format' => $update
			) )
			->getFormatter( 'Format::date_sql_to_format', $update )
			->setFormatter( 'Format::date_format_to_sql', $update ),
		Field::inst( 'registered_date' )
			->validator( 'Validate::dateFormat', $registered )
			->getFormatter( 'Format::date_sql_to_format', $registered )
			->setFormatter( 'Format::date_format_to_sql', $registered )
	)
	->process( $_POST )
	->json();
