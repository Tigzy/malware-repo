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

use
	DataTables,
	DataTables\Editor,
	DataTables\Editor\Join;


/**
 * Field definitions for the DataTables Editor.
 *
 * Each Database column that is used with Editor can be described with this 
 * Field method (both for Editor and Join instances). It basically tells
 * Editor what table column to use, how to format the data and if you want
 * to read and/or write this column.
 *
 * Field instances are used with the {@link Editor::field} and 
 * {@link Join::field} methods to describe what fields should be interacted
 * with by the editable table.
 *
 *  @example
 *    Simply get a column with the name "city". No validation is performed.
 *    <code>
 *      Field::inst( 'city' )
 *    </code>
 *
 *  @example
 *    Get a column with the name "first_name" - when edited a value must
 *    be given due to the "required" validation from the {@link Validate} class.
 *    <code>
 *      Field::inst( 'first_name' )->validator( 'Validate::required' )
 *    </code>
 *
 *  @example
 *    Working with a date field, which is validated, and also has *get* and
 *    *set* formatters.
 *    <code>
 *      Field::inst( 'registered_date' )
 *          ->validator( 'Validate::dateFormat', 'D, d M y' )
 *          ->getFormatter( 'Format::date_sql_to_format', 'D, d M y' )
 *          ->setFormatter( 'Format::date_format_to_sql', 'D, d M y' )
 *    </code>
 *
 *  @example
 *    Using an alias in the first parameter
 *    <code>
 *      Field::inst( 'name.first as first_name' )
 *    </code>
 */
class Field extends DataTables\Ext {
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Statics
	 */
	
	/** Set option flag (`set()`) - do not set data */
	const SET_NONE = 'none';

	/** Set option flag (`set()`) - write to database on both create and edit */
	const SET_BOTH = 'both';

	/** Set option flag (`set()`) - write to database only on create */
	const SET_CREATE = 'create';

	/** Set option flag (`set()`) - write to database only on edit */
	const SET_EDIT = 'edit';


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Field instance constructor.
	 *  @param string $dbField Name of the database column
	 *  @param string $name Name to use in the JSON output from Editor and the
	 *    HTTP submit from the client-side when editing. If not given then the
	 *    $dbField name is used.
	 */
	function __construct( $dbField=null, $name=null )
	{
		if ( $dbField !== null && $name === null ) {
			// Allow just a single parameter to be passed - each can be 
			// overridden if needed later using the API.
			$this->name( $dbField );
			$this->dbField( $dbField );
		}
		else {
			$this->name( $name );
			$this->dbField( $dbField );
		}
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private parameters
	 */

	/** @var string */
	private $_dbField = null;

	/** @var boolean */
	private $_get = true;

	/** @var mixed */
	private $_getFormatter = null;

	/** @var mixed */
	private $_getFormatterOpts = null;

	/** @var mixed */
	private $_getValue = null;

	/** @var string|callable */
	private $_optsTable = null;

	/** @var string */
	private $_optsValue = null;

	/** @var string */
	private $_optsLabel = null;

	/** @var callable */
	private $_optsCond = null;

	/** @var callable */
	private $_optsFormat = null;

	/** @var string */
	private $_name = null;

	/** @var string */
	private $_set = Field::SET_BOTH;

	/** @var mixed */
	private $_setFormatter = null;

	/** @var mixed */
	private $_setFormatterOpts = null;

	/** @var mixed */
	private $_setValue = null;

	/** @var mixed */
	private $_validator = array();

	/** @var Upload */
	private $_upload = null;

	/** @var callable */
	private $_xss = null;

	/** @var boolean */
	private $_xssFormat = true;



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */


	/**
	 * Get / set the DB field name.
	 * 
	 * Note that when used as a setter, an alias can be given for the field
	 * using the SQL `as` keyword - for example: `firstName as name`. In this
	 * situation the dbField is set to the field name before the `as`, and the
	 * field's name (`name()`) is set to the name after the ` as `.
	 *
	 * As a result of this, the following constructs have identical
	 * functionality:
	 *
	 *    Field::inst( 'firstName as name' );
	 *    Field::inst( 'firstName', 'name' );
	 *
	 *  @param string $_ Value to set if using as a setter.
	 *  @return string|self The name of the db field if no parameter is given,
	 *    or self if used as a setter.
	 */
	public function dbField ( $_=null )
	{
		if ( $_ === null ) {
			return $this->_dbField;
		}

		if ( stripos( $_, ' as ' ) ) {
			$a = preg_split( '/ as /i', $_ );
			$this->_dbField = trim( $a[0] );
			$this->_name = trim( $a[1] );
		}
		else {
			$this->_dbField = $_;
		}

		return $this;
	}


	/**
	 * Get / set the 'get' property of the field.
	 *
	 * A field can be marked as write only when setting the get property to false
	 * here.
	 *  @param boolean $_ Value to set if using as a setter.
	 *  @return boolean|self The get property if no parameter is given, or self
	 *    if used as a setter.
	 */
	public function get ( $_=null )
	{
		return $this->_getSet( $this->_get, $_ );
	}


	/**
	 * Get formatter for the field's data.
	 *
	 * When the data has been retrieved from the server, it can be passed through
	 * a formatter here, which will manipulate (format) the data as required. This
	 * can be useful when, for example, working with dates and a particular format
	 * is required on the client-side.
	 *
	 * Editor has a number of formatters available with the {@link Format} class
	 * which can be used directly with this method.
	 *  @param callable|string $_ Value to set if using as a setter. Can be given as
	 *    a closure function or a string with a reference to a function that will
	 *    be called with call_user_func().
	 *  @param mixed $opts Variable that is passed through to the get formatting
	 *    function - can be useful for passing through extra information such as
	 *    date formatting string, or a required flag. The actual options available
	 *    depend upon the formatter used.
	 *  @return callable|string|self The get formatter if no parameter is given, or
	 *    self if used as a setter.
	 */
	public function getFormatter ( $_=null, $opts=null )
	{
		if ( $opts !== null ) {
			$this->_getFormatterOpts = $opts;
		}
		return $this->_getSet( $this->_getFormatter, $_ );
	}


	/**
	 * Get / set a get value. If given, then this value is used to send to the
	 * client-side, regardless of what value is held by the database.
	 * 
	 * @param callable|string|number $_ Value to set, or no value to use as a
	 *     getter
	 * @return callable|string|self Value if used as a getter, or self if used
	 *     as a setter.
	 */
	public function getValue ( $_=null )
	{
		return $this->_getSet( $this->_getValue, $_ );
	}


	/**
	 * Get / set the 'name' property of the field.
	 *
	 * The name is typically the same as the dbField name, since it makes things
	 * less confusing(!), but it is possible to set a different name for the data
	 * which is used in the JSON returned to DataTables in a 'get' operation and
	 * the field name used in a 'set' operation.
	 *  @param string $_ Value to set if using as a setter.
	 *  @return string|self The name property if no parameter is given, or self
	 *    if used as a setter.
	 */
	public function name ( $_=null )
	{
		return $this->_getSet( $this->_name, $_ );
	}


	/**
	 * Get a list of values that can be used for the options list in radio,
	 * select and checkbox inputs from the database for this field.
	 *
	 * Note that this is for simple 'label / value' pairs only. For more complex
	 * data, including pairs that require joins and where conditions, use a
	 * closure to provide a query
	 *
	 * @param  string|callable $table Database table name to use to get the
	 *     paired data from, or a closure function if providing a method
	 * @param  string          $value Table column name that contains the pair's
	 *     value. Not used if the first parameter is given as a closure
	 * @param  string          $label Table column name that contains the pair's
	 *     label. Not used if the first parameter is given as a closure
	 * @param  callable        $condition Function that will add `where`
	 *     conditions to the query
	 * @return Field                  Self for chaining
	 */
	public function options ( $table, $value=null, $label=null, $condition=null, $format=null )
	{
		$this->_optsTable  = $table;
		$this->_optsValue  = $value;
		$this->_optsLabel  = $label;
		$this->_optsCond   = $condition;
		$this->_optsFormat = $format;

		return $this;
	}


	/**
	 * Get / set the 'set' property of the field.
	 *
	 * A field can be marked as read only using this option, to be set only
	 * during an create or edit action or to be set during both actions. This
	 * provides the ability to have fields that are only set when a new row is
	 * created (for example a "created" time stamp).
	 *  @param string|boolean $_ Value to set when the method is being used as a
	 *    setter (leave as undefined to use as a getter). This can take the
	 *    value of:
	 *    
	 *    * `true`              - Same as `Field::SET_NONE`
	 *    * `false`             - Same as `Field::SET_BOTH`
	 *    * `Field::SET_BOTH`   - Set the database value on both create and edit commands
	 *    * `Field::SET_NONE`   - Never set the database value
	 *    * `Field::SET_CREATE` - Set the database value only on create
	 *    * `Field::SET_EDIT`   - Set the database value only on edit
	 *  @return string|self The set property if no parameter is given, or self
	 *    if used as a setter.
	 */
	public function set ( $_=null )
	{
		if ( $_ === true ) {
			$_ = Field::SET_BOTH;
		}
		else if ( $_ === false ) {
			$_ = Field::SET_NONE;
		}

		return $this->_getSet( $this->_set, $_ );
	}


	/**
	 * Set formatter for the field's data.
	 *
	 * When the data has been retrieved from the server, it can be passed through
	 * a formatter here, which will manipulate (format) the data as required. This
	 * can be useful when, for example, working with dates and a particular format
	 * is required on the client-side.
	 *
	 * Editor has a number of formatters available with the {@link Format} class
	 * which can be used directly with this method.
	 *  @param callable|string $_ Value to set if using as a setter. Can be given as
	 *    a closure function or a string with a reference to a function that will
	 *    be called with call_user_func().
	 *  @param mixed $opts Variable that is passed through to the get formatting
	 *    function - can be useful for passing through extra information such as
	 *    date formatting string, or a required flag. The actual options available
	 *    depend upon the formatter used.
	 *  @return callable|string|self The set formatter if no parameter is given, or
	 *    self if used as a setter.
	 */
	public function setFormatter ( $_=null, $opts=null )
	{
		if ( $opts !== null ) {
			$this->_setFormatterOpts = $opts;
		}
		return $this->_getSet( $this->_setFormatter, $_ );
	}


	/**
	 * Get / set a set value. If given, then this value is used to write to the
	 * database regardless of what data is sent from the client-side.
	 * 
	 * @param callable|string|number $_ Value to set, or no value to use as a
	 *     getter
	 * @return callable|string|self Value if used as a getter, or self if used
	 *     as a setter.
	 */
	public function setValue ( $_=null )
	{
		return $this->_getSet( $this->_setValue, $_ );
	}


	/**
	 * Get / set the upload class for this field.
	 * @param  Upload $_ Upload class if used as a setter
	 * @return Upload|self Value if used as a getter, or self if used
	 *     as a setter.
	 */
	public function upload ( $_=null )
	{
		return $this->_getSet( $this->_upload, $_ );
	}


	/**
	 * Get / set the 'validator' of the field.
	 *
	 * The validator can be used to check if any abstract piece of data is valid
	 * or not according to the given rules of the validation function used.
	 *
	 * Multiple validation options can be applied to a field instance by calling
	 * this method multiple times. For example, it would be possible to have a
	 * 'required' validation and a 'maxLength' validation with multiple calls.
	 * 
	 * Editor has a number of validation available with the {@link Validate} class
	 * which can be used directly with this method.
	 *  @param callable|string $_ Value to set if using as the validation method.
	 *    Can be given as a closure function or a string with a reference to a 
	 *    function that will be called with call_user_func().
	 *  @param mixed $opts Variable that is passed through to the validation
	 *    function - can be useful for passing through extra information such as
	 *    date formatting string, or a required flag. The actual options available
	 *    depend upon the validation function used.
	 *  @return callable|string|self The validation method if no parameter is given,
	 *    or self if used as a setter.
	 */
	public function validator ( $_=null, $opts=null )
	{
		if ( $_ === null ) {
			return $this->_validator;
		}
		else {
			$this->_validator[] = array(
				"func" => $_,
				"opts" => $opts
			);
		}

		return $this;
	}


	/**
	 * Set a formatting method that will be used for XSS checking / removal.
	 * This should be a function that takes a single argument (the value to be
	 * cleaned) and returns the cleaned value.
	 *
	 * Editor will use HtmLawed by default for this operation, which is built
	 * into the software and no additional configuration is required, but a
	 * custom function can be used if you wish to use a different formatter such
	 * as HTMLPurifier.
	 *
	 * If you wish to disable this option (which you would only do if you are
	 * absolutely confident that your validation will pick up on any XSS inputs)
	 * simply provide a closure function that returns the value given to the
	 * function. This is _not_ recommended.
	 *
	 * @param  callable|false $xssFormatter XSS cleaner function, use `false` or
	 *   `null` to disable XSS cleaning.
	 * @return Field                        Self for chaining.
	 */
	public function xss ( $xssFormatter )
	{
		if ( $xssFormatter === true || $xssFormatter === false || $xssFormatter === null ) {
			$this->_xssFormat = $xssFormatter;
		}
		else {
			$this->_xss = $xssFormatter;
		}

		return $this;
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 * Used by the Editor class and not generally for public use
	 */

	/**
	 * Check to see if a field should be used for a particular action (get or set).
	 *
	 * Called by the Editor / Join class instances - not expected for general
	 * consumption - internal.
	 *  @param string $action Direction that the data is travelling  - 'get' is
	 *    reading DB data, `create` and `edit` for writing to the DB
	 *  @param array $data Data submitted from the client-side when setting.
	 *  @return boolean true if the field should be used in the get / set.
	 *  @internal
	 */
	public function apply ( $action, $data=null )
	{
		if ( $action === 'get' ) {
			// Get action - can we get this field
			return $this->_get;
		}
		else {
			// Note that validation must be done on input data before we get here

			// Create or edit action, are we configured to use this field
			if ( $action === 'create' &&
				($this->_set === Field::SET_NONE || $this->_set === Field::SET_EDIT)
			) {
				return false;
			}
			else if ( $action === 'edit' &&
				($this->_set === Field::SET_NONE || $this->_set === Field::SET_CREATE)
			) {
				return false;
			}

			// Check it was in the submitted data. If not, then not required
			// (validation would have failed if it was) and therefore we don't
			// set it. Check for a value as well, as it can format data from
			// some other source
			if ( $this->_setValue === null && ! $this->_inData( $this->name(), $data ) ) {
				return false;
			}

			// In the data set, so use it
			return true;
		}
	}


	/**
	 * Execute the ipOpts to get the list of options to return to the client-
	 * side
	 *
	 * @param  \DataTables\Database $db Database instance
	 * @return Array        Array of value / label options for the list
	 * @internal
	 */
	public function optionsExec ( $db )
	{
		$table = $this->_optsTable;

		if ( ! $table ) {
			return false;
		}
		else if ( is_callable($table) && is_object($table) ) {
			return $table();
		}
		else {
			$label = $this->_optsLabel;
			$value = $this->_optsValue;
			$formatter = $this->_optsFormat;
			$fields = array( $value );

			// Create a list of the fields that we need to get from the db
			if ( ! is_array( $label ) ) {
				$fields[] = $label;
			}
			else {
				$fields = array_merge( $fields, $label );
			}

			// We need a default formatter if one isn't provided
			if ( ! $formatter ) {
				$formatter = is_array( $label ) ?
					function ( $row ) use ( $label ) {
						$a = array();

						for ( $i=0, $ien=count($label) ; $i<$ien ; $i++ ) {
							$a[] = $row[ $label[$i] ];
						}

						return implode(' ', $a);
					} :
					function ( $row ) use ( $label ) {
						return $row[ $label ];
					};
			}

			// Get the data
			$rows = $db
				->selectDistinct(
					$table,
					$fields,
					$this->_optsCond
				)
				->fetchAll();

			// Create the output array
			$out = array();

			for ( $i=0, $ien=count($rows) ; $i<$ien ; $i++ ) {
				$out[] = array(
					"label" => $formatter( $rows[$i] ),
					"value" => $rows[$i][$value]
				);
			}

			usort( $out, function ( $a, $b ) {
				return is_numeric($a['label']) && is_numeric($b['label']) ?
					($a['label']*1) - ($b['label']*1) :
					strcmp( $a['label'], $b['label'] );
			} );

			return $out;
		}
	}


	/**
	 * Get the options table
	 * @return string Options table or null
	 * @internal
	 */
	public function optsTable ()
	{
		return is_string( $this->_optsTable ) ?
			$this->_optsTable :
			null;
	}


	/**
	 * Get the options value column
	 * @return string Options value or null
	 * @internal
	 */
	public function optsValue ()
	{
		return $this->_optsValue;
	}


	/**
	 * Get the value of the field, taking into account if it is coming from the
	 * DB or from a POST. If formatting has been specified for this field, it
	 * will be applied here.
	 *
	 * Called by the Editor / Join class instances - not expected for general
	 * consumption - internal.
	 *  @param string $direction Direction that the data is travelling  - 'get' is
	 *    reading data, and 'set' is writing it to the DB.
	 *  @param array $data Data submitted from the client-side when setting or the
	 *    data for the row when getting data from the DB.
	 *  @return string Value for the field
	 *  @internal
	 */
	public function val ( $direction, $data )
	{
		if ( $direction === 'get' ) {
			if ( $this->_getValue !== null ) {
				$val = $this->_getAssignedValue( $this->_getValue );
			}
			else {
				// Getting data, so the db field name
				$val = isset( $data[ $this->_dbField ] ) ?
					$data[ $this->_dbField ] :
					null;
			}

			return $this->_format(
				$val, $data, $this->_getFormatter, $this->_getFormatterOpts
			);
		}
		else {
			// Setting data, so using from the payload (POST usually) and thus
			// use the 'name'
			$val = $this->_setValue !== null ?
				$this->_getAssignedValue( $this->_setValue ) :
				$this->_readProp( $this->name(), $data );

			// XSS removal / checker
			if ( $this->_xssFormat ) {
				$val = $this->xssSafety( $val );
			}

			return $this->_format(
				$val, $data, $this->_setFormatter, $this->_setFormatterOpts
			);
		}
	}


	/**
	 * Check the validity of the field based on the data submitted. Note that
	 * this validation is performed on the wire data - i.e. that which is
	 * submitted, before any setFormatter is run
	 *
	 * Called by the Editor / Join class instances - not expected for general
	 * consumption - internal.
	 *
	 * @param array $data Data submitted from the client-side 
	 * @param Editor $editor Editor instance
	 * @param * $id Row id that is being validated
	 * @return boolean|string `true` if valid, string with error message if not
	 * @internal
	 */
	public function validate ( $data, $editor, $id=null )
	{
		// Three cases for the validator - closure, string or null
		if ( ! count( $this->_validator ) ) {
			return true;
		}

		$val = $this->_readProp( $this->name(), $data );

		for ( $i=0, $ien=count( $this->_validator ) ; $i<$ien ; $i++ ) {
			$validator = $this->_validator[$i];
			$processData = $editor->inData();
			$instances = array(
				'action' => $processData['action'],
				'id'     => $id,
				'field'  => $this,
				'editor' => $editor,
				'db'     => $editor->db()
			);

			if ( is_string( $validator['func'] ) ) {
				// Don't require the Editor namespace if DataTables validator is given as a string
				if ( strpos($validator['func'], "Validate::") === 0 ) {
					$res = call_user_func( "\\DataTables\\Editor\\".$validator['func'], $val, $data, $validator['opts'], $instances );
				}
				else {
					$res = call_user_func( $validator['func'], $val, $data, $validator['opts'], $instances );
				}
			}
			else {
				$func = $validator['func'];
				$res = $func( $val, $data, $this, $instances );
			}

			// Check if there was a validation error and if so, return it
			if ( $res !== true ) {
				return $res;
			}
		}

		// Validation methods all run, must be valid
		return true;
	}


	/**
	 * Write the value for this field to the output array for a read operation
	 *
	 * @param  array $out     Row output data (to the JSON)
	 * @param  mixed $srcData Row input data (raw, from the database)
	 * @internal
	 */
	public function write( &$out, $srcData )
	{
		$this->_writeProp( $out, $this->name(), $this->val('get', $srcData) );
	}


	/**
	 * Perform XSS prevention on an input.
	 *
	 * @param  * $val Value to be escaped
	 * @return string Safe value
	 */
	public function xssSafety ( $val ) {
		$xss = $this->_xss;

		if ( is_array( $val ) ) {
			$res = array();

			foreach ( $val as $individual ) {
				$res[] = $xss ?
					$xss( $individual ) :
					DataTables\Vendor\Htmlaw::filter( $individual );
			}

			return $res;
		}

		return $xss ?
			$xss( $val ) :
			DataTables\Vendor\Htmlaw::filter( $val );
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private methods
	 */

	/**
	 * Apply a formatter to data. The caller will decide what formatter to apply
	 * (get or set)
	 *
	 * @param  mixed    $val       Value to be formatted
	 * @param  mixed    $data      Full row data
	 * @param  callable $formatter Formatting function to be called
	 * @param  array    $opts      Array of options to be passed to the formatter
	 * @return mixed               Formatted value
	 */
	private function _format( $val, $data, $formatter, $opts )
	{
		// Three cases for the formatter - closure, string or null
		if ( $formatter ) {
			if ( is_string( $formatter ) ) {
				// Don't require the Editor namespace if DataTables validator is given as a string
				if ( strpos($formatter, "Format::") === 0 ) {
					// Editor formatter
					return call_user_func(
						"\\DataTables\\Editor\\".$formatter,
						$val,
						$data,
						$opts
					);
				}

				// User function (string identifier)
				return call_user_func( $formatter, $val, $data, $opts );
			}

			// Closure
			return $formatter( $val, $data, $opts );
		}
		return $val;
	}

	/**
	 * Get the value from `_[gs]etValue` - taking into account if it is callable
	 * function or not
	 *
	 * @param  mixed $val Value to be evaluated
	 * @return mixed      Value assigned, or returned from the function
	 */
	private function _getAssignedValue ( $val )
	{
		return is_callable($val) && is_object($val) ?
			$val() :
			$val;
	}

	/**
	 * Check is a parameter is in the submitted data set. This is functionally
	 * the same as the `_readProp()` method, but in this case a binary value
	 * is required to indicate if the value is present or not.
	 *
	 * @param  string $name  Javascript dotted object name to write to
	 * @param  array  $data   Data source array to read from
	 * @return boolean       `true` if present, `false` otherwise
	 * @private
	 */
	private function _inData ( $name, $data )
	{
		if ( strpos($name, '.') === false ) {
			return isset( $data[ $name ] ) ?
				true :
				false;
		}

		$names = explode( '.', $name );
		$inner = $data;

		for ( $i=0 ; $i<count($names)-1 ; $i++ ) {
			if ( ! isset( $inner[ $names[$i] ] ) ) {
				return false;
			}

			$inner = $inner[ $names[$i] ];
		}

		return isset( $inner [ $names[count($names)-1] ] ) ?
			true :
			false;
	}

	/**
	 * Read a value from a data structure, using Javascript dotted object
	 * notation. This is the inverse of the `_writeProp` method and provides
	 * the same support, matching DataTables' ability to read nested JSON
	 * data objects.
	 *
	 * @param  string $name  Javascript dotted object name to write to
	 * @param  array  $data  Data source array to read from
	 * @return mixed         The read value, or null if no value found.
	 * @private
	 */
	private function _readProp ( $name, $data )
	{
		if ( strpos($name, '.') === false ) {
			return isset( $data[ $name ] ) ?
				$data[ $name ] :
				null;
		}

		$names = explode( '.', $name );
		$inner = $data;

		for ( $i=0 ; $i<count($names)-1 ; $i++ ) {
			if ( ! isset( $inner[ $names[$i] ] ) ) {
				return null;
			}

			$inner = $inner[ $names[$i] ];
		}

		if ( isset( $names[count($names)-1] ) ) {
			$idx = $names[count($names)-1];

			return isset( $inner[ $idx ] ) ?
				$inner[ $idx ] :
				null;
		}
		return null;
	}

	/**
	 * Write the field's value to an array structure, using Javascript dotted
	 * object notation to indicate JSON data structure. For example `name.first`
	 * gives the data structure: `name: { first: ... }`. This matches DataTables
	 * own ability to do this on the client-side, although this doesn't
	 * implement implement quite such a complex structure (no array / function
	 * support).
	 *
	 * @param  array  &$out   Array to write the data to
	 * @param  string  $name  Javascript dotted object name to write to
	 * @param  mixed   $value Value to write
	 * @throws \Exception Information about duplicate properties
	 * @private
	 */
	private function _writeProp( &$out, $name, $value )
	{
		if ( strpos($name, '.') === false ) {
			$out[ $name ] = $value;
			return;
		}

		$names = explode( '.', $name );
		$inner = &$out;
		for ( $i=0 ; $i<count($names)-1 ; $i++ ) {
			$loopName = $names[$i];

			if ( ! isset( $inner[ $loopName ] ) ) {
				$inner[ $loopName ] = array();
			}
			else if ( ! is_array( $inner[ $loopName ] ) ) {
				throw new \Exception(
					'A property with the name `'.$name.'` already exists. This '.
					'can occur if you have properties which share a prefix - '.
					'for example `name` and `name.first`.'
				);
			}

			$inner = &$inner[ $loopName ];
		}

		if ( isset( $inner[ $names[count($names)-1] ] ) ) {
			throw new \Exception(
				'Duplicate field detected - a field with the name `'.$name.'` '.
				'already exists.'
			);
		}

		$inner[ $names[count($names)-1] ] = $value;
	}
}

