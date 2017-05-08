<?php

//Direct to install directory, if it exists
if(is_dir("install/"))
{
	header("Location: install/");
	die();
}

require_once(__DIR__."/db.php");
require_once(__DIR__."/utils.php");
require_once(__DIR__."/session.php");
require_once(__DIR__."/user.php");
require_once(__DIR__."/settings.php");
require_once(__DIR__."/page.php");
require_once(__DIR__."/permission.php");
require_once(__DIR__."/mail.php");

//=========================================
// Open Database

$db_host = $GLOBALS["config"]["db"]["usercake"]["host"]; 		//Host address (most likely localhost)
$db_name = $GLOBALS["config"]["db"]["usercake"]["dbname"]; 		//Name of Database
$db_user = $GLOBALS["config"]["db"]["usercake"]["username"]; 	//Name of database user
$db_pass = $GLOBALS["config"]["db"]["usercake"]["password"]; 	//Password for database user
$db_table_prefix = "uc_";

$user_db = new UCDatabase( $db_host, $db_name, $db_user, $db_pass, $db_table_prefix );
if (!$user_db->IsConnected()) {
	echo "Connection Failed: " . $user_db->LastError();
	exit();
}
GLOBAL $user_db;	// Make it global to the application

//=========================================
// Read settings

$user_settings = new UCSettings($user_db);

// Include the language pack
require_once($user_settings->Language());
GLOBAL $user_settings;

//=========================================
// Get current session

$session = Session::getInstance();
GLOBAL $session;

//=========================================
// Get current user

$current_user = UCUser::GetFromSession();
if ($current_user != NULL) {
	UCUser::setCurrentUser($current_user);
}

?>
