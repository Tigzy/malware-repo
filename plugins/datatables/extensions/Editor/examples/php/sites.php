<?php

/*
 * Example PHP implementation used for the index.html example
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
Editor::inst( $db, 'sites' )
	->fields(
		Field::inst( 'id' )->set( false ),
		Field::inst( 'name' )->validator( 'Validate::notEmpty' )
	)
	->join(
		Mjoin::inst( 'users' )
			->link( 'sites.id', 'users.site' )
			->fields(
				Field::inst( 'id' )
			)
	)
	->process( $_POST )
	->json();
