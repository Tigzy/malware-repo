<?php

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


/*
 * Example PHP implementation used for the join.html example
 */
Editor::inst( $db, 'users' )
	->field( 
		Field::inst( 'users.first_name' ),
		Field::inst( 'users.last_name' ),
		Field::inst( 'users.phone' ),
		Field::inst( 'users.site' )
			->options( 'sites', 'id', 'name' )
			->validator( 'Validate::dbValues' ),
		Field::inst( 'sites.name' )
	)
	->leftJoin( 'sites', 'sites.id', '=', 'users.site' )
	->process($_POST)
	->json();
