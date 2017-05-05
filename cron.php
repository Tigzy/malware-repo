<?php
require_once(__DIR__."/src/config.php");
require_once(__DIR__.'/src/core.php');
require_once(__DIR__.'/src/lib/restlib.php');
require_once(__DIR__."/src/lib/usercake/init.php");

$user = null;

class MRFCron extends Rest_Rest 
{	
	public function __construct() {
		parent::__construct();				// Init parent contructor	
	}
	
	private function getCore() {
		return new MRFCore();
	}
	
	public function Execute()
	{
		global $user;
		$current_user = UCUser::getCurrentUser();
		
		// Extract API key
		if($current_user != NULL) // if logged in, we get it from current cookie
			$key = $current_user->Activationtoken();
		else {
			if (!isset($key) && isset($_REQUEST['token'])) 	$key = $_REQUEST['token'];
			if (!isset($key) && isset($_POST['token'])) 	$key = $_POST['token'];	
		}
					
		// Verify API key/ Save user id
		if (!isset($key)) $this->response('',401);
		$is_api_valid 		= UCUser::ValidateAPIKey($key); 
		if (!$is_api_valid) $this->response('',401);		
		$user = new UCUser(UCUser::GetByAPIKey($key));	
		
		//===================================================================
		
		$core = $this->getCore();			
		if (ob_get_level() == 0) ob_start();
		set_time_limit(600);		
		$core->ExecuteCron();	
		ob_end_flush();
	}
}

$cron = new MRFCron();
$cron->Execute();

