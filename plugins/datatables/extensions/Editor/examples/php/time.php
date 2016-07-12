<?php

/*
 * Example PHP implementation used for time examples
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
		Field::inst( 'city' ),
		Field::inst( 'shift_start' )
			->validator( 'Validate::dateFormat', array(
				'empty' => false,
				'format' => 'g:i A'
			) )
			->getFormatter( 'Format::datetime', array( 'from' => 'H:i:s', 'to' => 'g:i A' ) )
			->setFormatter( 'Format::datetime', array( 'from' => 'g:i A', 'to' => 'H:i:s' ) ),
		Field::inst( 'shift_end' )
			->validator( 'Validate::dateFormat', array(
				'empty' => false,
				'format' => 'H:i:s'
			) )
	)
	->process( $_POST )
	->json();

