<?php

//@ Thanks to - http://phpsec.org
function generateHash($plainText, $salt = null)
{
	if ($salt === null)	{
		$salt = substr(md5(uniqid(rand(), true)), 0, 25);
	}
	else {
		$salt = substr($salt, 0, 25);
	}	
	return $salt . sha1($salt . $plainText);
}

function parseLength($len) {
	$user_units = strtolower(substr($len, -2));
	$user_time = substr($len, 0, -2);
	$units = array("mi" => 60,
			"hr" => 3600,
			"dy" => 86400,
			"wk" => 604800,
			"mo" => 2592000
	);
	if(!array_key_exists($user_units, $units))
		die("Invalid unit of time.");
	else if(!is_numeric($user_time))
		die("Invalid length of time.");
	else
		return (int)$user_time*$units[$user_units];
}

function getUniqueCode($length = "")
{
	$code = md5(uniqid(rand(), true));
	if ($length != "") {
		return substr($code, 0, $length);
	}
	else {
		return $code;
	}
}

function lang($key, $markers = NULL)
{
	global $translations_table;
	if($markers == NULL) {
		$str = $translations_table[$key];
	}
	else {
		//Replace any dyamic markers
		$str = $translations_table[$key];
		$iteration = 1;
		foreach($markers as $marker) {
			$str = str_replace("%m".$iteration."%",$marker,$str);
			$iteration++;
		}
	}
	//Ensure we have something to return
	if($str == "") {
		return ("No language key found");
	}
	else {
		return $str;
	}
}

function minMaxRange($min, $max, $what)
{
	if(strlen(trim($what)) < $min)
		return true;
	else if(strlen(trim($what)) > $max)
		return true;
	else
		return false;
}

function sanitize($str) {
	return strtolower(strip_tags(trim(($str))));
}

function getPageFiles($directories)
{
	$row = array();
	foreach($directories as $directory)
	{
		$pages = glob($directory . "/*.php");
		//print each file name
		foreach ($pages as $page){
			$tokens = explode('/', $page);
			$page = $tokens[sizeof($tokens)-1];
			$row[$page] = $page;
		}
	}
	return $row;
}