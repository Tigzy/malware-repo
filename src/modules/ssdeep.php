<?php

require_once(__DIR__.'/module.php');

class SSDEEP extends IModule
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
		$queryobj 		  = new QueryBuilder();
		$table_ssdeep = new QueryTable('samples_ssdeep');
		$table_ssdeep->setDelete(True);
		$table_ssdeep->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_ssdeep);	
		$results = $database->Execute($queryobj);
	}
	
	public function OnGetInfos(&$data)
	{
		//TODO, check if we need a cuckoo analysis
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
		
		$table_ssdeep = new QueryTable('samples_ssdeep');
		$table_ssdeep->setJoinType('LEFT');
		$table_ssdeep->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$table_ssdeep->addWhere(new QueryWhere('data', 'NULL', 'IS', 'int'));
		$queryobj->addJoinTable($table_ssdeep);	
		
		$queryobj->setLimits(0, 10);
		$results = $database->Execute($queryobj);
		
		// Get files to update
		foreach($results as $result) 
		{
			$file = $this->execute_callback('on_get_file', array($result['md5']));
			if (!$file) continue;
			if ($file->locked) continue;
			
			// Write to output	
			echo "[SSDEEP] Processing: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
			
			$this->RefreshData($file, $database);
			
			// Write to output			
			echo "[SSDEEP] Processed: " . $file->md5 . "<br/>\n";
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
			$table_ssdeep = new QueryTable('samples_ssdeep');
			$table_ssdeep->setSelect(array('data' => 'ssdeep'));	
			$table_ssdeep->setJoinType('LEFT');
			$table_ssdeep->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
			
			$filters = $data['filters'];
			if (isset($filters->ssdeep) && !empty($filters->ssdeep)) $table_ssdeep->addWhere(new QueryWhere('ssdeep', '%' . $this->escape_string($filters->ssdeep) . '%', 'LIKE', 'text'));
			
			$data['query']->addJoinTable($table_ssdeep);
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
		$file->ssdeep = $this->GetSsdeep($file); // Compute ssdeep
		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Update table
		$queryobj 	  = new QueryBuilder();
		$table_ssdeep = new QueryTable('samples_ssdeep');
		if ($create) 
		{
			$table_ssdeep->setInsert(array(
				new QueryUpdate('md5', 	$database->escape_string($file->md5)),
				new QueryUpdate('data', $database->escape_string($file->ssdeep))
			));
		}
		else 
		{
			$table_ssdeep->setUpdate(array(
				new QueryUpdate('data', $database->escape_string($file->ssdeep))
			));
			$table_ssdeep->addWhere(new QueryWhere('md5', $database->escape_string($file->md5), '='));
		}				
		$queryobj->addTable($table_ssdeep);	
		$results = $database->Execute($queryobj);	
		return True;
	}
	
	private function GetSsdeep($file)
	{
		$command = 'python "'.__DIR__.'/ssdeep/ssdeepparse.py" "'.$file->path.'"';
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
		CREATE TABLE IF NOT EXISTS `samples_ssdeep` (
		  `md5` varchar(32) NOT NULL,
		  `data` text NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>ssdeep table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing ssdeep table.</p>";
			$success = false;
		}
		
		$table_sql = "
		ALTER TABLE `samples_ssdeep`
  		  ADD PRIMARY KEY (`md5`);
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>ssdeep table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing ssdeep table.</p>";
			$success = false;
		}
		
		return $success;
	}
}