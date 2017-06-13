<?php

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/uploader.php');
require_once(__DIR__.'/modules.php');
require_once(__DIR__.'/storage.php');
require_once(__DIR__.'/utils.php');

// This class stores everything needed for MRF to work
class MRFCore
{
	private static $instance 	= null;	
	private $uploader 			= null;
	private $modules			= array();
	private $database			= null;
	private $users_table 		= array();
	
	const perm_user_admin 			= 2;
	const perm_user_downloader 		= 3;
	const perm_user_editor 			= 4;
	const perm_user_uploader 		= 5;
	
	public function __construct()
	{
		$options = array (
			'upload_dir' => $GLOBALS["config"]["urls"]["storagePath"], 
			'upload_url' => $GLOBALS["config"]["urls"]["storageUrl"], 
			'script_url' => $GLOBALS["config"]["urls"]["baseUrl"]."api.php", 
			'tmp_path' 	 => $GLOBALS["config"]["path"]["tmp"], 
			'delete_type' => 'DELETE', 
			'download_via_php' => 1 
		);		
		$callbacks = array(
	    	'parse_form_data' => array($this, 'OnParseFormData'),	
	    	'on_file_upload' => array($this, 'OnFileUpload'),
	    	'on_file_delete' => array($this, 'OnFileDelete'),
			'on_get_file' => array($this, 'OnGetFile'),
			'on_submit_file' => array($this, 'OnSubmitFile'),
	    	'on_get_file_data' => array($this, 'OnGetFileData'),
	    	'on_get_files_data' => array($this, 'OnGetFilesData'),
	    	'on_check_permissions' => array($this, 'OnCheckPermissions'),
			'on_check_file_exists' => array($this, 'OnCheckFileExists'),
			'on_get_database_object' => array($this, 'OnGetDatabase'),
	    );		
		$this->uploader = new UploadHandler($options, false, null, $callbacks);	// Load uploader
		$this->modules  = new Modules($GLOBALS["config"]["modules"], $callbacks);			// Load modules
		$this->database = new MRFDatabase(
			$GLOBALS["config"]["db"]["storage"]["host"], 
			$GLOBALS["config"]["db"]["storage"]["dbname"],
			$GLOBALS["config"]["db"]["storage"]["username"], 
			$GLOBALS["config"]["db"]["storage"]["password"],
			$GLOBALS["config"]["ui"]["files_per_page"]
		);
	}
	
	public function __destruct()
	{
		
	}
	
	public static function getInstance()
	{
		if ( !isset(self::$instance)) {
			self::$instance = new self;
		}	
		return self::$instance;
	}
	
	public function GetFiles() {
		return $this->uploader->getfiles(false);
	}
	
	// Returns an array with 1/0 file
	public function GetFile($md5) {
		return $this->uploader->getfile($md5, false);
	}
	
	public function DeleteFile() {
		return $this->uploader->deletefile(false);
	}
	
	public function DownloadFile() {
		return $this->uploader->downloadfile(false);
	}
	
	public function UploadFiles() {
		return $this->uploader->uploadfiles(false);
	}
	
	public function DownloadFiles($usepassword) {
		return $this->uploader->downloadfiles($usepassword, false);
	}
	
	public function GetUsers(){
		global $user_db;
		return $user_db->UsersFullData();
	}
	
	public function ModuleAction($action, $api) {
		return $this->modules->Notify($action, $api);	// Call modules, data is passed by reference
	}
	
	public function GetStorageInfo() 
	{
	   	$obj = new stdClass();
		$obj->count 	= $this->database->GetFilesCount();	
		$obj->total 	= $this->database->GetFilesTotalSize();
		$obj->max_page 	= $obj->count == 0 ? 1 : ceil($obj->count / $GLOBALS["config"]["ui"]["files_per_page"]);
		$this->modules->Notify("OnGetInfos", $obj);	// Call modules, data is passed by reference
		return $obj;
    } 
    
    public function GetFileContent($md5) 
	{
    	$file = $this->GetFile($md5);
    	if (!empty($file)) $file = reset($file);
    	else return False;
		
    	$handle = fopen($file->path, "r");
    	if (!$handle) return False;
    	
		$contents = fread($handle, $GLOBALS["config"]["ui"]["hex_max_length"]);
		fclose($handle);
		return $contents;
    }
    
    public function UpdateFile($md5, $user, $update)
    {
    	$file = $this->GetFile($md5);
    	if (!empty($file)) $file = reset($file);
    	else return False;
	
    	// This part has now restriction.
		//==================================================
    	
		if (isset($update->favorite)) {
			$this->database->UpdateFavorite($md5, $user, $update->favorite === "true");
			return True;
		}
		
		// This part is under ownership constraints.
		//==================================================
		
		$owner = $this->HasOwnership($user, $md5);    	
		
    	if (isset($update->lock)) {	
    		if (!$owner) return False;    		
			$this->database->UpdateLocked($md5, $update->lock === "true");
			$file->locked = ($update->lock === "true");
			if ($file->locked) return True;	// We consider it's a success.
		}
		
		// This part is under permissions constraints.
		//==================================================
		
		if (!$this->HasPermission($user, 'edit', $md5))
			return False;
		
		if (isset($update->threat)) {		
			$this->database->UpdateThreat($md5, $update->threat);
		}
		if (isset($update->uploader)) {
			$this->database->UpdateUploader($md5, $update->uploader);
		}
    	if (isset($update->comment)) {
			$this->database->UpdateComment($md5, $update->comment);
		}	
    	if (isset($update->tags)) {		
			$this->database->UpdateTags($md5, $update->tags);
		}
		if (isset($update->urls)) {		
			$this->database->UpdateUrls($md5, $update->urls);
		}		
		
		return True;
    }
    
    // Check if we can touch the file (either uploader, admin)
	public function HasOwnership($user, $md5)
	{
		if (!$user) return False;
		
		// Get file info
		$file = $md5 ? $this->GetFile($md5) : null;
		if ($file && !empty($file)) $file = reset($file);
		
		return $file->uploader == $user || UCUser::ValidateUserPermission($user, array(self::perm_user_admin));
	}
    
	// Check if we can touch the file
	public function HasPermission($user, $permission, $md5 = null)
	{	
		if (!$user) return False;
		
		// Get file info
		$file = $md5 ? $this->GetFile($md5) : null;
		if ($file && !empty($file)) $file = reset($file);
		
		// Edit permissions
		if ($permission == 'edit' && $file) {
			if ($file->locked) return False;
			if ($file->uploader == $user) return True;
			else return UCUser::ValidateUserPermission($user, array(self::perm_user_admin, self::perm_user_editor));
		} 
		else if ($permission == 'upload') {
			return UCUser::ValidateUserPermission($user, array(self::perm_user_admin, self::perm_user_uploader));
		}
		else if ($permission == 'download' && $file) {
			if ($file->uploader == $user) return True;
			return UCUser::ValidateUserPermission($user, array(self::perm_user_admin, self::perm_user_downloader));
		}
		else if ($permission == 'delete' && $file) {
			if ($file->locked) return False;
			if ($file->uploader == $user) return True;
			return UCUser::ValidateUserPermission($user, array(self::perm_user_admin, self::perm_user_editor));
		}
		return False;
	}
	
	public function OnGetDatabase() {
		return $this->database;
	}
	
	public function OnCheckPermissions($item, $permission)
	{
		global $user;
		return $this->HasPermission($user->Id(), $permission, $item);
	}
	
	public function OnCheckFileExists($file)
	{
		return $this->database->FileExists($file->md5);
	}
	
	public function OnParseFormData($file, $index) 
	{
		// Handle form data, e.g. $_REQUEST['description'][$index]		
		// explode files_data
		//files_data => [{"index":0,"vtsubmit":true,"cksubmit":true,"tags":"tag1,tag2,tag3"}]
		$file->tags 			= '';
		$file->urls				= '';
		if (isset($_REQUEST['files_data'])) 
		{
			$data_files = json_decode($_REQUEST['files_data']);
			if ($data_files && is_array($data_files))
			{
				foreach($data_files as $data_file) {				
					if (property_exists($data_file, 'index') && $data_file->index == $index) {
						if (property_exists($data_file, 'tags') && $data_file->tags) 					$file->tags = $data_file->tags;
						if (property_exists($data_file, 'urls') && $data_file->urls) 					$file->urls = $data_file->urls;
					} 
				}
			}
		}
		$data = array('file' => &$file, 'index' => $index);
		$this->modules->Notify("OnParseFormData", $data);	
		return $file;
	}
	
	public function OnFileUpload($file)
	{
		// Add file to database
		$file_more = $file;		
		$file_more->sha256 = hash_file('sha256', $file->path, False);	// Compute SHA256
		$file_more->threat = "";
		$this->database->AddFile($file_more);
		
		// At that point, the file needs to be present into the database.	
		// Call modules, file is passed by reference
		$this->modules->Notify("OnFileUpload", $file_more);	
			
		// Now, we read data from the database to display the file correctly
		$file_db = $this->GetFile($file_more->md5);
		if (!empty($file_db)) $file_more = reset($file_db);
		
		return $file_more;
	}
	
	public function OnFileDelete($md5) {
		$this->database->DeleteFile($md5);
		$this->modules->Notify("OnFileDelete", $md5);	
	}
	
	// Returns an array of filenames matching filters
	public function OnGetFilesData()
	{			
		global $user;
		$filters = new stdClass();
		
		// Setup filters
		if (isset($_GET["user"])) {
			$filters->uploader = UCUser::GetByUserName($_GET["user"]);
		}			
		if (isset($_GET["date"])) 			$filters->date 			= $_GET["date"];
		if (isset($_GET["md5"])) 			$filters->md5 			= $_GET["md5"];
		if (isset($_GET["sha256"]))			$filters->sha256 		= $_GET["sha256"];
		if (isset($_GET["vendor"])) 		$filters->threat 		= $_GET["vendor"];
		if (isset($_GET["name"])) 			$filters->filename 		= $_GET["name"];
		if (isset($_GET["page"])) 			$filters->page 			= $_GET["page"];
		if (isset($_GET["size"])) 			$filters->size 			= $_GET["size"];				
		if (isset($_GET["comment"])) 		$filters->comment 		= $_GET["comment"];	
		if (isset($_GET["favorite"])) 		$filters->favorite 		= $_GET["favorite"];	
		if (isset($_GET["tags"])) 			$filters->tags 			= $_GET["tags"];	
	    if (isset($_GET["urls"])) 			$filters->urls 			= $_GET["urls"];
	
		$results = $this->database->GetFiles($filters, $user->Id(), array($this,'OnFilterDatabaseQuery'));
	    $files = array();
		for ($i = 0; $i < count($results); ++$i) {
	        array_push($files, (object) $results[$i]);
	    }	
		return $files;
	}
	
	public function OnFilterDatabaseQuery($name, $data) {
		if ($name == 'pre_get_files')		
			$this->modules->Notify("OnPreGetFilesDatabaseQuery", $data);	// Call modules, data inside is passed by reference
		elseif ($name == 'post_get_files')		
			$this->modules->Notify("OnPostGetFilesDatabaseQuery", $data);	// Call modules, data inside is passed by reference
	}
	
	public function OnGetFile($md5)
	{
		$file = new stdClass();
		$file->md5 = $md5;
		return $this->uploader->get_file_object($file);
	}
	
	public function OnSubmitFile($filepath, $filename) {
		return $this->uploader->uploadfile($filepath, $filename);
	}
	
	// Modify file object to add additional fields if $db_info_needed = False
	// Otherwise needs to query the database to get information prior to processing it
	public function OnGetFileData($file, $db_info_needed)
	{	    
		global $user;
		
		if ( $db_info_needed ) {
			$file_db = $this->database->GetFile($file->md5, $user->Id(), array($this,'OnFilterDatabaseQuery') );
	        if (empty($file_db)) 
	        	return $file;
	        else {
	        	$file = (object) array_merge((array) $file, $file_db);
	        }
		}
	    
	    // Fetch user data
	    if (!array_key_exists($file->uploader, $this->users_table)) {
	        $user_tmp                      		= new stdClass();  
	        $user_obj							= new UCUser($file->uploader);	        
	        $user_tmp->avatar 	            	= ResizeImage($user_obj->Avatar(), 24, 24);
		    $user_tmp->name 	            	= $user_obj->Name();
	        $this->users_table[$file->uploader] = $user_tmp;
	    }
		
		// Get user data
		$file->user_avatar 	= $this->users_table[$file->uploader]->avatar;
		$file->user_name 	= $this->users_table[$file->uploader]->name;
		
		// Call modules
		$data = array('file' => &$file, 'database' => &$this->database);
		$this->modules->Notify("OnPostFileInfo", $data);	// Call modules, data inside is passed by reference		
		
		// Classify level
		if (stripos($file->threat, 'exploit') !== false 
		 || stripos($file->threat, 'trojan') !== false 
		 || stripos($file->threat, 'rootkit') !== false){
			$file->criticity = 1;
		} 
		else if (stripos($file->threat, 'pup') !== false 
			  || stripos($file->threat, 'not-a-virus') !== false){
			$file->criticity = 2;
		}
		else {
			$file->criticity = 3;
		}
		
		// Return object
		return $file;
	}
	
	public function ExecuteCron()
	{		
		// Sha256
		$queryobj 	   = new QueryBuilder();
		$table_samples = new QueryTable('samples');
		$table_samples->setSelect(array('md5' => ''));
		$table_samples->addWhere(new QueryWhere('sha256', 'NULL', 'IS', 'int'));
		$table_samples->addOrderBy(new QueryOrderBy('RAND()', 'DESC', False));
		$queryobj->addTable($table_samples);
		$results = $this->database->Execute($queryobj);
		
		// Get files to update
		foreach($results as $result) 
		{
			$file = $this->OnGetFile($result['md5']);
			if (!$file) continue;
			
			// Write to output	
			echo "[Sha256] Processing: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
			
		    $file->sha256 = hash_file('sha256', $file->path, False);	// Compute SHA256
			$this->database->UpdateSha256($file->md5, $file->sha256);
			
			// Write to output			
			echo "[Sha256] Processed: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
	    }
		
	    // Modules
		$data = array();
		$this->modules->Notify("OnExecuteCron", $data);	// Call modules, data inside is passed by reference
	}
	
	public function CreateDatabase()
	{
		$success = $this->database->Create();		
		$data = array();
		$this->modules->Notify("OnCreateDatabase", $data);	// Call modules, data inside is passed by reference
		return $success;
	}
	
	public function GetSubmissions($days_count)	{
		return $this->database->GetSubmissions($days_count);
	}
	
	public function GetSubmissionsPerUser()	
	{
		$data = $this->database->GetSubmissionsPerUser();
		foreach ($data as &$uploader_data)
		{
			 $uploader_data["avatar"] 	= "";
			 $uploader_data["name"] 	= "Unknown";
			
			 // Fetch user data
		     $user_obj = new UCUser($uploader_data['uploader']);
		     if (!$user_obj) {
		     	continue;
		     }
			
			// Get user data
			$uploader_data["avatar"] = ResizeImage($user_obj->Avatar(), 72, 72);
		    $uploader_data["name"]   = $user_obj->Name();
		}	
		return $data;
	}
	
	public function GetTags()	{
		return $this->database->GetTags();
	}
}