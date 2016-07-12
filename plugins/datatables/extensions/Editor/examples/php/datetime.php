<?php

/*
 * Example PHP implementation used for date time examples
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
		Field::inst( 'updated_date' )
			->validator( 'Validate::dateFormat', array(
				'empty' => false,
				'format' => 'm-d-Y g:i A'
			) )
			->getFormatter( 'Format::datetime', array(
				'from' => 'Y-m-d H:i:s',
				'to' =>   'm-d-Y g:i A'
			) )
			->setFormatter( 'Format::datetime', array(
				'from' => 'm-d-Y g:i A',
				'to' =>   'Y-m-d H:i:s'
			) ),
		Field::inst( 'registered_date' )
			->validator( 'Validate::dateFormat', array(
				'format' => 'j M Y H:i'
			) )
			->getFormatter( 'Format::datetime', array(
				'from' => 'Y-m-d H:i:s',
				'to' =>   'j M Y H:i'
			) )
			->setFormatter( 'Format::datetime', array(
				'from' => 'j M Y H:i',
				'to' =>   'Y-m-d H:i:s'
			) )
	)
	->process( $_POST )
	->json();
