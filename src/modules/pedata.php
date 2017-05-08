<?php

require_once(__DIR__.'/module.php');
require_once(__DIR__.'/../utils.php');

class PEData extends IModule
{	
	public function OnFileUpload(&$file)
	{
		$this->CreateData($file);		
		$file->pedata = "";	// This is quite big, not needed at file upload
	}
	
	public function OnFileDelete(&$md5)
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Delete from table
		$queryobj 	  = new QueryBuilder();
		$table_pedata = new QueryTable('samples_pedata');
		$table_pedata->setDelete(True);
		$table_pedata->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_pedata);	
		$results = $database->Execute($queryobj);
	}
	
	public function OnGetInfos(&$data)
	{
	}
	
	public function OnExecuteCron()
	{		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		$queryobj 	   = new QueryBuilder();
		$table_samples = new QueryTable('samples');
		$table_samples->setSelect(array('md5' => ''));			
		$table_samples->addOrderBy(new QueryOrderBy('RAND()', 'DESC', False));
		$queryobj->addTable($table_samples);
		
		$table_pedata = new QueryTable('samples_pedata');
		$table_pedata->setJoinType('LEFT');
		$table_pedata->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$table_pedata->addWhere(new QueryWhere('data', 'NULL', 'IS', 'int'));
		$queryobj->addJoinTable($table_pedata);	
		
		$queryobj->setLimits(0, 10);
		$results = $database->Execute($queryobj);
		
		// Get files to update
		foreach($results as $result) 
		{
			$file = $this->execute_callback('on_get_file', array($result['md5']));
			if (!$file) continue;
			if ($file->locked) continue;
			
			// Write to output	
			echo "[PEData] Processing: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
			
			$this->RefreshData($file, $database);
			
			// Write to output			
			echo "[PEData] Processed: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
	    }
	}
	
	public function pedatascan(&$data) 
	{
		global $user;
		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "POST"){ return false; }	
		
		$md5 = $data->getParameter("hash");		
		if (!$md5) { $data->response('missing hash parameter',400); return false; }			
		if (!$this->execute_callback('on_check_permissions', array($md5, 'edit'))) { $data->response('Unable scan file',400); return false; }	
		
		$file = $this->execute_callback('on_get_file', array($md5));
		if (!$file) { $data->response('Unable scan file',400); return false; }	
		if ($file->locked) { $data->response('Unable scan file',400); return false; }	
		
		if (!$this->RefreshData($file)) { $data->response('Unable to scan file',400); return false; }
		
		$data->response("{}",200);
		return True;
	}
	
	public function getpedata(&$data) 
	{
		global $user;
		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "GET"){ return false; }	
		
		$md5 = $data->getParameter("hash");		
		if (!$md5) { $data->response('missing hash parameter',400); return false; }	
		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) { $data->response('unable to get data',400); return false; }	
		
		$results = $this->GetData($md5, $database);
		if (empty($results)) { $data->response('Unable to send comment',400); return false; }
		
		$data->response($results[0]["pedata"],200);
		return True;
	}
	
	public function GetData($md5, MRFDatabase &$database)
	{
		$queryobj     = new QueryBuilder();
		$table_pedata = new QueryTable('samples_pedata');
		$table_pedata->setSelect(array('icon' => '', 'data' => 'pedata', 'pdbpath' => '', 'imphash' => '', 'signer' => ''));			
		$table_pedata->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_pedata);	
		$results = $database->Execute($queryobj);
		return $results;
	}
	
	public function OnPreGetFilesDatabaseQuery(&$data)
	{		
		//data[filters] is search filters
		//data[query] is querybuilder	
		//data[extended] is the extended bool
		$table_pedata = new QueryTable('samples_pedata');
		$table_pedata->setSelect(array('icon' => '', 'pdbpath' => '', 'imphash' => '', 'signer' => ''));			
		if ($data['extended']) $table_pedata->addSelect('data', 'pedata');		
		$table_pedata->setJoinType('LEFT');
		$table_pedata->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$data['query']->addJoinTable($table_pedata);
	}
	
	public function OnPostGetFilesDatabaseQuery(&$data)
	{		
	}
	
	public function OnPostFileInfo(&$data)
	{		
	}
	
	private function CreateData(&$file)
	{
		$this->RefreshData($file, True);	
	}
	
	private function RefreshData(&$file, $create = False)
	{
		$file->pedata  = $this->GeneratePeData($file); 	// Get PE data with pefile lib
		$file->icon    = $this->ExtractIcon($file->pedata);	// Extract icon from data
		$file->pdbpath = $this->ExtractPdbPath($file->pedata); // Extract pdbpath
		$file->imphash = $this->ExtractImphash($file->pedata); // Extract imphash
		$file->signer  = $this->ExtractSigner($file->pedata); // Extract imphash
		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Update table
		$queryobj 	  = new QueryBuilder();
		$table_pedata = new QueryTable('samples_pedata');
		if ($create) 
		{
			$table_pedata->setInsert(array(
				new QueryUpdate('md5', 	$database->escape_string($file->md5)),
				new QueryUpdate('data', $database->escape_string($file->pedata)),
				new QueryUpdate('icon', $database->escape_string($file->icon)),
				new QueryUpdate('pdbpath', $database->escape_string($file->pdbpath)),
				new QueryUpdate('imphash', $database->escape_string($file->imphash)),
				new QueryUpdate('signer', $database->escape_string($file->signer))
			));
		}
		else 
		{
			$table_pedata->setUpdate(array(
				new QueryUpdate('data', $database->escape_string($file->pedata)),
				new QueryUpdate('icon', $database->escape_string($file->icon)),
				new QueryUpdate('pdbpath', $database->escape_string($file->pdbpath)),
				new QueryUpdate('imphash', $database->escape_string($file->imphash)),
				new QueryUpdate('signer', $database->escape_string($file->signer))
			));
			$table_pedata->addWhere(new QueryWhere('md5', $database->escape_string($file->md5), '='));
		}				
		$queryobj->addTable($table_pedata);	
		$results = $database->Execute($queryobj);	
		return True;
	}
	
	private function GeneratePeData($file)
	{
		// Filter
		if (isset($file->mime) && $file->mime != "application/x-dosexec") 
		{
			$data = new stdClass();
			$data->valid = false;
			$data->error = "Not a PE file";
			return json_encode($data);
		}
		
		$command = 'python "'.__DIR__.'/pefile/peparse.py" "'.$file->path.'"';
		ob_start();
		system($command, $retcode);
		$output = ob_get_contents();
		ob_end_clean();
		if ($retcode == 0 || $retcode == 1)
			return $output;
		return '';
	}
	
	private function ExtractIcon($pedata)
	{	
		$decoded = json_decode($pedata);
		if ($decoded === false) return "";
	    if (!isset($decoded->data)) return "";
	    if (!isset($decoded->data->icon)) return "";
	    if (!isset($decoded->data->icon->blob)) return "";
	    
	    // Try with PHP handler
	    $converted = ResizeImage($decoded->data->icon->blob, 24, 24);
	    // If failed, try to extract ICO
	    if (empty($converted)) {
	    	$converted = ConvertIcon($decoded->data->icon->blob);
	    	$converted = ResizeImage($converted, 24, 24);
	    }    
	    return $converted;
	}
	
	private function ExtractPdbPath($pedata)
	{	
		$decoded = json_decode($pedata);
		if ($decoded === false) return "";
	    if (!isset($decoded->data)) return "";
	    if (!isset($decoded->data->pdbpath)) return "";
	    
	    return $decoded->data->pdbpath;
	}
	
	private function ExtractImphash($pedata)
	{	
		$decoded = json_decode($pedata);
		if ($decoded === false) return "";
	    if (!isset($decoded->data)) return "";
	    if (!isset($decoded->data->imphash)) return "";
	    
	    return $decoded->data->imphash;
	}
	
	private function ExtractSigner($pedata)
	{	
		$decoded = json_decode($pedata);
		if ($decoded === false) return "";
	    if (!isset($decoded->data)) return "";
	    if (!isset($decoded->data->signer)) return "";
	    
	    return $decoded->data->signer;
	}
	
	public function OnCreateDatabase()
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return false;
		
		$success = true;		
		$table_sql = "
		CREATE TABLE IF NOT EXISTS `samples_pedata` (
		  `md5` varchar(32) NOT NULL,
		  `data` longtext NOT NULL,
		  `icon` text NOT NULL,
		  `pdbpath` text NOT NULL,
  		  `imphash` text NOT NULL,
		  `signer` text NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>pedata table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing pedata table.</p>";
			$success = false;
		}
		
		$table_sql = "
		ALTER TABLE `samples_pedata`
  		  ADD PRIMARY KEY (`md5`);
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>pedata table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing pedata table.</p>";
			$success = false;
		}
		
		return $success;
	}
}