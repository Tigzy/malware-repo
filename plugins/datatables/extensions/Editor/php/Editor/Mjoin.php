<?php
/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor, utilising PHP 5.3+.
 *
 *  @author    SpryMedia
 *  @copyright 2012 SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *  @link      http://editor.datatables.net
 */

namespace DataTables\Editor;
if (!defined('DATATABLES')) exit();

use DataTables\Editor\Join;


/**
 * The `Mjoin` class extends the `Join` class with the join data type set to
 * 'array', whereas the `Join` default is `object` which has been rendered
 * obsolete by the `Editor->leftJoin()` method. The API API is otherwise
 * identical.
 *
 * This class is recommended over the `Join` class.
 */
class MJoin extends Join
{
	function __construct( $table=null )
	{
		parent::__construct( $table, 'array' );
	}
}
