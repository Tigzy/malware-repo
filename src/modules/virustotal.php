<?php

require_once(__DIR__.'/virustotal/virustotalapi.php');
require_once(__DIR__.'/../lib/querybuilder.php');
require_once(__DIR__.'/../storage.php');
require_once(__DIR__.'/../lib/restlib.php');
require_once(__DIR__.'/../lib/usercake/user.php');
require_once(__DIR__.'/../core.php');

class VirusTotal extends IModule
{	
	private $api = null;
	
	const perm_user_vt_uploader 	= 7;
	const perm_user_vt_contributor 	= 8;
	
	public function __construct(array $mod_conf = array(), $callbacks = null)
	{
		$this->api = new VirusTotalAPIV2(
			$mod_conf["key"]
		);
		parent::__construct($mod_conf, $callbacks);
	}
	
	public function OnFileUpload(&$file)
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Insert into VirusTotal table
		$queryobj 		  = new QueryBuilder();
		$table_virustotal = new QueryTable('samples_virustotal');
		$table_virustotal->setInsert(array(
				new QueryUpdate('md5', 	$database->escape_string($file->md5)),
				new QueryUpdate('link', 	''),
				new QueryUpdate('scan_id', 	''),
				new QueryUpdate('score', 	'0', 'int'),
				new QueryUpdate('status', 	strval(VirusTotalAPIV2::ERROR_FILE_NOT_CHECKED), 'int')
		));
		$queryobj->addTable($table_virustotal);	
		$results = $database->Execute($queryobj);
		
		// Submit analysis
		if (isset($file->vt_submit) && $file->vt_submit == True) {
			$this->ScanFile($file, $database);
		}
	}
	
	public function OnFileDelete(&$md5)
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Delete from table
		$queryobj 		  = new QueryBuilder();
		$table_virustotal = new QueryTable('samples_virustotal');
		$table_virustotal->setDelete(True);
		$table_virustotal->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_virustotal);	
		$results = $database->Execute($queryobj);
	}
	
	public function OnGetInfos(&$data)
	{	
	}
	
	public function OnParseFormData(&$data)
	{
		// Handle form data, $data is array($file, $index)	
		$data['file']->vt_submit = False;
		if (isset($_REQUEST['files_data'])) 
		{
			$data_files = json_decode($_REQUEST['files_data']);
			if ($data_files && is_array($data_files)) 
			{
				foreach($data_files as $data_file) 
				{				
					if (property_exists($data_file, 'index') && $data_file->index == $data['index']) {
						if (property_exists($data_file, 'vtsubmit') && $data_file->vtsubmit == True) $data['file']->vt_submit = True;
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
		$table_virustotal = new QueryTable('samples_virustotal');
		$table_virustotal->setSelect(array('link' => 'virustotal_link', 'scan_id' => 'virustotal_scan_id', 'score' => 'virustotal_score', 'status' => 'virustotal_status'));
		$table_virustotal->setJoinType('LEFT');
		$table_virustotal->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		
		if (isset($_GET["virustotal"])) $data['filters']->virustotal = $_GET["virustotal"];
		$filters = $data['filters'];
				
		if (isset($filters->virustotal) && !empty($filters->virustotal)) 
		{
			if (0 === strpos($filters->virustotal, '>')) 		$table_virustotal->addWhere(new QueryWhere('score', $data['database']->escape_string(substr($filters->virustotal, 1)), '>=', 'int'));
			else if (0 === strpos($filters->virustotal, '<')) 	$table_virustotal->addWhere(new QueryWhere('score', $data['database']->escape_string(substr($filters->virustotal, 1)), '<=', 'int'));
			else 												$table_virustotal->addWhere(new QueryWhere('score', $data['database']->escape_string($filters->virustotal), '<=', 'int'));				
		}
		$data['query']->addJoinTable($table_virustotal);
	}
	
	public function OnPostGetFilesDatabaseQuery(&$data)
	{
		//data is results
		foreach($data['results'] as &$result)
		{
			$result["virustotal_score"]     	= (int)$result["virustotal_score"];
	        $result["virustotal_status"] 		= (int)$result["virustotal_status"];
		}
	}
	
	public function OnPostFileInfo(&$data)
	{
		global $user;
		
		// data[file] is file acquired
		// data[database] is the database object
		// Refresh Score
		if (!$GLOBALS["config"]["cron"]["enabled"])
		{			
			if($data['file']->virustotal_status != VirusTotalAPIV2::ERROR_FILE_FOUND 
			&& $data['file']->virustotal_status != VirusTotalAPIV2::ERROR_FILE_NOT_CHECKED 
			&& $data['file']->virustotal_status != VirusTotalAPIV2::ERROR_FILE_TOO_BIG
			&& $this->HasPermission($user->Id(), 'virustotalupload', $data['file']))
			{
				$this->ScanFile($data['file'], $data['database']);
			}
		}
	}
	
	public function OnExecuteCron()
	{		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		$queryobj 		  = new QueryBuilder();
		$table_virustotal = new QueryTable('samples_virustotal');
		$table_virustotal->setSelect(array('md5' => ''));			
		$table_virustotal->addWhere(new QueryWhere('status', strval(VirusTotalAPIV2::ERROR_FILE_UNKNOWN), '<=', 'int'));
		$table_virustotal->addWhere(new QueryWhere('status', strval(VirusTotalAPIV2::ERROR_FILE_TOO_BIG), '<>', 'int'));
		$table_virustotal->addWhere(new QueryWhere('status', strval(VirusTotalAPIV2::ERROR_FILE_NOT_CHECKED), '<>', 'int'));
		$table_virustotal->addOrderBy(new QueryOrderBy('RAND()', 'DESC', False));		
		$queryobj->addTable($table_virustotal);
		$queryobj->setLimits(0, 10);
		$results = $database->Execute($queryobj);
		
		// Get files to update
		foreach($results as $result) 
		{
			$file = $this->execute_callback('on_get_file', array($result['md5']));
			if (!$file) continue;
			if ($file->locked) continue;
			
			// Write to output	
			echo "[VirusTotal] Processing: " . $file->md5 . " (status: " . $file->virustotal_status . ")" . "<br/>\n";
			ob_flush();
		    flush();
			
			$this->ScanFile($file, $database);
			
			// Write to output			
			echo "[VirusTotal] Processed: " . $file->md5 . " (status: " . $file->virustotal_status . ")" . "<br/>\n";
			ob_flush();
		    flush();
	    }
	}
	
	public function virustotalcomment(&$data)
	{
		global $user;
		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "POST"){ $data->response('',406); return false; }		
		
		$md5 		= $data->getParameter("hash");
		$comment 	= $data->getParameter("comment");
		
		if (!$md5) 											{ $data->response('missing hash parameter',400); return false; }
		if (!$comment) 										{ $data->response('missing comment parameter',400); return false; }		
		
		// Get file information, we need the path
		$file = $this->execute_callback('on_get_file', array($md5));
		if (!$file) { $data->response('missing comment parameter',400); return false; }		
		
		if (!$this->HasPermission($user->Id(), 'virustotalcontrib', $file)) { $data->response('Unable to send comment',400); return false; }		
		if (!$this->SendComment($md5, $comment)) 			{ $data->response('Unable to send comment',400); return false; }
		
		$data->response("{}",200);
		return True;
	}
	
	public function virustotalscan(&$data)
	{
		global $user;
		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "POST"){ $data->response('',406); return false; }		
		
		$md5 = $data->getParameter("hash");		
		if (!$md5) { $data->response('missing hash parameter',400); return false; }
		
		// Get file information, we need the path
		$file = $this->execute_callback('on_get_file', array($md5));
		if (!$file) { $data->response('Unable to scan file',400); return false; }
		
		// Get database
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) { $data->response('Unable to scan file',400); return false; }
		
		// Rescan file
		if (!$this->HasPermission($user->Id(), 'virustotalupload', $file)) { $data->response('Unable to send comment',400); return false; }		
		
		if ($file->virustotal_status == VirusTotalAPIV2::ERROR_FILE_NOT_CHECKED) {
			if (!$this->ScanFile($file, $database)) { $data->response('Unable to send comment',400); return false; }
		} 
		else {
			if (!$this->RescanFile($file, $database)) { $data->response('Unable to send comment',400); return false; }
		}

		$payload = new stdClass();
		$payload->status 	= $file->virustotal_status;
		$payload->score 	= $file->virustotal_score;
		$payload->link		= $file->virustotal_link;
		
		$data->response(json_encode($payload),200);
		return True;
	}
	
	private function HasPermission($user, $permission, $file = null)
	{	
		if (!$user) return False;			
		if ($permission == 'virustotalcontrib' && $file) {
			if ($file->locked) return False;
			return UCUser::ValidateUserPermission($user, array(MRFCore::perm_user_admin, self::perm_user_vt_contributor));
		}
		else if ($permission == 'virustotalupload' && $file) {
			if ($file->locked) return False;
			return UCUser::ValidateUserPermission($user, array(MRFCore::perm_user_admin, self::perm_user_vt_uploader));
		}
		return False;
	}	
	
	private function SendComment($hash, $comment)
	{				
		$result = $this->api->makeComment($hash, $comment);
		if (isset($result->response_code)) {
			return $result->response_code == 1;
		}
		return False;
	}
	
	private function SetResultsDatabase(&$file, MRFDatabase &$database)
	{
		// Update VirusTotal table
		$queryobj 		  = new QueryBuilder();
		$table_virustotal = new QueryTable('samples_virustotal');
		$table_virustotal->setUpdate(array(
				new QueryUpdate('link', 	$database->escape_string($file->virustotal_link)),
				new QueryUpdate('scan_id', 	$database->escape_string($file->virustotal_scan_id)),
				new QueryUpdate('score', 	$file->virustotal_score, 'int'),
				new QueryUpdate('status', 	$file->virustotal_status, 'int')
		));
		$table_virustotal->addWhere(new QueryWhere('md5', $database->escape_string($file->md5), '='));
		$queryobj->addTable($table_virustotal);	
		$results = $database->Execute($queryobj);
		
		// Update Vendor
		if (isset($file->virustotal_vendor) && (!isset($file->threat) || empty($file->threat)))
		{
			$file->threat = $file->virustotal_vendor;
			$database->UpdateThreat($file->md5, $file->threat);	
		}
		return True;
	}
	
	// Ask for a rescan on a file known to be existing
	private function RescanFile(&$file, MRFDatabase &$database) {
		return $this->UploadFile($file, $database, false);
	}
	
	// Upload file to VT
	private function UploadFile(&$file, MRFDatabase &$database, $send_file = true)
	{		
		if ($send_file) {
			$result = $this->api->scanFile($file->path,$file->filename);
		} else {
			$result = $this->api->rescanFile($file->md5);
		}		
		if (isset($result->response_code)) 
		{
			// If file has been sent for analysis, we set the result to according response code
			if($result->response_code == VirusTotalAPIV2::ERROR_FILE_FOUND){
				$file->virustotal_status = VirusTotalAPIV2::ERROR_FILE_BEING_ANALYZED;
			}
			// If we asked for a rescan but the file is unknown on VT, force an upload
			else if($result->response_code == VirusTotalAPIV2::ERROR_FILE_UNKNOWN && !$send_file){
				return $this->UploadFile($file, $database, true);
			}
			else {
				$file->virustotal_status = $result->response_code;
			}
		}		
		if (isset($result->permalink)) {
			$file->virustotal_link 		= $result->permalink;
			$file->virustotal_scan_id 	= $result->scan_id;
		}		
		$this->SetResultsDatabase($file, $database);	
		return True;
	}
	
	// This function gets the report if exists, or upload file if unknown (and option enabled)
	private function ScanFile(&$file, MRFDatabase &$database, $upload_if_unknown = false)
	{	
		$success 					= False;
		$is_pending_analysis 		= isset($file->virustotal_status) && $file->virustotal_status == VirusTotalAPIV2::ERROR_FILE_BEING_ANALYZED;
		
		$file->virustotal_score 	= 0;
		$file->virustotal_link  	= '';
		$file->virustotal_status	= VirusTotalAPIV2::ERROR_FILE_NOT_CHECKED;
					
		// Check size
		if ($file->size >= 30000000) // VT limit is 32MB
		{
			$file->virustotal_status = VirusTotalAPIV2::ERROR_FILE_TOO_BIG;
			$this->SetResultsDatabase($file, $database);
			return $success;
		}
		
		// Get existing scan id, or else file hash
		$resource = $file->md5;
		if (isset($file->virustotal_scan_id) && !empty($file->virustotal_scan_id)) {
			$resource = $file->virustotal_scan_id;
		}		
		
		// First, check if file exists
		$report = $this->api->getFileReport($resource);
		if ($report && isset($report->response_code))
		{
			$file->virustotal_scan_id = '';
			
			if ($report->response_code == VirusTotalAPIV2::ERROR_API_LIMIT) {
				$file->virustotal_status = VirusTotalAPIV2::ERROR_API_LIMIT;	//API limit exceeded. Retry later.
			}		
			else if ($report->response_code == VirusTotalAPIV2::ERROR_FILE_BEING_ANALYZED){
				$file->virustotal_status = VirusTotalAPIV2::ERROR_FILE_BEING_ANALYZED; //Being scanned; Keep the permalink to check later				
				if(isset($report->permalink)) {
					$file->virustotal_link = $report->permalink;
				}
	            $success = True;
			}
			else if ($report->response_code == VirusTotalAPIV2::ERROR_API_ERROR) {
				$file->virustotal_status = VirusTotalAPIV2::ERROR_API_ERROR;	//Error occured			
			}
			else if ($report->response_code == VirusTotalAPIV2::ERROR_FILE_UNKNOWN) {				
				if ($this->config["automatic_upload"] == True || $upload_if_unknown == True) { //No results, upload the file
					return $this->UploadFile($file, $database);
				}
				else {
					$file->virustotal_status = VirusTotalAPIV2::ERROR_FILE_UNKNOWN;
				}
	            $success = True;
			}
			else if ($report->response_code == VirusTotalAPIV2::ERROR_FILE_FOUND && isset($report->permalink))
			{				
				//Results					
				if(isset($report->positives)) 	$file->virustotal_score 	= $report->positives;
				if(isset($report->permalink)) 	$file->virustotal_link 		= $report->permalink;					
				if(isset($report->scan_id)) 	$file->virustotal_scan_id  	= $report->scan_id;
				$file->virustotal_status = VirusTotalAPIV2::ERROR_FILE_FOUND;

				if (isset($report->scans)){
					foreach($this->config["vendors_priority"] as $vendor) {
						if (isset($report->scans->{$vendor}) && !empty($report->scans->{$vendor}->result)) {
							$file->virustotal_vendor = $report->scans->{$vendor}->result;
							break;
						}
					}
				}
	            // Comment file
	            if ($is_pending_analysis && $this->config["comment_uploaded"]["enabled"]) {
	            	$this->SendComment($file->md5, $this->config["comment_uploaded"]["comment"]);
	            }
	            $success = True;
			}			
			$this->SetResultsDatabase($file, $database);	
		}
	    return $success;
	}
	
	public function OnCreateDatabase()
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return false;
		
		$success = true;		
		$table_sql = "
		CREATE TABLE IF NOT EXISTS `samples_virustotal` (
		  `md5` varchar(32) NOT NULL,
		  `link` text NOT NULL,
		  `scan_id` text NOT NULL,
		  `score` int(11) NOT NULL,
		  `status` int(11) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>virustotal table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing virustotal table.</p>";
			$success = false;
		}
			
		$table_sql = "
		ALTER TABLE `samples_virustotal`
  		  ADD PRIMARY KEY (`md5`);
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>virustotal table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing virustotal table.</p>";
			$success = false;
		}
		
		return $success;
	}
}