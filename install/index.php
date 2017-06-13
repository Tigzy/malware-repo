<?php 
require_once(__DIR__."/../src/config.php");
require_once(__DIR__."/../src/lib/usercake/init.php");

if(isset($_GET["install"]))
{
	global $user_db;
	$success = $user_db->Create();
	
	//=====================
	// Custom part
	if (file_exists(__DIR__."/more.php")) 
	{
		include (__DIR__."/more.php");		
		if ( !Install() ){
			$success = false;
		}
	}	
	//=====================
	
	if($success)
		echo "<p><strong>Database setup complete, please delete the install folder.</strong></p>";
	else
		echo "<p><a href=\"?install=true\">Try again</a></p>";
}
else {
	echo "<a href='?install=true'>Install</a>";
}

?>