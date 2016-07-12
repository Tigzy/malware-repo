<?php
/**
 * Oracle database driver for DataTables libraries. Please note that this
 * uses the Oracle PDO driver, not the oci8_*() methods.
 *
 * This is a *beta* driver.
 * Consider using https://github.com/yajra/laravel-pdo-via-oci8 if you are
 * looking to Oracle with PDO
 *
 *  @author    SpryMedia
 *  @copyright 2014 SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *  @link      http://editor.datatables.net
 */

namespace DataTables\Database;
if (!defined('DATATABLES')) exit();

use PDO;
use DataTables\Database\Query;
use DataTables\Database\DriverOracleResult;


/**
 * MySQL driver for DataTables Database Query class
 *  @internal
 */
class DriverOracleQuery extends Query {
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */
	private $_stmt;


	protected $_identifier_limiter = '';

	protected $_field_quote = '"';

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
			$port = ":{$port}";
		}

		try {
			// If the user space PDO driver from https://github.com/yajra/laravel-pdo-via-oci8
			// is available then use it.
			if ( class_exists('\yajra\Pdo\Oci8') ) {
				$pdo = new \yajra\Pdo\Oci8(
					"{$host}{$port}/{$db}",
					$user,
					$pass,
					array(
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
					)
				);
			}
			else {
				$pdo = @new PDO(
					"oci:dbname=//{$host}{$port}/{$db}".self::dsnPostfix( $dsn ),
					$user,
					$pass,
					array(
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
					)
				);
			}
		} catch (\PDOException $e) {
			// If we can't establish a DB connection then we return a DataTables
			// error.
			echo json_encode( array( 
				"sError" => "An error occurred while connecting to the database ".
					"'{$db}'. The error reported by the server was: ".$e->getMessage()
			) );
			exit(0);
		}

		// Conform to ISO standards
		$stmt = $pdo->prepare( "ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'" );
		$stmt->execute();

		return $pdo;
	}



	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Protected methods
	 */

	protected function _prepare( $sql )
	{
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

		return new DriverOracleResult( $this->_dbcon, $this->_stmt );
	}


	protected function _build_table()
	{
		$out = array();

		for ( $i=0, $ien=count($this->_table) ; $i<$ien ; $i++ ) {
			$t = $this->_table[$i];

			if ( strpos($t, ' as ') ) {
				$a = explode( ' as ', $t );
				$out[] = $a[0].' '.$a[1];
			}
			else {
				$out[] = $t;
			}
		}

		return ' '.implode(', ', $out).' ';
	}
}

