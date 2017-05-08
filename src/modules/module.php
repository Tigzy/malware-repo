<?php

// Interface for MRF modules
abstract class IModule
{
	protected $config = array();	
	protected $callbacks = array(
    	'parse_form_data' => null,
    	'on_generate_filename' => null,    	
    	'on_file_upload' => null,
    	'on_file_update' => null, 
    	'on_file_delete' => null,    	
    	'on_get_file_data' => null,
    	'on_get_files_data' => null,
    	'on_check_permissions' => null,
    	'on_check_file_exists' => null,
    );
	
	public function __construct(array $mod_conf = array(), $callbacks = null)
	{
		$this->config = $mod_conf;
		if ($callbacks) {
        	$this->callbacks = $callbacks + $this->callbacks;
        }
	}
	
	public function __destruct()
	{
		
	}
	
	protected function execute_callback($name, $params = array()) {
    	if (isset($this->callbacks[$name])) {
    		return call_user_func_array($this->callbacks[$name], $params);
    	}
    	return False;
    }
    
    public function GetConfig() {
    	return $this->config;
    }
	
	abstract public function OnFileUpload(&$file);		// Function called when a file has been uploaded
	abstract public function OnFileDelete(&$md5);		// Function called when a file has been removed
	abstract public function OnGetInfos(&$data);		// Function called when we need information on the system
	abstract public function OnPreGetFilesDatabaseQuery(&$data);	// Function called when we are about to query the database
	abstract public function OnPostGetFilesDatabaseQuery(&$data);	// Function called when we are about to query the database
	abstract public function OnPostFileInfo(&$data);	// Called when the file information has been acquired
}