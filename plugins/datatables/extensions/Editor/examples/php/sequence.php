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
Editor::inst( $db, 'audiobooks' )
	->fields(
		Field::inst( 'title' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'author' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'duration' )->validator( 'Validate::notEmpty' ),
		Field::inst( 'readingOrder' )->validator( 'Validate::numeric' )
	)
	->on( 'preCreate', function ( $editor, $values ) {
		// On create update all the other records to make room for our new one
		$editor->db()
			->query( 'update', 'audiobooks' )
			->set( 'readingOrder', 'readingOrder+1', false )
			->where( 'readingOrder', $values['readingOrder'], '>=' )
			->exec();
	} )
	->on( 'postRemove', function ( $editor, $id, $values ) {
		// On remove, the sequence needs to be updated to decrement all rows
		// beyond the deleted row
		$editor->db()
			->query( 'update', 'audiobooks' )
			->set( 'readingOrder', 'readingOrder-1', false )
			->where( 'readingOrder', $values['readingOrder'], '>' )
			->exec();
	} )
	->process( $_POST )
	->json();
