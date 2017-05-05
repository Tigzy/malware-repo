<?php
/*
UserCake Version: 2.0.2
http://usercake.com
*/

require_once(__DIR__."/src/config.php");
require_once(__DIR__."/src/lib/usercake/init.php");
if (!UCUser::CanUserAccessUrl($_SERVER['PHP_SELF'])) { die();}
$user = UCUser::getCurrentUser();

//Log the user out
if($user)
{
	$user->LogOut();
}

global $user_settings;
if(!empty($user_settings->WebsiteUrl())) 
{
	$add_http = "";	
	if(strpos($user_settings->WebsiteUrl(),"http") === false) {
		$add_http = "http://";
	}
	
	header("Location: ".$add_http . $user_settings->WebsiteUrl());
	die();
}
else
{
	header("Location: http://".$_SERVER['HTTP_HOST']);
	die();
}	

?>

