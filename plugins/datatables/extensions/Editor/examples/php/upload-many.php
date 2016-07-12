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
Editor::inst( $db, 'users' )
	->fields(
		Field::inst( 'users.first_name' ),
		Field::inst( 'users.last_name' ),
		Field::inst( 'users.phone' ),
		Field::inst( 'users.site' )
			->options( 'sites', 'id', 'name' ),
		Field::inst( 'sites.name' )
	)
	->join(
		Mjoin::inst( 'files' )
			->link( 'users.id', 'users_files.user_id' )
			->link( 'files.id', 'users_files.file_id' )
			->fields(
				Field::inst( 'id' )
					->upload( Upload::inst( $_SERVER['DOCUMENT_ROOT'].'/upload/__ID__.__EXTN__' )
						->db( 'files', 'id', array(
							'filename'    => Upload::DB_FILE_NAME,
							'filesize'    => Upload::DB_FILE_SIZE,
							'web_path'    => Upload::DB_WEB_PATH,
							'system_path' => Upload::DB_SYSTEM_PATH
						) )
						->validator( function ( $file ) {
							return$file['size'] >= 50000 ?
								"Files must be smaller than 50K" :
								null;
						} )
						->allowedExtensions( [ 'png', 'jpg' ], "Please upload an image" )
					)
			)
	)
	->leftJoin( 'sites', 'sites.id', '=', 'users.site' )
	->process( $_POST )
	->json();
