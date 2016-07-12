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

namespace DataTables\Database;
if (!defined('DATATABLES')) exit();

use
	DataTables,
	DataTables\Database,
	DataTables\Database\Query,
	DataTables\Database\Result;


//
// This is a stub class that a driver must extend and complete
//

/**
 * Perform an individual query on the database.
 * 
 * The typical pattern for using this class is through the {@link
 * \DataTables\Database::query} method (and it's 'select', etc short-cuts).
 * Typically it would not be initialised directly.
 *
 * Note that this is a stub class that a driver will extend and complete as
 * required for individual database types. Individual drivers could add
 * additional methods, but this is discouraged to ensure that the API is the
 * same for all database types.
 */
class Query {
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Query instance constructor.
	 *
	 * Note that typically instances of this class will be automatically created
	 * through the {@link \DataTables\Database::query} method.
	 *  @param Database        $db    Database instance
	 *  @param string          $type  Query type - 'select', 'insert', 'update' or 'delete'
	 *  @param string|string[] $table Tables to operate on - see {@link table}.
	 */
	public function __construct( $db, $type, $table=null )
	{
		$this->_dbcon = $db;
		$this->_type = $type;
		$this->table( $table );
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */

	/**
	 * @var string Driver to use
	 * @internal
	 */
	protected $_type = "";

	/**
	 * @var resource Database connection
	 * @internal
	 */
	protected $_dbcon = null;

	/**
	 * @var array
	 * @internal
	 */
	protected $_table = array();

	/**
	 * @var array
	 * @internal
	 */
	protected $_field = array();

	/**
	 * @var array
	 * @internal
	 */
	protected $_bindings = array();

	/**
	 * @var array
	 * @internal
	 */
	protected $_where = array();

	/**
	 * @var array
	 * @internal
	 */
	protected $_join = array();

	/**
	 * @var array
	 * @internal
	 */
	protected $_order = array();

	/**
	 * @var array
	 * @internal
	 */
	protected $_noBind = array();

	/**
	 * @var int
	 * @internal
	 */
	protected $_limit = null;

	/**
	 * @var int
	 * @internal
	 */
	protected $_offset = null;

	/**
	 * @var string
	 * @internal
	 */
	protected $_distinct = false;

	/**
	 * @var string
	 * @internal
	 */
	protected $_identifier_limiter = '`';

	/**
	 * @var string
	 * @internal
	 */
	protected $_field_quote = '\'';



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Static methods
	 */

	/**
	 * Commit a transaction.
	 *  @param \PDO $dbh The Database handle (typically a PDO object, but not always).
	 */
	public static function commit ( $dbh )
	{
		$dbh->commit();
	}

	/**
	 * Database connection - override by the database driver.
	 *  @param string|array $user User name or all parameters in an array
	 *  @param string $pass Password
	 *  @param string $host Host name
	 *  @param string $db   Database name
	 *  @return Query
	 */
	public static function connect ( $user, $pass='', $host='', $port='', $db='', $dsn='' )
	{
		return false;
	}


	/**
	 * Start a database transaction
	 *  @param \PDO $dbh The Database handle (typically a PDO object, but not always).
	 */
	public static function transaction ( $dbh )
	{
		$dbh->beginTransaction();
	}


	/**
	 * Rollback the database state to the start of the transaction.
	 *  @param \PDO $dbh The Database handle (typically a PDO object, but not always).
	 */
	public static function rollback ( $dbh )
	{
		$dbh->rollBack();
	}


	/**
	 * Common helper for the drivers to handle a PDO DSN postfix
	 *  @param string $dsn DSN postfix to use
	 *  @return Query
	 *  @internal
	 */
	static function dsnPostfix ( $dsn )
	{
		if ( ! $dsn ) {
			return '';
		}

		// Add a DSN field separator if not given
		if ( strpos( $dsn, ';' ) !== 0 ) {
			return ';'.$dsn;
		}

		return $dsn;
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Safely bind an input value to a parameter. This is evaluated when the
	 * query is executed. This allows user input to be safely executed without
	 * risk of an SQL injection attack.
	 *
	 * @param  [type] $name  Parameter name. This should include a leading colon
	 * @param  [type] $value Value to bind
	 * @param  [type] $type  Data type. See the PHP PDO documentation:
	 *   http://php.net/manual/en/pdo.constants.php
	 * @return Query
	 */
	public function bind ( $name, $value, $type=null )
	{
		$this->_bindings[] = array(
			"name"  => $this->_safe_bind( $name ),
			"value" => $value,
			"type"  => $type
		);

		return $this;
	}

	/**
	 * Set a distinct flag for a `select` query. Note that this has no effect on
	 * any of the other query types.
	 *  @param boolean $dis Optional
	 *  @return Query
	 */
	public function distinct ( $dis )
	{
		$this->_distinct = $dis;
		return $this;
	}

	/**
	 * Execute the query.
	 *  @param string $sql SQL string to execute (only if _type is 'raw').
	 *  @return Result
	 */
	public function exec ( $sql=null )
	{
		$type = strtolower( $this->_type );

		if ( $type === 'select' ) {
			return $this->_select();
		}
		else if ( $type === 'insert' ) {
			return $this->_insert();
		}
		else if ( $type === 'update' ) {
			return $this->_update();
		}
		else if ( $type === 'delete' ) {
			return $this->_delete();
		}
		else if ( $type === 'raw' ) {
			return $this->_raw( $sql );
		}
		
		throw new \Exception("Unknown database command or not supported: ".$type, 1);
	}


	/**
	 * Get fields.
	 *  @param string|string[] $get,... Fields to get - can be specified as
	 *    individual fields, an array of fields, a string of comma separated
	 *    fields or any combination of those.
	 *  @return self
	 */
	public function get ( $get )
	{
		if ( $get === null ) {
			return $this;
		}

		$args = func_get_args();

		for ( $i=0 ; $i<count($args) ; $i++ ) {
			// If argument is an array then we loop over and add each using a
			// recursive call
			if ( is_array( $args[$i] ) ) {
				for ( $j=0 ; $j<count($args[$i]) ; $j++ ) {
					$this->get( $args[$i][$j] );
				}
			}
			else {
				// String argument so split into pieces and add
				// TODO - This limits the ability to use functions. Need to
				// parse the string so it will split when not in a function
				$fields = explode(",", $args[$i]);

				for ( $j=0 ; $j<count($fields) ; $j++ ) {
					$this->_field[] = trim( $fields[$j] );
				}
			}
		}

		return $this;
	}


	/**
	 * Perform a JOIN operation
	 *  @param string $table     Table name to do the JOIN on
	 *  @param string $condition JOIN condition
	 *  @param string $type      JOIN type
	 *  @return self
	 */
	public function join ( $table, $condition, $type='' )
	{
		// Tidy and check we know what the join type is
		if ($type !== '') {
			$type = strtoupper(trim($type));

			if ( ! in_array($type, array('LEFT', 'RIGHT', 'INNER', 'OUTER', 'LEFT OUTER', 'RIGHT OUTER'))) {
				$type = '';
			}
		}

		// Protect the identifiers
		if (preg_match('/([\w\.]+)([\W\s]+)(.+)/', $condition, $match))
		{
			$match[1] = $this->_protect_identifiers( $match[1] );
			$match[3] = $this->_protect_identifiers( $match[3] );

			$condition = $match[1].$match[2].$match[3];
		}

		$this->_join[] = $type .' JOIN '. $this->_protect_identifiers($table) .' ON '. $condition .' ';

		return $this;
	}


	/**
	 * Limit the result set to a certain size.
	 *  @param int $lim The number of records to limit the result to.
	 *  @return self
	 */
	public function limit ( $lim )
	{
		$this->_limit = $lim;

		return $this;
	}


	/**
	 * Set table(s) to perform the query on.
	 *  @param string|string[] $table,... Table(s) to use - can be specified as
	 *    individual names, an array of names, a string of comma separated
	 *    names or any combination of those.
	 *  @return self
	 */
	public function table ( $table )
	{
		if ( $table === null ) {
			return $this;
		}

		if ( is_array($table) ) {
			// Array so loop internally
			for ( $i=0 ; $i<count($table) ; $i++ ) {
				$this->table( $table[$i] );
			}
		}
		else {
			// String based, explode for multiple tables
			$tables = explode(",", $table);

			for ( $i=0 ; $i<count($tables) ; $i++ ) {
				$this->_table[] = $this->_protect_identifiers( trim($tables[$i]) );
			}
		}

		return $this;
	}


	/**
	 * Offset the return set by a given number of records (useful for paging).
	 *  @param int $off The number of records to offset the result by.
	 *  @return self
	 */
	public function offset ( $off )
	{
		$this->_offset = $off;

		return $this;
	}


	/**
	 * Order by
	 *  @param string|string[] $order Columns and direction to order by - can
	 *    be specified as individual names, an array of names, a string of comma 
	 *    separated names or any combination of those.
	 *  @return self
	 */
	public function order ( $order )
	{
		if ( $order === null ) {
			return $this;
		}

		if ( !is_array($order) ) {
			$order = explode(",", $order);
		}

		for ( $i=0 ; $i<count($order) ; $i++ ) {
			// Simplify the white-space
			$order[$i] = trim( preg_replace('/[\t ]+/', ' ', $order[$i]) );

			// Find the identifier so we don't escape that
			if ( strpos($order[$i], ' ') !== false ) {
				$direction = strstr($order[$i], ' ');
				$identifier = substr($order[$i], 0, - strlen($direction));
			}
			else {
				$direction = '';
				$identifier = $order[$i];
			}

			$this->_order[] = $this->_protect_identifiers( $identifier ).' '.$direction;
		}

		return $this;
	}


	/**
	 * Set fields to a given value.
	 *
	 * Can be used in two different ways, as set( field, value ) or as an array of
	 * fields to set: set( array( 'fieldName' => 'value', ...) );
	 *  @param string|string[] $set Can be given as a single string, when then $val
	 *    must be set, or as an array of key/value pairs to be set.
	 *  @param string          $val When $set is given as a simple string, $set is the field
	 *    name and this is the field's value.
	 *  @param boolean         $bind Should the value be bound or not
	 *  @return self
	 */
	public function set ( $set, $val=null, $bind=true )
	{
		if ( $set === null ) {
			return $this;
		}

		if ( !is_array($set) ) {
			$set = array( $set => $val );
		}

		foreach ($set as $key => $value) {
			$this->_field[] = $key;

			if ( $bind ) {
				$this->bind( ':'.$key, $value );
			}
			else {
				$this->_noBind[$key] = $value;
			}
		}

		return $this;
	}


	/**
	 * Where query - multiple conditions are bound as ANDs.
	 *
	 * Can be used in two different ways, as where( field, value ) or as an array of
	 * conditions to use: where( array('fieldName', ...), array('value', ...) );
	 *  @param string|string[]|callable $key   Single field name, or an array of field names.
	 *    If given as a function (i.e. a closure), the function is called, passing the
	 *    query itself in as the only parameter, so the function can add extra conditions
	 *    with parentheses around the additional parameters.
	 *  @param string|string[]          $value Single field value, or an array of
	 *    values. Can be null to search for `IS NULL` or `IS NOT NULL` (depending
	 *    on the value of `$op` which should be `=` or `!=`.
	 *  @param string                   $op    Condition operator: <, >, = etc
	 *  @param boolean                  $bind  Escape the value (true, default) or not (false).
	 *  @return self
	 *
	 *  @example
	 *     The following will produce
	 *     `'WHERE name='allan' AND ( location='Scotland' OR location='Canada' )`:
	 *
	 *     <code>
	 *       $query
	 *         ->where( 'name', 'allan' )
	 *         ->where( function ($q) {
	 *           $q->where( 'location', 'Scotland' );
	 *           $q->where( 'location', 'Canada' );
	 *         } );
	 *     </code>
	 */
	public function where ( $key, $value=null, $op="=", $bind=true )
	{
		if ( $key === null ) {
			return $this;
		}
		else if ( is_callable($key) && is_object($key) ) { // is a closure
			$this->_where_group( true, 'AND' );
			$key( $this );
			$this->_where_group( false, 'OR' );
		}
		else {
			if ( !is_array($key) && is_array($value) ) {
				for ( $i=0 ; $i<count($value) ; $i++ ) {
					$this->where( $key, $value[$i], $op, $bind );
				}
				return $this;
			}

			$this->_where( $key, $value, 'AND ', $op, $bind );
		}

		return $this;
	}


	/**
	 * Add addition where conditions to the query with an AND operator. An alias
	 * of `where` for naming consistency.
	 *
	 * Can be used in two different ways, as where( field, value ) or as an array of
	 * conditions to use: where( array('fieldName', ...), array('value', ...) );
	 *  @param string|string[]|callable $key   Single field name, or an array of field names.
	 *    If given as a function (i.e. a closure), the function is called, passing the
	 *    query itself in as the only parameter, so the function can add extra conditions
	 *    with parentheses around the additional parameters.
	 *  @param string|string[]          $value Single field value, or an array of
	 *    values. Can be null to search for `IS NULL` or `IS NOT NULL` (depending
	 *    on the value of `$op` which should be `=` or `!=`.
	 *  @param string                   $op    Condition operator: <, >, = etc
	 *  @param boolean                  $bind  Escape the value (true, default) or not (false).
	 *  @return self
	 */
	public function and_where ( $key, $value=null, $op="=", $bind=true )
	{
		return $this->where( $key, $value, $op, $bind );
	}


	/**
	 * Add addition where conditions to the query with an OR operator.
	 *
	 * Can be used in two different ways, as where( field, value ) or as an array of
	 * conditions to use: where( array('fieldName', ...), array('value', ...) );
	 *  @param string|string[]|callable $key   Single field name, or an array of field names.
	 *    If given as a function (i.e. a closure), the function is called, passing the
	 *    query itself in as the only parameter, so the function can add extra conditions
	 *    with parentheses around the additional parameters.
	 *  @param string|string[]          $value Single field value, or an array of
	 *    values. Can be null to search for `IS NULL` or `IS NOT NULL` (depending
	 *    on the value of `$op` which should be `=` or `!=`.
	 *  @param string                   $op    Condition operator: <, >, = etc
	 *  @param boolean                  $bind  Escape the value (true, default) or not (false).
	 *  @return self
	 */
	public function or_where ( $key, $value=null, $op="=", $bind=true )
	{
		if ( $key === null ) {
			return $this;
		}
		else if ( is_callable($key) ) {
			$this->_where_group( true, 'OR' );
			$key( $this );
			$this->_where_group( false, 'OR' );
		}
		else {
			if ( !is_array($key) && is_array($value) ) {
				for ( $i=0 ; $i<count($value) ; $i++ ) {
					$this->or_where( $key, $value[$i], $op, $bind );
				}
				return $this;
			}

			$this->_where( $key, $value, 'OR ', $op, $bind );
		}

		return $this;
	}


	/**
	 * Provide grouping for WHERE conditions. Calling this function with `true`
	 * as the first parameter will open a bracket, and `false` will then close
	 * it.
	 *
	 *  @param boolean $inOut `true` to open brackets, `false` to close
	 *  @param string  $op    Conditional operator to use to join to the
	 *      preceding condition. Default `AND`.
	 *  @return self
	 */
	public function where_group ( $inOut, $op='AND' )
	{
		$this->_where_group( $inOut, $op );

		return $this;
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Protected methods
	 */

	/**
	 * Create a comma separated field list
	 * @param bool $addAlias Flag to add an alias
	 * @return string
	 * @internal
	 */
	protected function _build_field( $addAlias=false )
	{
		$a = array();

		for ( $i=0 ; $i<count($this->_field) ; $i++ ) {
			$field = $this->_field[$i];

			// Keep the name when referring to a table
			if ( $addAlias && strpos($field, ' as ') !== false && $field !== '*' ) {
				$split = explode(' as ', $field);
				$a[] = $this->_protect_identifiers( $split[0] ).' as '.
					$this->_field_quote. $split[1] .$this->_field_quote;
			}
			else if ( $addAlias && strpos($field, ' as ') === false && $field !== '*' ) {
				$a[] = $this->_protect_identifiers( $field ).' as '.
					$this->_field_quote. $field .$this->_field_quote;
			}
			else {
				$a[] = $this->_protect_identifiers( $field );
			}
		}

		return ' '.implode(', ', $a).' ';
	}

	/**
	 * Create a JOIN statement list
	 *  @return string
	 *  @internal
	 */
	protected function _build_join()
	{
		return implode(' ', $this->_join);
	}

	/**
	 * Create the LIMIT / OFFSET string
	 *
	 * MySQL and Postgres stylee - anything else can have the driver override
	 *  @return string
	 *  @internal
	 */
	protected function _build_limit()
	{
		$out = '';
		
		if ( $this->_limit ) {
			$out .= ' LIMIT '.$this->_limit;
		}

		if ( $this->_offset ) {
			$out .= ' OFFSET '.$this->_offset;
		}

		return $out;
	}

	/**
	 * Create the ORDER BY string
	 *  @return string
	 *  @internal
	 */
	protected function _build_order()
	{
		if ( count( $this->_order ) > 0 ) {
			return ' ORDER BY '.implode(', ', $this->_order).' ';
		}
		return '';
	}

	/**
	 * Create a set list
	 *  @return string
	 *  @internal
	 */
	protected function _build_set()
	{
		$a = array();

		for ( $i=0 ; $i<count($this->_field) ; $i++ ) {
			$field = $this->_field[$i];

			if ( isset( $this->_noBind[ $field ] ) ) {
				$a[] = $this->_protect_identifiers( $field ) .' = '. $this->_noBind[ $field ];
			}
			else {
				$a[] = $this->_protect_identifiers( $field ) .' = :'. $this->_safe_bind( $field );
			}
		}

		return ' '.implode(', ', $a).' ';
	}

	/**
	 * Create the TABLE list
	 *  @return string
	 *  @internal
	 */
	protected function _build_table()
	{
		return ' '.implode(', ', $this->_table).' ';
	}

	/**
	 * Create a bind field value list
	 *  @return string
	 *  @internal
	 */
	protected function _build_value()
	{
		$a = array();

		for ( $i=0, $ien=count($this->_field) ; $i<$ien ; $i++ ) {
			$a[] = ' :'.$this->_safe_bind( $this->_field[$i] );
		}

		return ' '.implode(', ', $a).' ';
	}

	/**
	 * Create the WHERE statement
	 *  @return string
	 *  @internal
	 */
	protected function _build_where()
	{
		if ( count($this->_where) === 0 ) {
			return "";
		}

		$condition = "WHERE ";

		for ( $i=0 ; $i<count($this->_where) ; $i++ ) {
			if ( $i === 0 ) {
				// Nothing (simplifies the logic!)
			}
			else if ( $this->_where[$i]['group'] === ')' ) {
				// Nothing
			}
			else if ( $this->_where[$i-1]['group'] === '(' ) {
				// Nothing
			}
			else {
				$condition .= $this->_where[$i]['operator'];
			}

			if ( $this->_where[$i]['group'] !== null ) {
				$condition .= $this->_where[$i]['group'];
			}
			else {
				$condition .= $this->_where[$i]['query'] .' ';
			}
		}

		return $condition;
	}

	/**
	 * Create a DELETE statement
	 *  @return Result
	 *  @internal
	 */
	protected function _delete()
	{
		$this->_prepare( 
			'DELETE FROM '
			.$this->_build_table()
			.$this->_build_where()
		);

		return $this->_exec();
	}

	/**
	 * Execute the query. Provided by the driver
	 *  @return Result
	 *  @internal
	 */
	protected function _exec()
	{}

	/**
	 * Create an INSERT statement
	 *  @return Result
	 *  @internal
	 */
	protected function _insert()
	{
		$this->_prepare( 
			'INSERT INTO '
				.$this->_build_table().' ('
					.$this->_build_field()
				.') '
			.'VALUES ('
				.$this->_build_value()
			.')'
		);

		return $this->_exec();
	}

	/**
	 * Prepare the SQL query by populating the bound variables.
	 * Provided by the driver
	 *  @return void
	 *  @internal
	 */
	protected function _prepare( $sql )
	{}

	/**
	 * Protect field names
	 * @param string $identifier String to be protected
	 * @return string
	 * @internal
	 */
	protected function _protect_identifiers( $identifier )
	{
		$idl = $this->_identifier_limiter;

		// No escaping character
		if ( $idl === '' ) {
			return $identifier;
		}

		// Dealing with a function or other expression? Just return immediately
		if (strpos($identifier, '(') !== FALSE || strpos($identifier, '*') !== FALSE || strpos($identifier, ' ') !== FALSE)
		{
			return $identifier;
		}

		// Going to be operating on the spaces in strings, to simplify the white-space
		$identifier = preg_replace('/[\t ]+/', ' ', $identifier);

		// Find if our identifier has an alias, so we don't escape that
		if ( strpos($identifier, ' as ') !== false ) {
			$alias = strstr($identifier, ' as ');
			$identifier = substr($identifier, 0, - strlen($alias));
		}
		else {
			$alias = '';
		}

		$a = explode('.', $identifier);
		return $idl . implode($idl.'.'.$idl, $a) . $idl . $alias;
	}

	/**
	 * Passed in SQL statement
	 *  @return Result
	 *  @internal
	 */
	protected function _raw( $sql )
	{
		$this->_prepare( $sql );

		return $this->_exec();
	}

	/**
	 * The characters that can be used for the PDO bindValue name are quite
	 * limited (`[a-zA-Z0-9_]+`). We need to abstract this out to allow slightly
	 * more complex expressions including dots for easy aliasing
	 * @param string $name Field name
	 * @return string
	 * @internal
	 */
	protected function _safe_bind ( $name )
	{
		$name = str_replace('.', '_1_', $name);
		$name = str_replace('-', '_2_', $name);

		return $name;
	}

	/**
	 * Create a SELECT statement
	 *  @return Result
	 *  @internal
	 */
	protected function _select()
	{
		$this->_prepare( 
			'SELECT '.($this->_distinct ? 'DISTINCT ' : '')
			.$this->_build_field( true )
			.'FROM '.$this->_build_table()
			.$this->_build_join()
			.$this->_build_where()
			.$this->_build_order()
			.$this->_build_limit()
		);

		return $this->_exec();
	}

	/**
	 * Create an UPDATE statement
	 *  @return Result
	 *  @internal
	 */
	protected function _update()
	{
		$this->_prepare( 
			'UPDATE '
			.$this->_build_table()
			.'SET '.$this->_build_set()
			.$this->_build_where()
		);

		return $this->_exec();
	}

	/**
	 * Add an individual where condition to the query.
	 * @internal
	 * @param $where
	 * @param null $value
	 * @param string $type
	 * @param string $op
	 * @param bool $bind
	 */
	protected function _where ( $where, $value=null, $type='AND ', $op="=", $bind=true )
	{
		$idl = $this->_identifier_limiter;

		if ( $where === null ) {
			return;
		}
		else if ( !is_array($where) ) {
			$where = array( $where => $value );
		}

		foreach ($where as $key => $value) {
			$i = count( $this->_where );

			if ( $value === null ) {
				// Null query
				$this->_where[] = array(
					'operator' => $type,
					'group'    => null,
					'field'    => $this->_protect_identifiers($key),
					'query'    => $this->_protect_identifiers($key) .( $op === '=' ?
						' IS NULL' :
						' IS NOT NULL')
				);
			}
			else if ( $bind ) {
				// Binding condition (i.e. escape data)
				$this->_where[] = array(
					'operator' => $type,
					'group'    => null,
					'field'    => $this->_protect_identifiers($key),
					'query'    => $this->_protect_identifiers($key) .' '.$op.' '.$this->_safe_bind(':where_'.$i)
				);
				$this->bind( ':where_'.$i, $value );
			}
			else {
				// Non-binding condition
				$this->_where[] = array(
					'operator' => $type,
					'group'    => null,
					'field'    => null,
					'query'    => $this->_protect_identifiers($key) .' '. $op .' '. $this->_protect_identifiers($value)
				);
			}
		}
	}

	/**
	 * Add parentheses to a where condition
	 *  @return string
	 *  @internal
	 */
	protected function _where_group ( $inOut, $op )
	{
		$this->_where[] = array(
			"group"    => $inOut ? '(' : ')',
			"operator" => $op
		);
	}
};


