<?php

require_once(__DIR__.'/module.php');
require_once(__DIR__.'/../utils.php');

class OfficeData extends IModule
{	
	public function OnFileUpload(&$file)
	{
		$this->CreateData($file);		
		$file->officedata = "";	// This is quite big, not needed at file upload
	}
	
	public function OnFileDelete(&$md5)
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Delete from table
		$queryobj 	  = new QueryBuilder();
		$table_officedata = new QueryTable('samples_officedata');
		$table_officedata->setDelete(True);
		$table_officedata->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_officedata);	
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
		
		$table_officedata = new QueryTable('samples_officedata');
		$table_officedata->setJoinType('LEFT');
		$table_officedata->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$table_officedata->addWhere(new QueryWhere('data', 'NULL', 'IS', 'int'));
		$queryobj->addJoinTable($table_officedata);	
		
		$queryobj->setLimits(0, 10);
		$results = $database->Execute($queryobj);
		
		// Get files to update
		foreach($results as $result) 
		{
			$file = $this->execute_callback('on_get_file', array($result['md5']));
			if (!$file) continue;
			if ($file->locked) continue;
			
			// Write to output	
			echo "[OfficeData] Processing: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
			
			$this->RefreshData($file, $database);
			
			// Write to output			
			echo "[OfficeData] Processed: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
	    }
	}
	
	public function officedatascan(&$data) 
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
	
	public function getofficedata(&$data) 
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
		
		$data->response($results[0]["officedata"],200);
		return True;
	}
	
	public function GetData($md5, MRFDatabase &$database)
	{
		$queryobj         = new QueryBuilder();
		$table_officedata = new QueryTable('samples_officedata');
		$table_officedata->setSelect(array('data' => 'officedata'));			
		$table_officedata->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_officedata);	
		$results = $database->Execute($queryobj);
		return $results;
	}
	
	public function OnPreGetFilesDatabaseQuery(&$data)
	{		
		//data[filters] is search filters
		//data[query] is querybuilder	
		//data[extended] is the extended bool
		if ($data['extended'])
		{
			$table_officedata = new QueryTable('samples_officedata');
			$table_officedata->addSelect('data', 'officedata');		
			$table_officedata->setJoinType('LEFT');
			$table_officedata->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
			$data['query']->addJoinTable($table_officedata);
		}
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
		$file->officedata  = $this->GenerateOfficeData($file); 	// Get PE data with pefile lib
		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Update table
		$queryobj 	  = new QueryBuilder();
		$table_officedata = new QueryTable('samples_officedata');
		if ($create) 
		{
			$table_officedata->setInsert(array(
				new QueryUpdate('md5', 	$database->escape_string($file->md5)),
				new QueryUpdate('data', $database->escape_string($file->officedata))
			));
		}
		else 
		{
			$table_officedata->setUpdate(array(
				new QueryUpdate('data', $database->escape_string($file->officedata))
			));
			$table_officedata->addWhere(new QueryWhere('md5', $database->escape_string($file->md5), '='));
		}				
		$queryobj->addTable($table_officedata);	
		$results = $database->Execute($queryobj);	
		return True;
	}
	
	private function GenerateOfficeData($file)
	{
		// Filter
		if (isset($file->mime) 
		&& $file->mime != "application/msword"
		&& $file->mime != "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
		&& $file->mime != "application/vnd.openxmlformats-officedocument.wordprocessingml.template"
		&& $file->mime != "application/vnd.ms-word.document.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-word.template.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-excel"
		&& $file->mime != "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
		&& $file->mime != "application/vnd.openxmlformats-officedocument.spreadsheetml.template"
		&& $file->mime != "application/vnd.ms-excel.sheet.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-excel.template.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-excel.addin.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-excel.sheet.binary.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-powerpoint"
		&& $file->mime != "application/vnd.openxmlformats-officedocument.presentationml.presentation"
		&& $file->mime != "application/vnd.openxmlformats-officedocument.presentationml.template"
		&& $file->mime != "application/vnd.openxmlformats-officedocument.presentationml.slideshow"
		&& $file->mime != "application/vnd.ms-powerpoint.addin.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-powerpoint.presentation.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-powerpoint.template.macroEnabled.12"
		&& $file->mime != "application/vnd.ms-powerpoint.slideshow.macroEnabled.12") 
		{
			$data = new stdClass();
			$data->valid = false;
			$data->error = "Not an office document";
			return json_encode($data);
		}
		
		$command = 'python "'.__DIR__.'/officefile/officeparse.py" "'.$file->path.'"';
		ob_start();
		system($command, $retcode);
		$output = ob_get_contents();
		ob_end_clean();
		if ($retcode == 0 || $retcode == 1)
			return $output;
		return '';
	}
	
	public function OnCreateDatabase()
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return false;
		
		$success = true;		
		$table_sql = "
		CREATE TABLE IF NOT EXISTS `samples_officedata` (
		  `md5` varchar(32) NOT NULL,
		  `data` longtext NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>officedata table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing officedata table.</p>";
			$success = false;
		}
		
		$table_sql = "
		ALTER TABLE `samples_officedata`
  		  ADD PRIMARY KEY (`md5`);
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>officedata table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing officedata table.</p>";
			$success = false;
		}
		
		return $success;
	}
}