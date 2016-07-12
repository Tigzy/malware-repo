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

// Build our Editor instance and process the data coming from _POST
$editor = Editor::inst( $db, 'tranches', 'gid' )
    ->fields(
        Field::inst( 'tranches.gid' )->set(false),
        Field::inst( 'tranches.code_pouvoir' ),
        Field::inst( 'tranches.code_direction' ),
        Field::inst( 'tranches.code_type_depense' ),
        Field::inst( 'tranches.code_depense' )
    );
// where clause logic
// nothing here, we just want to create a record!
 
// we've broken the line to make sure where clauses come before processing
$editor
    ->process( $_POST )
    ->json();