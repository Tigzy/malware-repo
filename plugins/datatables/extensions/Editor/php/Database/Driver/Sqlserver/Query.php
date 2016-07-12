<?php
/**
 * SQL Server driver for DataTables PHP libraries
 *
 *  @author    SpryMedia
 *  @copyright 2013 SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *  @link      http://editor.datatables.net
 */

namespace DataTables\Database;
if (!defined('DATATABLES')) exit();

use PDO;
use DataTables\Database\Query;
use DataTables\Database\DriverPostgresResult;


/**
 * SQL Server driver for DataTables Database Query class
 *  @internal
 */
class DriverSqlserverQuery extends Query {
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */
	private $_stmt;


	protected $_identifier_limiter = '';

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	static function connect( $user, $pass='', $host='', $port='', $db='', $dsn='' )
	{
		if ( is_array( $user ) ) {
			$opts = $user;
			$user = $opts['user'];
			$pass = $opts['pass'];
			$port = $opts['port'];
			$host = $opts['host'];
			$db   = $opts['db'];
			$dsn  = isset( $opts['dsn'] ) ? $opts['dsn'] : '';
		}

		if ( $port !== "" ) {
			$port = ",{$port}";
		}

		try {
			$pdo = new PDO(
				"sqlsrv:Server={$host}{$port};Database={$db}".self::dsnPostfix( $dsn ),
				$user,
				$pass,
				array(
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
				)
			);
		} catch (\PDOException $e) {
			// If we can't establish a DB connection then we return a DataTables
			// error.
			echo json_encode( array( 
				"sError" => "An error occurred while connecting to the database ".
					"'{$db}'. The error reported by the server was: ".$e->getMessage()
			) );
			exit(0);
		}

		return $pdo;
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Protected methods
	 */

	protected function _prepare( $sql )
	{
		// Prep a PDO statement
		$this->_stmt = $this->_dbcon->prepare( $sql );

		// bind values
		for ( $i=0 ; $i<count($this->_bindings) ; $i++ ) {
			$binding = $this->_bindings[$i];

			$this->_stmt->bindValue(
				$binding['name'],
				$binding['value'],
				$binding['type'] ? $binding['type'] : \PDO::PARAM_STR
			);
		}

		//file_put_contents( '/tmp/editor_sql', $sql."\n", FILE_APPEND );
	}


	protected function _exec()
	{
		try {
			$this->_stmt->execute();
		}
		catch (PDOException $e) {
			echo "An SQL error occurred: ".$e->getMessage();
			error_log( "An SQL error occurred: ".$e->getMessage() );
			return false;
		}

		return new DriverSqlserverResult( $this->_dbcon, $this->_stmt );
	}


	// SQL Server 2012+ only
	protected function _build_limit()
	{
		$out = '';

		if ( $this->_offset ) {
			$out .= ' OFFSET '.$this->_offset.' ROWS';
		}
		
		if ( $this->_limit ) {
			if ( ! $this->_offset ) {
				$out .= ' OFFSET 0 ROWS';
			}
			$out .= ' FETCH NEXT '.$this->_limit. ' ROWS ONLY';
		}

		return $out;
	}
}

