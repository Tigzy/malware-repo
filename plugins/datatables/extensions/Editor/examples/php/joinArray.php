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
		Field::inst( 'users.site' )
			->options( 'sites', 'id', 'name' ),
		Field::inst( 'sites.name' )
	)
	->leftJoin( 'sites', 'sites.id', '=', 'users.site' )
	->join(
		Mjoin::inst( 'access' )
			->link( 'users.id', 'user_access.user_id' )
			->link( 'access.id', 'user_access.access_id' )
			->order( 'name asc' )
			->fields(
				Field::inst( 'id' )
					->validator( 'Validate::required' )
					->options( 'access', 'id', 'name' ),
				Field::inst( 'name' )
			)
	)
	->process($_POST)
	->json();
