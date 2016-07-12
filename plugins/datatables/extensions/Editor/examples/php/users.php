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

if ( ! isset($_POST['site']) || ! is_numeric($_POST['site']) ) {
	echo json_encode( [ "data" => [] ] );
}
else {
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
		->where( 'site', $_POST['site'] )
		->process($_POST)
		->json();
}
