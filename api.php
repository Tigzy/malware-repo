<?php
require_once(__DIR__."/src/config.php");
require_once(__DIR__.'/src/core.php');
require_once(__DIR__.'/src/lib/restlib.php');
require_once(__DIR__."/src/lib/usercake/init.php");

$user = null;

class Rest_Api extends Rest_Rest {
	public function __construct(){
		parent::__construct();				// Init parent contructor	
	}
	
	public function processApi()
	{		
		global $user;
		$current_user = UCUser::getCurrentUser();
		
		// Extract requested API
		$func = isset($_REQUEST['action']) ? strtolower(trim(str_replace("/","",$_REQUEST['action']))) : null;	
		if (!$func && isset($_POST['action'])) $func = strtolower(trim(str_replace("/","",$_POST['action']))) ;	
		
		// Could not extract function, and is not a DELETE request nor a DOWNLOAD request
		if (!$func) 
		{
			if ($this->get_request_method() == "DELETE") {
				// DELETE request
			}
			else if ($this->get_request_method() != "DELETE" 
				&& isset($_REQUEST) 
				&& isset($_REQUEST['_method']) 
				&& $_REQUEST['_method'] == 'DELETE') {
				// DELETE request
			}
			else if (isset($_REQUEST) && isset($_REQUEST['download'])) {
				// DOWNLOAD request
			}
			else {
				$this->response('',406);
			}
		}
		
		// Extract API key
		if($current_user != NULL) // if logged in, we get it from current cookie
			$key = $current_user->Activationtoken();
		else {
			if (!isset($key) && isset($_REQUEST['token'])) 	$key = $_REQUEST['token'];
			if (!isset($key) && isset($_POST['token'])) 	$key = $_POST['token'];	
		}
					
		// Verify API key/ Save user id
		if (!isset($key)) $this->response('',401);
		$is_api_valid 	= UCUser::ValidateAPIKey($key); 
		$user 			= new UCUser(UCUser::GetByAPIKey($key));	
				
		// Go to selected route
		if (!$is_api_valid)											$this->response('',401);		
		else if((int)method_exists($this,$func) > 0)				$this->$func();
		else if($this->get_request_method() == "DELETE" 
			|| (isset($_REQUEST) 
				&& isset($_REQUEST['_method']) 
				&& $_REQUEST['_method'] == 'DELETE')) 				$this->deletefile();
		else if(isset($_REQUEST) && isset($_REQUEST['download'])) 	$this->downloadfile();
		else														$this->unknown($func);
	}
	
	private function getCore() {
		return new MRFCore();
	}
	
	public function getParameter($key) {
		$key_as_header = 'HTTP_' . strtoupper(trim(str_replace("-","_",$key)));
		$value = isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;   // Search in request
		if (!$value && isset($_POST[$key])) $value = $_POST[$key];  // Search in post
		if (!$value && isset($_SERVER[$key_as_header])) $value = $_SERVER[$key_as_header]; // Search in headers
		return $value;
	}
	
	//===========================================================================
	// Routes
	
	// If the route is unknown, give a chance to the modules
	public function unknown($func) {        
        $core 		= $this->getCore();
		$results 	= $core->ModuleAction($func, $this);
		if (!$results) {
        	$this->response('',404);
        	return false;
        }        
        // Answer handled by the modules in case it's found.
	}
	
	public function downloadfile() {
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }		
		$core 		= $this->getCore();
		$results 	= $core->DownloadFile();
		if (!$results) {
			$this->response("Not enough rights",403);
			return false;
		}
		$this->response("{}",200);
	}
	
	public function bulkdownload() {
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }
		
		$use_password = $this->getParameter("use_password");
		if (!$use_password) $use_password = false;
		
		$core 		= $this->getCore();
		$results 	= $core->DownloadFiles($use_password);
		if (!$results) {
			$this->response("Not enough rights",403);
			return false;
		}
		$this->response("{}",200);
	}
	
	public function getfile() {
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }
		
		$md5 = $this->getParameter("hash");
		if (!$md5) {$this->response('missing hash parameter',400); return false; }		
		$core 		= $this->getCore();
		$results 	= $core->GetFile($md5);
		echo json_encode($results);
	}
	
	public function getfiles() {
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }
		$core 		= $this->getCore();
		$results 	= $core->GetFiles();
		echo json_encode($results);
	}
	
	public function getstorageinfo() {
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }
		$core 		= $this->getCore();
		$results 	= $core->GetStorageInfo();
		echo json_encode($results);
	}
	
	public function getusers() {
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }
		$core 		= $this->getCore();
		$results 	= $core->GetUsers();
		echo json_encode($results);
	}
	
	public function updatefile() {
		global $user;
		if($this->get_request_method() != "POST"){ $this->response('',406); return false; }
				
		$md5 = $this->getParameter("hash");
		if (!$md5) {$this->response('missing hash parameter',400); return false; }		
		
		$update				= new stdClass();
		$update->favorite	= $this->getParameter("favorite");
		$update->threat		= $this->getParameter("vendor");
		$update->uploader	= $this->getParameter("new_user");
		$update->comment	= $this->getParameter("comment");
		$update->tags		= $this->getParameter("tags");
		$update->urls 		= $this->getParameter("urls");	
		$update->lock 		= $this->getParameter("lock");	
		$core 				= $this->getCore();
		$results 			= $core->UpdateFile($md5, $user->Id(), $update);
		
		if (!$results) {
			$this->response("Not enough rights",403);
			return false;
		}
		$this->response("{}",200);
	}
	
	public function deletefile() {		
		if($this->get_request_method() != "DELETE" && $this->get_request_method() != "POST"){ $this->response('',406); return false; }
		$core 				= $this->getCore();
		$results 			= $core->DeleteFile();		
		if (!$results) {
			$this->response("Not enough rights",403);
			return false;
		}
		$this->response("{}",200);
	}
	
	public function uploadfiles() {
		if($this->get_request_method() != "POST"){ $this->response('',406); return false; }		
		$core 				= $this->getCore();
		$results 			= $core->UploadFiles();		
		if (!$results) {
			$this->response("Not enough rights",403);
			return false;
		}
		echo json_encode($results);
	}
	
	public function gethexdata() {
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }
		
		$md5 = $this->getParameter("hash");		
		if (!$md5) { $this->response('missing hash parameter',400); return false; }	
		
		$core 		= $this->getCore();
		$results 	= $core->GetFileContent($md5);
		echo $results;
	}
	
	public function getsubmissionsdata() {	
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }		
		
		$days_count 	= -1;
		$days_count_val = $this->getParameter("days_count");		
		if ($days_count_val) $days_count = $days_count_val;
		
		$core 			= $this->getCore();
		$data 			= $core->GetSubmissions($days_count);	
		$labels 		= array();
		$points 		= array();							
		foreach($data as $val)
		{
		    $labels[] = $val["date"];
		    $points[] = $val["count"];
		}				
		$data_new = new stdClass();
		$data_new->labels 			= $labels;
		$data_new->points 			= $points;
		echo json_encode($data_new);
	}
	
	public function getsubmissionsperuserdata() {	
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }		
		
		$core 			= $this->getCore();
		$data 			= $core->GetSubmissionsPerUser();	
		echo json_encode($data);
	}
	
	public function gettagsdata() {	
		if($this->get_request_method() != "GET"){ $this->response('',406); return false; }		
		
		$core 			= $this->getCore();
		$data 			= $core->GetTags();	
		echo json_encode($data);
	}
}

// Initiiate Library
$api = new Rest_Api;
$api->processApi();

?>