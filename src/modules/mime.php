<?php

require_once(__DIR__.'/module.php');

class Mime extends IModule
{	
	public function OnFileUpload(&$file)
	{
		$this->CreateData($file);		
	}
	
	public function OnFileDelete(&$md5)
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Delete from table
		$queryobj 	= new QueryBuilder();
		$table_mime = new QueryTable('samples_mime');
		$table_mime->setDelete(True);
		$table_mime->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_mime);	
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
		
		$table_mime = new QueryTable('samples_mime');
		$table_mime->setJoinType('LEFT');
		$table_mime->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$table_mime->addWhere(new QueryWhere('data', 'NULL', 'IS', 'int'));
		$queryobj->addJoinTable($table_mime);	
		
		$queryobj->setLimits(0, 10);
		$results = $database->Execute($queryobj);
		
		// Get files to update
		foreach($results as $result) 
		{
			$file = $this->execute_callback('on_get_file', array($result['md5']));
			if (!$file) continue;
			if ($file->locked) continue;
			
			// Write to output	
			echo "[MIME] Processing: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
			
			$this->RefreshData($file, $database);
			
			// Write to output			
			echo "[MIME] Processed: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
	    }
	}
	
	public function OnPreGetFilesDatabaseQuery(&$data)
	{		
		//data[filters] is search filters
		//data[query] is querybuilder	
		//data[extended] is the extended bool
		if ($data['extended'])
		{
			$table_mime = new QueryTable('samples_mime');
			$table_mime->setSelect(array('data' => 'mime'));	
			$table_mime->setJoinType('LEFT');
			$table_mime->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
			
			$filters = $data['filters'];
			if (isset($filters->mime) && !empty($filters->mime)) $table_mime->addWhere(new QueryWhere('data', '%' . $this->escape_string($filters->mime) . '%', 'LIKE', 'text'));
			
			$data['query']->addJoinTable($table_mime);
		}
	}
	
	public function getmimedata(&$data) 
	{			
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "GET"){ $data->response('',406); return false; }		
		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) { $data->response('Unable to get results',400); return false; }		
		
		$queryobj 	= new QueryBuilder();
		$table_mime = new QueryTable('samples_mime');
		$table_mime->setSelect(array('data' => 'mime'));	
		$table_mime->addRawSelect('COUNT(*)', 'count');			
		$table_mime->setGroupBy(array('data'));
		$queryobj->addTable($table_mime);	
		$results = $database->Execute($queryobj);	
		
		$labels 		= array();
		$points 		= array();							
		foreach($results as $val)
		{
		    $labels[] = $val["mime"];
		    $points[] = $val["count"];
		}				
		$data_new = new stdClass();
		$data_new->labels 			= $labels;
		$data_new->points 			= $points;
		
		$data->response(json_encode($data_new),200);
		return true;
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
		$file->mime = mime_content_type($file->path);
		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Update table
		$queryobj 	= new QueryBuilder();
		$table_mime = new QueryTable('samples_mime');
		if ($create) 
		{
			$table_mime->setInsert(array(
				new QueryUpdate('md5', 	$database->escape_string($file->md5)),
				new QueryUpdate('data', $database->escape_string($file->mime))
			));
		}
		else 
		{
			$table_mime->setUpdate(array(
				new QueryUpdate('data', $database->escape_string($file->mime))
			));
			$table_mime->addWhere(new QueryWhere('md5', $database->escape_string($file->md5), '='));
		}				
		$queryobj->addTable($table_mime);	
		$results = $database->Execute($queryobj);	
		return True;
	}
	
	public function OnCreateDatabase()
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return false;
		
		$success = true;		
		$table_sql = "
		CREATE TABLE IF NOT EXISTS `samples_mime` (
		  `md5` varchar(32) NOT NULL,
		  `data` text NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>mime table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing mime table.</p>";
			$success = false;
		}
		
		$table_sql = "
		ALTER TABLE `samples_mime`
  		  ADD PRIMARY KEY (`md5`);
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>mime table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing mime table.</p>";
			$success = false;
		}
		
		return $success;
	}
}