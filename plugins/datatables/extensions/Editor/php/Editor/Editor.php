<?php
/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor, utilising PHP 5.3+.
 *
 *  @author    SpryMedia
 *  @version   1.5.6
 *  @copyright 2012 SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *  @link      http://editor.datatables.net
 */

namespace DataTables;
if (!defined('DATATABLES')) exit();

use
	DataTables,
	DataTables\Editor\Join,
	DataTables\Editor\Field;


/**
 * DataTables Editor base class for creating editable tables.
 *
 * Editor class instances are capable of servicing all of the requests that
 * DataTables and Editor will make from the client-side - specifically:
 * 
 * * Get data
 * * Create new record
 * * Edit existing record
 * * Delete existing records
 *
 * The Editor instance is configured with information regarding the
 * database table fields that you which to make editable, and other information
 * needed to read and write to the database (table name for example!).
 *
 * This documentation is very much focused on describing the API presented
 * by these DataTables Editor classes. For a more general overview of how
 * the Editor class is used, and how to install Editor on your server, please
 * refer to the {@link http://editor.datatables.net/manual Editor manual}.
 *
 *  @example 
 *    A very basic example of using Editor to create a table with four fields.
 *    This is all that is needed on the server-side to create a editable
 *    table - the {@link process} method determines what action DataTables /
 *    Editor is requesting from the server-side and will correctly action it.
 *    <code>
 *      Editor::inst( $db, 'browsers' )
 *          ->fields(
 *              Field::inst( 'first_name' )->validator( 'Validate::required' ),
 *              Field::inst( 'last_name' )->validator( 'Validate::required' ),
 *              Field::inst( 'country' ),
 *              Field::inst( 'details' )
 *          )
 *          ->process( $_POST )
 *          ->json();
 *    </code>
 */
class Editor extends Ext {
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Statics
	 */

	/** Request type - read */
	const ACTION_READ = 'read';

	/** Request type - create */
	const ACTION_CREATE = 'create';

	/** Request type - edit */
	const ACTION_EDIT = 'edit';

	/** Request type - delete */
	const ACTION_DELETE = 'delete';

	/** Request type - upload */
	const ACTION_UPLOAD = 'upload';


	/**
	 * Determine the request type from an HTTP request.
	 * 
	 * @param array $http Typically $_POST, but can be any array used to carry
	 *   an Editor payload
	 * @return string `Editor::ACTION_READ`, `Editor::ACTION_CREATE`,
	 *   `Editor::ACTION_EDIT` or `Editor::ACTION_DELETE` indicating the request
	 *   type.
	 */
	static public function action ( $http )
	{
		if ( ! isset( $http['action'] ) ) {
			return self::ACTION_READ;
		}

		switch ( $http['action'] ) {
			case 'create':
				return self::ACTION_CREATE;

			case 'edit':
				return self::ACTION_EDIT;

			case 'remove':
				return self::ACTION_DELETE;

			case 'upload':
				return self::ACTION_UPLOAD;

			default:
				throw new \Exception("Unknown Editor action: ".$http['action']);
		}
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Constructor.
	 *  @param Database $db An instance of the DataTables Database class that we can
	 *    use for the DB connection. Can be given here or with the 'db' method.
	 *    <code>
	 *      456
	 *    </code>
	 *  @param string|array $table The table name in the database to read and write
	 *    information from and to. Can be given here or with the 'table' method.
	 *  @param string $pkey Primary key column name in the table given in the $table
	 *    parameter. Can be given here or with the 'pkey' method.
	 */
	function __construct( $db=null, $table=null, $pkey=null )
	{
		// Set constructor parameters using the API - note that the get/set will
		// ignore null values if they are used (i.e. not passed in)
		$this->db( $db );
		$this->table( $table );
		$this->pkey( $pkey );
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public properties
	 */

	/** @var string */
	public $version = '1.5.6';



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */

	/** @var DataTables\Database */
	private $_db = null;

	/** @var DataTables\Editor\Field[] */
	private $_fields = array();

	/** @var array */
	private $_formData;

	/** @var array */
	private $_processData;

	/** @var string */
	private $_idPrefix = 'row_';

	/** @var DataTables\Editor\Join[] */
	private $_join = array();

	/** @var string */
	private $_pkey = 'id';

	/** @var string[] */
	private $_table = array();

	/** @var boolean */
	private $_transaction = true;

	/** @var array */
	private $_where = array();

	/** @var array */
	private $_leftJoin = array();

	/** @var boolean - deprecated */
	private $_whereSet = false;

	/** @var array */
	private $_out = array(
		"fieldErrors" => array(),
		"error" => "",
		"data" => array(),
		"ipOpts" => array()
	);

	/** @var array */
	private $_events = array();



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Get the data constructed in this instance.
	 * 
	 * This will get the PHP array of data that has been constructed for the 
	 * command that has been processed by this instance. Therefore only useful after
	 * process has been called.
	 *  @return array Processed data array.
	 */
	public function data ()
	{
		return $this->_out;
	}


	/**
	 * Get / set the DB connection instance
	 *  @param Database $_ DataTable's Database class instance to use for database
	 *    connectivity. If not given, then used as a getter.
	 *  @return Database|self The Database connection instance if no parameter
	 *    is given, or self if used as a setter.
	 */
	public function db ( $_=null )
	{
		return $this->_getSet( $this->_db, $_ );
	}


	/**
	 * Get / set field instance.
	 * 
	 * The list of fields designates which columns in the table that Editor will work
	 * with (both get and set).
	 *  @param Field|string $_... This parameter effects the return value of the
	 *      function:
	 *
	 *      * `null` - Get an array of all fields assigned to the instance
	 * 	    * `string` - Get a specific field instance whose 'name' matches the
	 *           field passed in
	 *      * {@link Field} - Add a field to the instance's list of fields. This
	 *           can be as many fields as required (i.e. multiple arguments)
	 *      * `array` - An array of {@link Field} instances to add to the list
	 *        of fields.
	 *  @return Field|Field[]|Editor The selected field, an array of fields, or
	 *      the Editor instance for chaining, depending on the input parameter.
	 *  @throws \Exception Unkown field error
	 *  @see {@link Field} for field documentation.
	 */
	public function field ( $_=null )
	{
		if ( is_string( $_ ) ) {
			for ( $i=0, $ien=count($this->_fields) ; $i<$ien ; $i++ ) {
				if ( $this->_fields[$i]->name() === $_ ) {
					return $this->_fields[$i];
				}
			}

			throw new \Exception('Unknown field: '.$_);
		}

		if ( $_ !== null && !is_array($_) ) {
			$_ = func_get_args();
		}
		return $this->_getSet( $this->_fields, $_, true );
	}


	/**
	 * Get / set field instances.
	 * 
	 * An alias of {@link field}, for convenience.
	 *  @param Field $_... Instances of the {@link Field} class, given as a single 
	 *    instance of {@link Field}, an array of {@link Field} instances, or multiple
	 *    {@link Field} instance parameters for the function.
	 *  @return Field[]|self Array of fields, or self if used as a setter.
	 *  @see {@link Field} for field documentation.
	 */
	public function fields ( $_=null )
	{
		if ( $_ !== null && !is_array($_) ) {
			$_ = func_get_args();
		}
		return $this->_getSet( $this->_fields, $_, true );
	}


	/**
	 * Get / set the DOM prefix.
	 *
	 * Typically primary keys are numeric and this is not a valid ID value in an
	 * HTML document - is also increases the likelihood of an ID clash if multiple
	 * tables are used on a single page. As such, a prefix is assigned to the 
	 * primary key value for each row, and this is used as the DOM ID, so Editor
	 * can track individual rows.
	 *  @param string $_ Primary key's name. If not given, then used as a getter.
	 *  @return string|self Primary key value if no parameter is given, or
	 *    self if used as a setter.
	 */
	public function idPrefix ( $_=null )
	{
		return $this->_getSet( $this->_idPrefix, $_ );
	}


	/**
	 * Get the data that is being processed by the Editor instance. This is only
	 * useful once the `process()` method has been called, and is available for
	 * use in validation and formatter methods.
	 *
	 *   @return array Data given to `process()`.
	 */
	public function inData ()
	{
		return $this->_processData;
	}


	/**
	 * Get / set join instances. Note that for the majority of use cases you
	 * will want to use the `leftJoin()` method. It is significantly easier
	 * to use if you are just doing a simple left join!
	 * 
	 * The list of Join instances that Editor will join the parent table to
	 * (i.e. the one that the {@link table} and {@link fields} methods refer to
	 * in this class instance).
	 *
	 *  @param Join $_,... Instances of the {@link Join} class, given as a
	 *    single instance of {@link Join}, an array of {@link Join} instances,
	 *    or multiple {@link Join} instance parameters for the function.
	 *  @return Join[]|self Array of joins, or self if used as a setter.
	 *  @see {@link Join} for joining documentation.
	 */
	public function join ( $_=null )
	{
		if ( $_ !== null && !is_array($_) ) {
			$_ = func_get_args();
		}
		return $this->_getSet( $this->_join, $_, true );
	}


	/**
	 * Get the JSON for the data constructed in this instance.
	 * 
	 * Basically the same as the {@link data} method, but in this case we echo, or
	 * return the JSON string of the data.
	 *  @param boolean $print Echo the JSON string out (true, default) or return it
	 *    (false).
	 *  @return string|self self if printing the JSON, or JSON representation of 
	 *    the processed data if false is given as the first parameter.
	 */
	public function json ( $print=true )
	{
		if ( $print ) {
			echo json_encode( $this->_out );
			return $this;
		}
		return json_encode( $this->_out );
	}


	/**
	 * Echo out JSONP for the data constructed and processed in this instance.
	 * This is basically the same as {@link json} but wraps the return in a
	 * JSONP callback.
	 *
	 * @param string $callback The callback function name to use. If not given
	 *    or `null`, then `$_GET['callback']` is used (the jQuery default).
	 * @return self Self for chaining.
	 * @throws \Exception JSONP function name validation
	 */
	public function jsonp ( $callback=null )
	{
		if ( ! $callback ) {
			$callback = $_GET['callback'];
		}

		if ( preg_match('/[^a-zA-Z0-9_]/', $callback) ) {
			throw new \Exception("Invalid JSONP callback function name");
		}

		echo $callback.'('.json_encode( $this->_out ).');';
		return $this;
	}


	/**
	 * Add a left join condition to the Editor instance, allowing it to operate
	 * over multiple tables. Multiple `leftJoin()` calls can be made for a
	 * single Editor instance to join multiple tables.
	 *
	 * A left join is the most common type of join that is used with Editor
	 * so this method is provided to make its use very easy to configure. Its
	 * parameters are basically the same as writing an SQL left join statement,
	 * but in this case Editor will handle the create, update and remove
	 * requirements of the join for you:
	 *
	 * * Create - On create Editor will insert the data into the primary table
	 *   and then into the joined tables - selecting the required data for each
	 *   table.
	 * * Edit - On edit Editor will update the main table, and then either
	 *   update the existing rows in the joined table that match the join and
	 *   edit conditions, or insert a new row into the joined table if required.
	 * * Remove - On delete Editor will remove the main row and then loop over
	 *   each of the joined tables and remove the joined data matching the join
	 *   link from the main table.
	 *
	 * Please note that when using join tables, Editor requires that you fully
	 * qualify each field with the field's table name. SQL can result table
	 * names for ambiguous field names, but for Editor to provide its full CRUD
	 * options, the table name must also be given. For example the field
	 * `first_name` in the table `users` would be given as `users.first_name`.
	 *
	 * @param string $table Table name to do a join onto
	 * @param string $field1 Field from the parent table to use as the join link
	 * @param string $operator Join condition (`=`, '<`, etc)
	 * @param string $field2 Field from the child table to use as the join link
	 * @return self Self for chaining.
	 *
	 * @example 
	 *    Simple join:
	 *    <code>
	 *        ->field( 
	 *          Field::inst( 'users.first_name as myField' ),
	 *          Field::inst( 'users.last_name' ),
	 *          Field::inst( 'users.dept_id' ),
	 *          Field::inst( 'dept.name' )
	 *        )
	 *        ->leftJoin( 'dept', 'users.dept_id', '=', 'dept.id' )
	 *        ->process($_POST)
	 *        ->json();
	 *    </code>
	 *
	 *    This is basically the same as the following SQL statement:
	 * 
	 *    <code>
	 *      SELECT users.first_name, users.last_name, user.dept_id, dept.name
	 *      FROM users
	 *      LEFT JOIN dept ON users.dept_id = dept.id
	 *    </code>
	 */
	public function leftJoin ( $table, $field1, $operator, $field2 )
	{
		$this->_leftJoin[] = array(
			"table"    => $table,
			"field1"   => $field1,
			"field2"   => $field2,
			"operator" => $operator
		);

		return $this;
	}


	/**
	 * Add an event listener. The `Editor` class will trigger an number of
	 * events that some action can be taken on.
	 *
	 * @param  [type] $name     Event name
	 * @param  [type] $callback Callback function to execute when the event
	 *     occurs
	 * @return self Self for chaining.
	 */
	public function on ( $name, $callback )
	{
		if ( ! isset( $this->_events[ $name ] ) ) {
			$this->_events[ $name ] = array();
		}

		$this->_events[ $name ][] = $callback;

		return $this;
	}


	/**
	 * Get / set the table name.
	 * 
	 * The table name designated which DB table Editor will use as its data
	 * source for working with the database. Table names can be given with an
	 * alias, which can be used to simplify larger table names. The field
	 * names would also need to reflect the alias, just like an SQL query. For
	 * example: `users as a`.
	 *
	 *  @param string|array $_,... Table names given as a single string, an array of
	 *    strings or multiple string parameters for the function.
	 *  @return string[]|self Array of tables names, or self if used as a setter.
	 */
	public function table ( $_=null )
	{
		if ( $_ !== null && !is_array($_) ) {
			$_ = func_get_args();
		}
		return $this->_getSet( $this->_table, $_, true );
	}


	/**
	 * Get / set transaction support.
	 *
	 * When enabled (which it is by default) Editor will use an SQL transaction
	 * to ensure data integrity while it is performing operations on the table.
	 * This can be optionally disabled using this method, if required by your
	 * database configuration.
	 *  @param boolean $_ Enable (`true`) or disabled (`false`) transactions.
	 *    If not given, then used as a getter.
	 *  @return boolean|self Transactions enabled flag, or self if used as a
	 *    setter.
	 */
	public function transaction ( $_=null )
	{
		return $this->_getSet( $this->_transaction, $_ );
	}


	/**
	 * Get / set the primary key.
	 *
	 * The primary key must be known to Editor so it will know which rows are being
	 * edited / deleted upon those actions. The default value is 'id'.
	 *  @param string $_ Primary key's name. If not given, then used as a getter.
	 *  @return string|self Primary key value if no parameter is given, or
	 *    self if used as a setter.
	 */
	public function pkey ( $_=null )
	{
		return $this->_getSet( $this->_pkey, $_ );
	}


	/**
	 * Process a request from the Editor client-side to get / set data.
	 *  @param array $data Typically $_POST or $_GET as required by what is sent by Editor
	 *  @return self
	 */
	public function process ( $data )
	{
		$this->_processData = $data;
		$this->_formData = isset($data['data']) ? $data['data'] : null;

		if ( $this->_transaction ) {
			$this->_db->transaction();
		}

		try {
			$this->_prepJoin();

			if ( !isset($data['action']) ) {
				/* Get data */
				$this->_out = array_merge( $this->_out, $this->_get( null, $data ) );
			}
			else if ( $data['action'] == "upload" ) {
				/* File upload */
				$this->_upload( $data );
			}
			else if ( $data['action'] == "remove" ) {
				/* Remove rows */
				$this->_remove( $data );
				$this->_fileClean();
			}
			else {
				/* Create or edit row */
				// Pre events so they can occur before the validation
				foreach ($data['data'] as $id => $values) {
					if ( $data['action'] == 'create' ) {
						$this->_trigger( 'preCreate', $values );
					}
					else {
						$id = str_replace( $this->_idPrefix, '', $id );
						$this->_trigger( 'preEdit', $id, $values );
					}
				}

				// Validation
				$valid = $this->validate( $this->_out['fieldErrors'], $data );

				// Global validation - if you want global validation - do it here
				// $this->_out['error'] = "";

				if ( $valid ) {
					foreach ($data['data'] as $id => $values) {
						$d = $data['action'] == "create" ?
							$this->_insert( $values ) :
							$this->_update( $id, $values );

						if ( $d !== null ) {
							$this->_out['data'][] = $d;
						}
					}
				}

				$this->_fileClean();
			}

			if ( $this->_transaction ) {
				$this->_db->commit();
			}
		}
		catch (\Exception $e) {
			// Error feedback
			$this->_out['error'] = $e->getMessage();
			
			if ( $this->_transaction ) {
				$this->_db->rollback();
			}
		}

		// Tidy up the reply
		if ( count( $this->_out['fieldErrors'] ) === 0 ) {
			unset( $this->_out['fieldErrors'] );
		}

		if ( $this->_out['error'] === '' ) {
			unset( $this->_out['error'] );
		}

		if ( count( $this->_out['ipOpts'] ) === 0 ) {
			unset( $this->_out['ipOpts'] );
		}

		return $this;
	}


	/**
	 * Perform validation on a data set.
	 *
	 * Note that validation is performed on data only when the action is
	 * `create` or `edit`. Additionally, validation is performed on the _wire
	 * data_ - i.e. that which is submitted from the client, without formatting.
	 * Any formatting required by `setFormatter` is performed after the data
	 * from the client has been validated.
	 *
	 *  @param &array $errors Output array to which field error information will
	 *      be written. Each element in the array represents a field in an error
	 *      condition. These elements are themselves arrays with two properties
	 *      set; `name` and `status`.
	 *  @param array $data The format data to check
	 *  @return boolean `true` if the data is valid, `false` if not.
	 */
	public function validate ( &$errors, $data )
	{
		// Validation is only performed on create and edit
		if ( $data['action'] != "create" && $data['action'] != "edit" ) {
			return true;
		}

		foreach( $data['data'] as $id => $values ) {
			for ( $i=0 ; $i<count($this->_fields) ; $i++ ) {
				$field = $this->_fields[$i];
				$validation = $field->validate( $values, $this,
					str_replace( $this->idPrefix(), '', $id )
				);

				if ( $validation !== true ) {
					$errors[] = array(
						"name" => $field->name(),
						"status" => $validation
					);
				}
			}

			// MJoin validation
			for ( $i=0 ; $i<count($this->_join) ; $i++ ) {
				$this->_join[$i]->validate( $errors, $this, $values );
			}
		}

		return count( $errors ) > 0 ? false : true;
	}


	/**
	 * Where condition to add to the query used to get data from the database.
	 * 
	 * Can be used in two different ways:
	 * 
	 * * Simple case: `where( field, value, operator )`
	 * * Complex: `where( fn )`
	 *
	 * The simple case is fairly self explanatory, a condition is applied to the
	 * data that looks like `field operator value` (e.g. `name = 'Allan'`). The
	 * complex case allows full control over the query conditions by providing a
	 * closure function that has access to the database Query that Editor is
	 * using, so you can use the `where()`, `or_where()`, `and_where()` and
	 * `where_group()` methods as you require.
	 *
	 * Please be very careful when using this method! If an edit made by a user
	 * using Editor removes the row from the where condition, the result is
	 * undefined (since Editor expects the row to still be available, but the
	 * condition removes it from the result set).
	 * 
	 * @param string|callable $key   Single field name or a closure function
	 * @param string          $value Single field value.
	 * @param string          $op    Condition operator: <, >, = etc
	 * @return string[]|self Where condition array, or self if used as a setter.
	 */
	public function where ( $key=null, $value=null, $op='=' )
	{
		if ( $key === null ) {
			return $this->_where;
		}

		if ( is_callable($key) && is_object($key) ) {
			$this->_where[] = $key;
		}
		else {
			$this->_where[] = array(
				"key"   => $key,
				"value" => $value,
				"op"    => $op
			);
		}

		return $this;
	}


	/**
	 * Get / set if the WHERE conditions should be included in the create and
	 * edit actions.
	 * 
	 *  @param boolean $_ Include (`true`), or not (`false`)
	 *  @return boolean Current value
	 *  @deprecated Note that `whereSet` is now deprecated and replaced with the
	 *    ability to set values for columns on create and edit. The C# libraries
	 *    do not support this option at all.
	 */
	public function whereSet ( $_=null )
	{
		return $this->_getSet( $this->_whereSet, $_ );
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private methods
	 */

	/**
	 * Get an array of objects from the database to be given to DataTables as a
	 * result of an sAjaxSource request, such that DataTables can display the information
	 * from the DB in the table.
	 *
	 *  @param integer $id Primary key value to get an individual row (after create or
	 *    update operations). Gets the full set if not given.
	 *  @param array $http HTTP parameters from GET or POST request (so we can service
	 *    server-side processing requests from DataTables).
	 *  @return array DataTables get information
	 *  @throws \Exception Error on SQL execution
	 *  @private
	 */
	private function _get( $id=null, $http=null )
	{
		$query = $this->_db
			->query('select')
			->table( $this->_table )
			->get( $this->_pkey );

		// Add all fields that we need to get from the database
		foreach ($this->_fields as $field) {
			if ( $field->apply('get') && $field->getValue() === null ) {
				$query->get( $field->dbField() );
			}
		}

		$this->_get_where( $query );
		$this->_perform_left_join( $query );
		$ssp = $this->_ssp_query( $query, $http );

		if ( $id !== null ) {
			$query->where( $this->_pkey, $id );
		}

		$res = $query->exec();
		if ( ! $res ) {
			throw new \Exception('Error executing SQL for data get');
		}

		$out = array();
		while ( $row=$res->fetch() ) {
			$inner = array();
			$inner['DT_RowId'] = $this->_idPrefix . $row[ $this->_pkey ];

			foreach ($this->_fields as $field) {
				if ( $field->apply('get') ) {
					$field->write( $inner, $row );
				}
			}

			$out[] = $inner;
		}

		// Field options
		$options = array();

		foreach ($this->_fields as $field) {
			$opts = $field->optionsExec( $this->_db );

			if ( $opts !== false ) {
				$options[ $field->name() ] = $opts;
			}
		}

		// Row based "joins"
		for ( $i=0 ; $i<count($this->_join) ; $i++ ) {
			$this->_join[$i]->data( $this, $out, $options );
		}

		return array_merge(
			array(
				'data'    => $out,
				'options' => $options,
				'files'   => $this->_fileData()
			),
			$ssp
		);
	}


	/**
	 * Insert a new row in the database
	 *  @private
	 */
	private function _insert( $values )
	{
		// Insert the new row
		$id = $this->_insert_or_update( null, $values );

		// Was the primary key sent and set? Unusual, but it is possible
		$pkeyField = $this->_find_field( $this->_pkey, 'name' );
		if ( $pkeyField && $pkeyField->apply( 'edit', $values ) ) {
			$id = $pkeyField->val( 'set', $values );
		}

		// Join tables
		for ( $i=0 ; $i<count($this->_join) ; $i++ ) {
			$this->_join[$i]->create( $this, $id, $values );
		}

		// Full data set for the created row
		$row = $this->_get( $id );
		$row = count( $row['data'] ) > 0 ?
			$row['data'][0] :
			null;

		$this->_trigger( 'postCreate', $id, $values, $row );

		return $row;
	}


	/**
	 * Update a row in the database
	 *  @param string $id The DOM ID for the row that is being edited.
	 *  @return array Row's data
	 *  @private
	 */
	private function _update( $id, $values )
	{
		$id = str_replace( $this->_idPrefix, '', $id );

		// Update or insert the rows for the parent table and the left joined
		// tables
		$this->_insert_or_update( $id, $values );

		// And the join tables
		for ( $i=0 ; $i<count($this->_join) ; $i++ ) {
			$this->_join[$i]->update( $this, $id, $values );
		}

		// Was the primary key altered as part of the edit? Unusual, but it is
		// possible
		$pkeyField = $this->_find_field( $this->_pkey, 'name' );
		$getId = $pkeyField && $pkeyField->apply( 'edit', $values ) ?
			$pkeyField->val( 'set', $values ) :
			$id;

		// Full data set for the modified row
		$row = $this->_get( $getId );
		$row = count( $row['data'] ) > 0 ?
			$row['data'][0] :
			null;

		$this->_trigger( 'postEdit', $id, $values, $row );

		return $row;
	}


	/**
	 * Delete one or more rows from the database
	 *  @private
	 */
	private function _remove( $data )
	{
		$ids = array();

		// Get the ids to delete from the data source
		foreach ($data['data'] as $idSrc => $rowData) {
			// Strip the ID prefix that the client-side sends back
			$id = str_replace( $this->_idPrefix, "", $idSrc );

			$this->_trigger( 'preRemove', $id, $rowData );
			$ids[] = $id;
		}

		if ( count( $ids ) === 0 ) {
			throw new \Exception('No ids submitted for the delete');
		}

		// Row based joins - remove first as the host row will be removed which
        // is a dependency
		for ( $i=0 ; $i<count($this->_join) ; $i++ ) {
			$this->_join[$i]->remove( $this, $ids );
		}

		// Remove from the left join tables
		for ( $i=0, $ien=count($this->_leftJoin) ; $i<$ien ; $i++ ) {
			$join = $this->_leftJoin[$i];
			$table = $this->_alias( $join['table'], 'orig' );

			// which side of the join refers to the parent table?
			if ( strpos( $join['field1'], $join['table'] ) === 0 ) {
				$parentLink = $join['field2'];
				$childLink = $join['field1'];
			}
			else {
				$parentLink = $join['field1'];
				$childLink = $join['field2'];
			}

			// Only delete on the primary key, since that is what the ids refer
			// to - otherwise we'd be deleting random data!
			if ( $parentLink === $this->_pkey ) {
				$this->_remove_table( $join['table'], $ids, $childLink );
			}
		}

		// Remove from the primary tables
		for ( $i=0, $ien=count($this->_table) ; $i<$ien ; $i++ ) {
			$this->_remove_table( $this->_table[$i], $ids );
		}

		foreach ($data['data'] as $idSrc => $rowData) {
			$id = str_replace( $this->_idPrefix, "", $idSrc );

			$this->_trigger( 'postRemove', $id, $rowData );
		}
	}


	/**
	 * File upload
	 *  @param array $data Upload data
	 *  @throws \Exception File upload name error
	 *  @private
	 */
	private function _upload( $data )
	{
		// Search for upload field in local fields
		$field = $this->_find_field( $data['uploadField'], 'name' );
		$fieldName = '';

		if ( ! $field ) {
			// Perhaps it is in a join instance
			for ( $i=0 ; $i<count($this->_join) ; $i++ ) {
				$join = $this->_join[$i];
				$fields = $join->fields();

				for ( $j=0, $jen=count($fields) ; $j<$jen ; $j++ ) {
					$joinField = $fields[ $j ];
					$name = $join->name().'[].'.$joinField->name();

					if ( $name === $data['uploadField'] ) {
						$field = $joinField;
						$fieldName = $name;
					}
				}
			}
		}
		else {
			$fieldName = $field->name();
		}

		if ( ! $field ) {
			throw new \Exception("Unknown upload field name submitted");
		}

		$upload = $field->upload();
		if ( ! $upload ) {
			throw new \Exception("File uploaded to a field that does not have upload options configured");
		}

		$res = $upload->exec( $this );

		if ( $res === false ) {
			$this->_out['fieldErrors'][] = array(
				"name"   => $fieldName,      // field name can be just the field's
				"status" => $upload->error() // name or a join combination
			);
		}
		else {
			$files = $this->_fileData( $upload->table() );

			$this->_out['files'] = $files;
			$this->_out['upload']['id'] = $res;
		}
	}


	/**
	 * Get information about the files that are detailed in the database for
	 * the fields which have an upload method defined on them.
	 *
	 * @param  string [$limitTable=null] Limit the data gathering to a single
	 *     table only
	 * @return array File information
	 * @private
	 */
	private function _fileData ( $limitTable=null )
	{
		$files = array();

		// The fields in this instance
		$this->_fileDataFields( $files, $this->_fields, $limitTable );
		
		// From joined tables
		for ( $i=0 ; $i<count($this->_join) ; $i++ ) {
			$this->_fileDataFields( $files, $this->_join[$i]->fields(), $limitTable );
		}

		return $files;
	}


	/**
	 * Common file get method for any array of fields
	 * @param  array &$files File output array
	 * @param  Field[] $fields Fields to get file information about
	 * @param  string $limitTable Limit the data gathering to a single table
	 *     only
	 * @private
	 */
	private function _fileDataFields ( &$files, $fields, $limitTable )
	{
		foreach ($fields as $field) {
			$upload = $field->upload();

			if ( $upload ) {
				$table = $upload->table();

				if ( ! $table ) {
					continue;
				}

				if ( $limitTable !== null && $table !== $limitTable ) {
					continue;
				}

				if ( isset( $files[ $table ] ) ) {
					continue;
				}

				$fileData = $upload->data( $this->_db );

				if ( $fileData !== null ) {
					$files[ $table ] = $fileData;
				}
			}
		}
	}

	/**
	 * Run the file clean up
	 *
	 * @private
	 */
	private function _fileClean ()
	{
		foreach ( $this->_fields as $field ) {
			$upload = $field->upload();

			if ( $upload ) {
				$upload->dbCleanExec( $this, $field );
			}
		}

		for ( $i=0 ; $i<count($this->_join) ; $i++ ) {
			foreach ( $this->_join[$i]->fields() as $field ) {
				$upload = $field->upload();

				if ( $upload ) {
					$upload->dbCleanExec( $this, $field );
				}
			}
		}
	}


	/*  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *
	 * Server-side processing methods
	 */

	/**
	 * When server-side processing is being used, modify the query with // the
     * required extra conditions
	 *
	 *  @param \DataTables\Database\Query $query Query instance to apply the SSP commands to
	 *  @param array $http Parameters from HTTP request
	 *  @return array Server-side processing information array
	 *  @private
	 */
	private function _ssp_query ( $query, $http )
	{
		if ( ! isset( $http['draw'] ) ) {
			return array();
		}

		// Add the server-side processing conditions
		$this->_ssp_limit( $query, $http );
		$this->_ssp_sort( $query, $http );
		$this->_ssp_filter( $query, $http );

		// Get the number of rows in the result set
		$ssp_set_count = $this->_db
			->query('select')
			->table( $this->_table )
			->get( 'COUNT('.$this->_pkey.') as cnt' );
		$this->_get_where( $ssp_set_count );
		$this->_ssp_filter( $ssp_set_count, $http );
		$this->_perform_left_join( $ssp_set_count );
		$ssp_set_count = $ssp_set_count->exec()->fetch();

		// Get the number of rows in the full set
		$ssp_full_count = $this->_db
			->query('select')
			->table( $this->_table )
			->get( 'COUNT('.$this->_pkey.') as cnt' );
		$this->_get_where( $ssp_full_count );
		if ( count( $this->_where ) ) { // only needed if there is a where condition
			$this->_perform_left_join( $ssp_full_count );
		}
		$ssp_full_count = $ssp_full_count->exec()->fetch();

		return array(
			"draw" => intval( $http['draw'] ),
			"recordsTotal" => $ssp_full_count['cnt'],
			"recordsFiltered" => $ssp_set_count['cnt']
		);
	}


	/**
	 * Convert a column index to a database field name - used for server-side
	 * processing requests.
	 *  @param array $http HTTP variables (i.e. GET or POST)
	 *  @param int $index Index in the DataTables' submitted data
	 *  @returns string DB field name
	 *  @throws \Exception Unknown fields
	 *  @private
	 */
	private function _ssp_field( $http, $index )
	{
		$name = $http['columns'][$index]['data'];
		$field = $this->_find_field( $name, 'name' );

		if ( ! $field ) {
			// Is it the primary key?
			if ( $name === 'DT_RowId' ) {
				return $this->_pkey;
			}

			throw new \Exception('Unknown field: '.$name .' (index '.$index.')');
		}

		return $field->dbField();
	}


	/**
	 * Sorting requirements to a server-side processing query.
	 *  @param \DataTables\Database\Query $query Query instance to apply sorting to
	 *  @param array $http HTTP variables (i.e. GET or POST)
	 *  @private
	 */
	private function _ssp_sort ( $query, $http )
	{
		for ( $i=0 ; $i<count($http['order']) ; $i++ ) {
			$order = $http['order'][$i];

			$query->order(
				$this->_ssp_field( $http, $order['column'] ) .' '.
				($order['dir']==='asc' ? 'asc' : 'desc')
			);
		}
	}


	/**
	 * Add DataTables' 'where' condition to a server-side processing query. This
	 * works for both global and individual column filtering.
	 *  @param \DataTables\Database\Query $query Query instance to apply the WHERE conditions to
	 *  @param array $http HTTP variables (i.e. GET or POST)
	 *  @private
	 */
	private function _ssp_filter ( $query, $http )
	{
		$that = $this;

		// Global filter
		$fields = $this->_fields;

		// Global search, add a ( ... or ... ) set of filters for each column
		// in the table (not the fields, just the columns submitted)
		if ( $http['search']['value'] ) {
			$query->where( function ($q) use (&$that, &$fields, $http) {
				for ( $i=0 ; $i<count($http['columns']) ; $i++ ) {
					if ( $http['columns'][$i]['searchable'] == 'true' ) {
						$field = $that->_ssp_field( $http, $i );

						if ( $field ) {
							$q->or_where( $field, '%'.$http['search']['value'].'%', 'like' );
						}
					}
				}
			} );
		}

		// if ( $http['search']['value'] ) {
		// 	$words = explode(" ", $http['search']['value']);

		// 	$query->where( function ($q) use (&$that, &$fields, $http, $words) {
		// 		for ( $j=0, $jen=count($words) ; $j<$jen ; $j++ ) {
		// 			if ( $words[$j] ) {
		// 				$q->where_group( true );

		// 				for ( $i=0, $ien=count($http['columns']) ; $i<$ien ; $i++ ) {
		// 					if ( $http['columns'][$i]['searchable'] == 'true' ) {
		// 						$field = $that->_ssp_field( $http, $i );

		// 						$q->or_where( $field, $words[$j].'%', 'like' );
		// 						$q->or_where( $field, '% '.$words[$j].'%', 'like' );
		// 					}
		// 				}

		// 				$q->where_group( false );
		// 			}
		// 		}
		// 	} );
		// }

		// Column filters
		for ( $i=0, $ien=count($http['columns']) ; $i<$ien ; $i++ ) {
			$column = $http['columns'][$i];
			$search = $column['search']['value'];

			if ( $search !== '' && $column['searchable'] == 'true' ) {
				$query->where( $this->_ssp_field( $http, $i ), '%'.$search.'%', 'like' );
			}
		}
	}


	/**
	 * Add a limit / offset to a server-side processing query
	 *  @param \DataTables\Database\Query $query Query instance to apply the offset / limit to
	 *  @param array $http HTTP variables (i.e. GET or POST)
	 *  @private
	 */
	private function _ssp_limit ( $query, $http )
	{
		if ( $http['length'] != -1 ) { // -1 is 'show all' in DataTables
			$query
				->offset( $http['start'] )
				->limit( $http['length'] );
		}
	}


	/*  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *
	 * Internal helper methods
	 */

	/**
	 * Add left join commands for the instance to a query.
	 *
	 *  @param \DataTables\Database\Query $query Query instance to apply the joins to
	 *  @private
	 */
	private function _perform_left_join ( $query )
	{
		if ( count($this->_leftJoin) ) {
			for ( $i=0, $ien=count($this->_leftJoin) ; $i<$ien ; $i++ ) {
				$join = $this->_leftJoin[$i];

				$query->join( $join['table'], $join['field1'].' '.$join['operator'].' '.$join['field2'], 'LEFT' );
			}
		}
	}


	/**
	 * Add local WHERE condition to query
	 *  @param \DataTables\Database\Query $query Query instance to apply the WHERE conditions to
	 *  @private
	 */
	private function _get_where ( $query )
	{
		for ( $i=0 ; $i<count($this->_where) ; $i++ ) {
			if ( is_callable( $this->_where[$i] ) ) {
				$this->_where[$i]( $query );
			}
			else {
				$query->where(
					$this->_where[$i]['key'],
					$this->_where[$i]['value'],
					$this->_where[$i]['op']
				);
			}
		}
	}


	/**
	 * Get a field instance from a known field name
	 *
	 *  @param string $name Field name
	 *  @param string $type Matching name type
	 *  @return Field Field instance
	 *  @private
	 */
	private function _find_field ( $name, $type )
	{
		for ( $i=0, $ien=count($this->_fields) ; $i<$ien ; $i++ ) {
			$field = $this->_fields[ $i ];

			if ( $type === 'name' && $field->name() === $name ) {
				return $field;
			}
			else if ( $type === 'db' && $field->dbField() === $name ) {
				return $field;
			}
		}

		return null;
	}


	/**
	 * Insert or update a row for all main tables and left joined tables.
	 *
	 *  @param int $id ID to use to condition the update. If null is given, the
	 *      first query performed is an insert and the inserted id used as the
	 *      value should there be any subsequent tables to operate on.
	 *  @return \DataTables\Database\Result Result from the query or null if no query
	 *      performed.
	 *  @private
	 */
	private function _insert_or_update ( $id, $values )
	{
		// Loop over all tables in _table, doing the insert or update as needed
		for ( $i=0, $ien=count( $this->_table ) ; $i<$ien ; $i++ ) {
			$res = $this->_insert_or_update_table(
				$this->_table[$i],
				$values,
				$id === null ?
					null :
					array($this->_pkey => $id)
			);

			// If we don't have an id yet, then the first insert will return
			// the id we want
			if ( $id === null ) {
				$id = $res->insertId();
			}
		}

		// And for the left join tables as well
		for ( $i=0, $ien=count( $this->_leftJoin ) ; $i<$ien ; $i++ ) {
			$join = $this->_leftJoin[$i];

			// which side of the join refers to the parent table?
			$joinTable = $this->_alias( $join['table'], 'alias' );
			$tablePart = $this->_part( $join['field1'] );

			if ( $this->_part( $join['field1'], 'db' ) ) {
				$tablePart = $this->_part( $join['field1'], 'db' ).'.'.$tablePart;
			}

			if ( $tablePart === $joinTable ) {
				$parentLink = $join['field2'];
				$childLink = $join['field1'];
			}
			else {
				$parentLink = $join['field1'];
				$childLink = $join['field2'];
			}

			if ( $parentLink === $this->_pkey ) {
				$whereVal = $id;
			}
			else {
				// We need submitted information about the joined data to be
				// submitted as well as the new value. We first check if the
				// host field was submitted
				$field = $this->_find_field( $parentLink, 'db' );

				if ( ! $field || ! $field->apply( 'set', $values ) ) {
					// If not, then check if the child id was submitted
					$field = $this->_find_field( $childLink, 'db' );

					// No data available, so we can't do anything
					if ( ! $field || ! $field->apply( 'set', $values ) ) {
						continue;
					}
				}

				$whereVal = $field->val('set', $values);
			}

			$whereName = $this->_part( $childLink, 'field' );

			$this->_insert_or_update_table(
				$join['table'],
				$values,
				array( $whereName => $whereVal )
			);
		}

		return $id;
	}


	/**
	 * Insert or update a row in a single database table, based on the data
	 * given and the fields configured for the instance.
	 *
	 * The function will find the fields which are required for this specific
	 * table, based on the names of fields and use only the appropriate data for
	 * this table. Therefore the full submitted data set can be passed in.
	 *
	 *  @param string $table Database table name to use (can include an alias)
	 *  @param array $where Update condition
	 *  @return \DataTables\Database\Result Result from the query or null if no query
	 *      performed.
	 *  @throws \Exception Where set error
	 *  @private
	 */
	private function _insert_or_update_table ( $table, $values, $where=null )
	{
		$set = array();
		$action = ($where === null) ? 'create' : 'edit';
		$tableAlias = $this->_alias( $table, 'alias' );

		for ( $i=0 ; $i<count($this->_fields) ; $i++ ) {
			$field = $this->_fields[$i];
			$tablePart = $this->_part( $field->dbField() );

			if ( $this->_part( $field->dbField(), 'db' ) ) {
				$tablePart = $this->_part( $field->dbField(), 'db' ).'.'.$tablePart;
			}

			// Does this field apply to this table (only check when a join is
			// being used)
			if ( count($this->_leftJoin) && $tablePart !== $tableAlias ) {
				continue;
			}

			// Check if this field should be set, based on options and
			// submitted data
			if ( ! $field->apply( $action, $values ) ) {
				continue;
			}

			// Some db's (specifically postgres) don't like having the table
			// name prefixing the column name. Todo: it might be nicer to have
			// the db layer abstract this out?
			$fieldPart = $this->_part( $field->dbField(), 'field' );
			$set[ $fieldPart ] = $field->val( 'set', $values );
		}

		// Add where fields if setting where values and required for this
		// table
		// Note that `whereSet` is now deprecated
		if ( $this->_whereSet ) {
			for ( $j=0, $jen=count($this->_where) ; $j<$jen ; $j++ ) {
				$cond = $this->_where[$j];

				if ( ! is_callable( $cond ) ) {
					// Make sure the value wasn't in the submitted data set,
					// otherwise we would be overwriting it
					if ( ! isset( $set[ $cond['key'] ] ) )
					{
						$whereTablePart = $this->_part( $cond['key'], 'table' );

						// No table part on the where condition to match against
						// or table operating on matches table part from cond.
						if ( ! $whereTablePart || $tableAlias == $whereTablePart ) {
							$set[ $cond['key'] ] = $cond['value'];
						}
					}
					else {
						throw new \Exception( 'Where condition used as a setter, '.
							'but value submitted for field: '.$cond['key']
						);
					}
				}
			}
		}

		// If nothing to do, then do nothing!
		if ( ! count( $set ) ) {
			return null;
		}

		// Insert or update
		if ( $action === 'create' ) {
			return $this->_db->insert( $table, $set );
		}
		else {
			return $this->_db->push( $table, $set, $where );
		}
	}


	/**
	 * Delete one or more rows from the database for an individual table
	 *
	 * @param string $table Database table name to use
	 * @param array $ids Array of ids to remove
	 * @param string $pkey Database column name to match the ids on for the
	 *   delete condition. If not given the instance's base primary key is
	 *   used.
	 * @private
	 */
	private function _remove_table ( $table, $ids, $pkey=null )
	{
		if ( $pkey === null ) {
			$pkey = $this->_pkey;
		}

		// Check there is a field which has a set option for this table
		$count = 0;

		foreach ($this->_fields as $field) {
			if ( strpos( $field->dbField(), '.') === false || (
					$this->_part( $field->dbField(), 'table' ) === $table &&
					$field->set() !== Field::SET_NONE
				)
			) {
				$count++;
			}
		}

		if ( $count > 0 ) {
			$this->_db
				->query( 'delete' )
				->table( $table )
				->or_where( $pkey, $ids )
				->exec();
		}
	}


	/**
	 * Check the validity of the set options if  we are doing a join, since
	 * there are some conditions for this state. Will throw an error if not
	 * valid.
	 *
	 *  @private
	 */
	private function _prepJoin ()
	{
		if ( count( $this->_leftJoin ) === 0 ) {
			return;
		}

		// Check if the primary key has a table identifier - if not - add one
		if ( strpos( $this->_pkey, '.' ) === false ) {
			$this->_pkey = $this->_alias( $this->_table[0], 'alias' ).'.'.$this->_pkey;
		}

		// Check that all fields have a table selector, otherwise, we'd need to
		// know the structure of the tables, to know which fields belong in
		// which. This extra requirement on the fields removes that
		for ( $i=0, $ien=count($this->_fields) ; $i<$ien ; $i++ ) {
			$field = $this->_fields[$i];
			$name = $field->dbField();

			if ( strpos( $name, '.' ) === false ) {
				throw new \Exception( 'Table part of the field "'.$name.'" was not found. '.
					'In Editor instances that use a join, all fields must have the '.
					'database table set explicitly.'
				);
			}
		}
	}


	/**
	 * Get one side or the other of an aliased SQL field name.
	 *
	 *  @param string $name SQL field
	 *  @param string $type Which part to get: `alias` (default) or `orig`.
	 *  @returns string Alias
	 *  @private
	 */
	private function _alias ( $name, $type='alias' )
	{
		if ( stripos( $name, ' as ' ) !== false ) {
			$a = preg_split( '/ as /i', $name );
			return $type === 'alias' ?
				$a[1] :
				$a[0];
		}

		return $name;
	}


	/**
	 * Get part of an SQL field definition regardless of how deeply defined it
	 * is
	 *
	 *  @param string $name SQL field
	 *  @param string $type Which part to get: `table` (default) or `db` or
	 *      `column`
	 *  @return string Part name
	 *  @private
	 */
	private function _part ( $name, $type='table' )
	{
		$db = null;
		$table = null;
		$column = null;

		if ( strpos( $name, '.' ) !== false ) {
			$a = explode( '.', $name );

			if ( count($a) === 3 ) {
				$db = $a[0];
				$table = $a[1];
				$column = $a[2];
			}
			else if ( count($a) === 2 ) {
				$table = $a[0];
				$column = $a[1];
			}
		}
		else {
			$column = $name;
		}

		if ( $type === 'db' ) {
			return $db;
		}
		else if ( $type === 'table' ) {
			return $table;
		}
		return $column;
	}


	/**
	 * Trigger an event
	 * 
	 * @private
	 */
	private function _trigger ()
	{
		$args = func_get_args();
		$eventName = array_shift( $args );
		array_unshift( $args, $this );

		if ( ! isset( $this->_events[ $eventName ] ) ) {
			return;
		}

		$events = $this->_events[ $eventName ];

		for ( $i=0, $ien=count($events) ; $i<$ien ; $i++ ) {
			call_user_func_array( $events[$i], $args );
		}
	}
}

