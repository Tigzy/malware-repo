<?php

require_once(__DIR__.'/cuckoo/cuckooapi.php');
require_once(__DIR__.'/../lib/querybuilder.php');
require_once(__DIR__.'/../storage.php');
require_once(__DIR__.'/../lib/restlib.php');
require_once(__DIR__.'/../lib/usercake/user.php');
require_once(__DIR__.'/../core.php');

class Cuckoo extends IModule
{	
	private $api 		= null;
	private $available 	= false;
	
	const perm_user_cuckoo_uploader = 6;
	
	public function __construct(array $mod_conf = array(), $callbacks = null)
	{
		$this->api = new CuckooAPI(
			$mod_conf["web_base_url"],
			$mod_conf["api_base_url"],
			$mod_conf["scan"]
		);		
		parent::__construct($mod_conf, $callbacks);
		$this->OnGetInfos(new stdClass());
	}
	
	public function cuckooscan(&$data)
	{
		global $user;
		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "POST"){ return false; }	
		
		$md5 = $data->getParameter("hash");		
		if (!$md5) { $data->response('missing hash parameter',400); return false; }
		
		$options 		= array();		
		$machine 		= $data->getParameter("machine");
		if ($machine && $machine != "auto") {
			$options["machine"] = $machine;
		}
		
		$scan_options 	= $data->getParameter("scan_options");	
		if ($scan_options && is_array($scan_options)) {
			$options["options"] = "";
			foreach($scan_options as $index => $value) {
				if ($options["options"] != "") {
					$options["options"] = $options["options"] . ",";
				}
				$options["options"] = $options["options"] . $value . "=yes"; 
			}
		}		
		
		// Get file information, we need the path
		$file = $this->execute_callback('on_get_file', array($md5));
		if (!$file) { $data->response('Unable to scan file',400); return false; }
		
		// Get database
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) { $data->response('Unable to scan file',400); return false; }
		
		// Scan file
		if (!$this->HasPermission($user->Id(), 'cuckooupload', $file)) { $data->response('Unable to scan file',400); return false; }		
		if (!$this->ScanFile($file, $database, $options)) { $data->response('Unable to scan file',400); return false; }
		
		$payload = new stdClass();
		$payload->status 	= $file->cuckoo_status;
		$payload->score 	= $file->cuckoo_scan_id;
		$payload->link		= $file->cuckoo_link;
		
		$data->response(json_encode($payload),200);
		
		return True;
	}
	
	public function cuckoogetmachines(&$data)
	{		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "GET"){ return false; }	
		
		$obj = $this->api->getMachines();
		if (!is_object($obj)) { $data->response('Unable to get information',400); return false; }
		
		$obj->scan_options = $this->config["scan_optional"];
		
		$data->response(json_encode($obj),200);		
		return True;
	}
	
	public static function SortTaskById($task1, $task2)
	{
		if ($task1->id < $task2->id) 
			return -1;
		else if ($task1->id > $task2->id) 
			return 1;
		else 
			return 0;
	}
	
	public function cuckoogettasks(&$data)
	{		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "GET"){ return false; }	
		
		$obj = $this->api->getInfos();
		if (!is_object($obj)) { $data->response('Unable to get information',400); return false; }
		
		$max = 100;	// We limit to 100 tasks, we want to get the newests first
		
		$obj = $this->api->getTasks();
		if (!is_object($obj)) { $data->response('Unable to get information',400); return false; }
		
		// Sort by ID, truncate
		usort($obj->tasks, array("Cuckoo", "SortTaskById"));
		$obj->tasks = array_reverse($obj->tasks);
		$obj->tasks = array_slice($obj->tasks, 0, $max);
		
		// Add more data
		foreach($obj->tasks as &$task) {
			$task->link = $this->api->getReportUrl($task->id);
		}
		
		$data->response(json_encode($obj),200);		
		return True;
	}
	
	private function HasPermission($user, $permission, $file = null)
	{	
		if (!$user) return False;		
		if ($permission == 'cuckooupload' && $file) {
			if ($file->locked) return False;
			return UCUser::ValidateUserPermission($user, array(MRFCore::perm_user_admin, self::perm_user_cuckoo_uploader));
		}
		return False;
	}
	
	public function OnFileUpload(&$file)
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Insert into Cuckoo table
		$queryobj     = new QueryBuilder();
		$table_cuckoo = new QueryTable('samples_cuckoo');
		$table_cuckoo->setInsert(array(
				new QueryUpdate('md5', 	$database->escape_string($file->md5)),
				new QueryUpdate('scan_id', 	-1),
				new QueryUpdate('status', 	strval(CuckooAPI::ERROR_FILE_UNKNOWN), 'int')
		));
		$queryobj->addTable($table_cuckoo);	
		$results = $database->Execute($queryobj);
		
		// Submit analysis
		if (isset($file->cuckoo_submit) && $file->cuckoo_submit == True) {
			$this->ScanFile($file, $database);
		}
	}
	
	public function OnFileDelete(&$md5)
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Delete from table
		$queryobj 	  = new QueryBuilder();
		$table_cuckoo = new QueryTable('samples_cuckoo');
		$table_cuckoo->setDelete(True);
		$table_cuckoo->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_cuckoo);	
		$results = $database->Execute($queryobj);
	}
	
	public function OnGetInfos(&$data)
	{		
		$obj = $this->api->getInfos();
		if (is_object($obj)) 
		{				
			$this->available 			= true;			
			$data->cuckoo 				= $obj;				
			$data->cuckoo->browse_url 	= $this->api->getBrowseUrl();
		}
	}
	
	public function OnParseFormData(&$data)
	{
		// Handle form data, $data is array($file, $index)	
		$data['file']->cuckoo_submit = False;
		if (isset($_REQUEST['files_data'])) 
		{
			$data_files = json_decode($_REQUEST['files_data']);
			if ($data_files && is_array($data_files)) 
			{
				foreach($data_files as $data_file) 
				{				
					if (property_exists($data_file, 'index') && $data_file->index == $data['index']) {
						if (property_exists($data_file, 'cksubmit') && $data_file->cksubmit == True) $data['file']->cuckoo_submit = True;
					} 
				}
			}
		}
	}
	
	public function OnPreGetFilesDatabaseQuery(&$data)
	{
		//data[filters] is search filters
		//data[query] is querybuilder	
		//data[database] is mysqli handler
		//data[extended] is the extended bool
		$table_cuckoo = new QueryTable('samples_cuckoo');
		$table_cuckoo->setSelect(array('scan_id' => 'cuckoo_scan_id', 'status' => 'cuckoo_status'));	
		$table_cuckoo->setJoinType('LEFT');
		$table_cuckoo->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		
		if (isset($_GET["cuckoo"])) $data['filters']->cuckoo = $_GET["cuckoo"];	
		$filters = $data['filters'];
		
		if (isset($filters->cuckoo) && !empty($filters->cuckoo) && $filters->cuckoo != 'none') 
		{
			if ($filters->cuckoo == 'scanning') 		$table_cuckoo->addWhere(new QueryWhere('status', strval(CuckooAPI::ERROR_FILE_BEING_ANALYZED), '=', 'int'));
			else if ($filters->cuckoo == 'results') 	$table_cuckoo->addWhere(new QueryWhere('status', strval(CuckooAPI::ERROR_FILE_FOUND), '=', 'int'));
			else 										$table_cuckoo->addWhere(new QueryWhere('status', strval(CuckooAPI::ERROR_FILE_UNKNOWN), '=', 'int'));				
		}
		$data['query']->addJoinTable($table_cuckoo);
	}
	
	public function OnPostGetFilesDatabaseQuery(&$data)
	{
		//data[results] is results
		//data[extended] is the extended bool
		foreach($data['results'] as &$result)
		{
			if ($result["cuckoo_scan_id"] != null) {
				$result["cuckoo_scan_id"] = (int)$result["cuckoo_scan_id"];
			}
			else {
				$result["cuckoo_scan_id"] = -1;
			}
			
			if ($result["cuckoo_status"] != null) {
				$result["cuckoo_status"] = (int)$result["cuckoo_status"];
			}
			else {
				$result["cuckoo_status"] = CuckooAPI::ERROR_FILE_UNKNOWN;
			}
		}
	}
	
	public function OnPostFileInfo(&$data)
	{	
		global $user;
		
		// data[file] is file acquired
		// data[database] is the database object
		// Refresh Cuckoo
		if (!$GLOBALS["config"]["cron"]["enabled"])
		{			
			// Refresh state
			if ( $data['file']->cuckoo_status == CuckooAPI::ERROR_FILE_BEING_ANALYZED 
			 && $this->available 
			 && $this->HasPermission($user->Id(), 'cuckooupload', $data['file']) ) {
				$this->GetResults($data['file'], $data['database']);
			}		
			// Search old analysis
			else if ( $data['file']->cuckoo_status == CuckooAPI::ERROR_FILE_UNKNOWN 
			 && $this->available 
			 && $this->HasPermission($user->Id(), 'cuckooupload', $data['file']) ) {
				$this->SearchResults($data['file'], $data['database']);
			}
		}
		
		// Cuckoo data
		if ( $data['file']->cuckoo_status == CuckooAPI::ERROR_FILE_FOUND ) {
			$data['file']->cuckoo_link = $this->api->getReportUrl($data['file']->cuckoo_scan_id);
		}
	}
	
	public function OnExecuteCron()
	{		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Get files to update
		$queryobj 		  = new QueryBuilder();
		$table_cuckoo = new QueryTable('samples_cuckoo');
		$table_cuckoo->setSelect(array('md5' => ''));			
		$table_cuckoo->addWhere(new QueryWhere('status', strval(CuckooAPI::ERROR_FILE_BEING_ANALYZED), '=', 'int'));
		$table_cuckoo->addOrderBy(new QueryOrderBy('RAND()', 'DESC', False));		
		$queryobj->addTable($table_cuckoo);
		$queryobj->setLimits(0, 50);
		$results = $database->Execute($queryobj);		
		
		foreach($results as $result) 
		{
			$file = $this->execute_callback('on_get_file', array($result['md5']));
			if (!$file) continue;
			if ($file->locked) continue;
			
			// Write to output	
			echo "[Cuckoo] Processing: " . $file->md5 . " (status: " . $file->cuckoo_status . ")" . "<br/>\n";
			ob_flush();
		    flush();
			
			$this->SearchResults($file, $database);
			
			// Write to output			
			echo "[Cuckoo] Processed: " . $file->md5 . " (status: " . $file->cuckoo_status . ")" . "<br/>\n";
			ob_flush();
		    flush();
	    }
	    
		// Search existing reports
		$queryobj 		  = new QueryBuilder();
		$table_cuckoo = new QueryTable('samples_cuckoo');
		$table_cuckoo->setSelect(array('md5' => ''));			
		$table_cuckoo->addWhere(new QueryWhere('status', strval(CuckooAPI::ERROR_FILE_UNKNOWN), '=', 'int'));
		$table_cuckoo->addOrderBy(new QueryOrderBy('RAND()', 'DESC', False));		
		$queryobj->addTable($table_cuckoo);
		$queryobj->setLimits(0, 10);
		$results = $database->Execute($queryobj);		
		
		foreach($results as $result) 
		{
			$file = $this->execute_callback('on_get_file', array($result['md5']));
			if (!$file) continue;
			
			// Write to output	
			echo "[Cuckoo] Searching: " . $file->md5 . " (status: " . $file->cuckoo_status . ")" . "<br/>\n";
			ob_flush();
		    flush();
			
			$this->SearchResults($file, $database);
			
			// Write to output			
			echo "[Cuckoo] Processed: " . $file->md5 . " (status: " . $file->cuckoo_status . ")" . "<br/>\n";
			ob_flush();
		    flush();
	    }
	}
	
	private function ScanFile($file, MRFDatabase &$database, $options = array())
	{		
	    $success = False;
	    
	    $file->cuckoo_link			= '';
		$file->cuckoo_scan_id  		= 0;
		$file->cuckoo_status		= CuckooAPI::ERROR_FILE_UNKNOWN;
		
		// Check size
		if ($file->size >= 30000000) //Cuckoo limit is 32MB, we keep some margin
		{
			$file->cuckoo_status = CuckooAPI::ERROR_FILE_TOO_BIG;	//file is too big
			$this->SetResultsDatabase($file, $database);
			return $success;
		}
	
		$result = $this->api->scanFile($file->path, $file->filename, $options);			
		if (isset($result->response_code)) {
			$file->cuckoo_status = $result->response_code;
			$this->SetResultsDatabase($file, $database);
	    	return False;
		}	
		else if (isset($result->task_id)){
			$file->cuckoo_status 		= CuckooAPI::ERROR_FILE_BEING_ANALYZED;		
			$file->cuckoo_scan_id 		= $result->task_id;
			$file->cuckoo_link 			= $this->api->getReportUrl($result->task_id);
	        $this->SetResultsDatabase($file, $database);
	    	return True;
		}
		return False;
	}	

	private function SearchResults($file, MRFDatabase &$database)
	{
		$result = $this->api->getFileReport($file->md5);		
		if (is_object($result) && isset($result->sample) && isset($result->sample->id)) {
			$file->cuckoo_status 		= CuckooAPI::ERROR_FILE_FOUND;
			$file->cuckoo_scan_id		= $result->sample->id;
			$file->cuckoo_link 	  		= $this->api->getReportUrl($file->cuckoo_scan_id);
			$this->SetResultsDatabase($file, $database);
		}
	}
	
	private function GetResults(&$file, MRFDatabase &$database)
	{	
		$result = $this->api->getTask($file->cuckoo_scan_id);		
		if (is_array($result) && isset($result['response_code'])) 
		{
			if ($result['response_code'] == CuckooAPI::ERROR_API_ERROR) 
			{		
				$file->cuckoo_status 		= CuckooAPI::ERROR_FILE_UNKNOWN;		// reset
				$file->cuckoo_scan_id 		= 0;									// reset
				$file->cuckoo_link 			= '';									// reset				
				$this->SetResultsDatabase($file, $database);
			}
		}
		else if (is_object($result) && isset($result->task) && isset($result->task->status)) 
		{
			if ($result->task->status == 'reported') {		
				$file->cuckoo_status 		= CuckooAPI::ERROR_FILE_FOUND;
				$file->cuckoo_link 	  		= $this->api->getReportUrl($file->cuckoo_scan_id);
				$this->SetResultsDatabase($file, $database);
			}
			else if ($result->task->status == 'failed_analysis') {		
				$file->cuckoo_status 		= CuckooAPI::ERROR_FILE_UNKNOWN;		// reset
				$file->cuckoo_scan_id 		= 0;									// reset
				$file->cuckoo_link 			= '';									// reset	
				$this->SetResultsDatabase($file, $database);
			}
		}
	}
	
	private function SetResultsDatabase(&$file, MRFDatabase &$database)
	{
		// Update Cuckoo table
		$queryobj     = new QueryBuilder();
		$table_cuckoo = new QueryTable('samples_cuckoo');
		$table_cuckoo->setUpdate(array(
				new QueryUpdate('scan_id', 	$file->cuckoo_scan_id, 'int'),
				new QueryUpdate('status', 	$file->cuckoo_status, 'int')
		));
		$table_cuckoo->addWhere(new QueryWhere('md5', $database->escape_string($file->md5), '='));
		$queryobj->addTable($table_cuckoo);	
		$results = $database->Execute($queryobj);
	}
	
	public function OnCreateDatabase()
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return false;
		
		$success = true;		
		$table_sql = "
		CREATE TABLE IF NOT EXISTS `samples_cuckoo` (
		  `md5` varchar(32) NOT NULL,
		  `scan_id` int(11) NOT NULL,
		  `status` int(11) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>cuckoo table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing cuckoo table.</p>";
			$success = false;
		}
		
		$table_sql = "
		ALTER TABLE `samples_cuckoo`
  		  ADD PRIMARY KEY (`md5`);
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>cuckoo table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing cuckoo table.</p>";
			$success = false;
		}
		
		return $success;
	}		
}