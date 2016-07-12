<?php if (!defined('DATATABLES')) exit(); // Ensure being used in DataTables env.

// Enable error reporting for debugging (remove for production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once(__DIR__."/../../../../../src/config.php");

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Database user / pass
 */
$sql_details = array(
	"type" => "Mysql",  // Database type: "Mysql", "Postgres", "Sqlite" or "Sqlserver"
	"user" => $GLOBALS["config"]["db"]["signatures"]["username"],       // Database user name
	"pass" => $GLOBALS["config"]["db"]["signatures"]["password"],       // Database password
	"host" => $GLOBALS["config"]["db"]["signatures"]["host"],       // Database host
	"port" => "",       // Database connection port (can be left empty for default)
	"db"   => $GLOBALS["config"]["db"]["signatures"]["dbname"],       // Database name
	"dsn"  => ""        // PHP DSN extra information. Set as `charset=utf8` if you are using MySQL
);


