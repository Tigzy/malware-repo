<?php

require_once(__DIR__.'/module.php');
require_once(__DIR__.'/../utils.php');

class PDFData extends IModule
{	
	public function OnFileUpload(&$file)
	{
		$this->CreateData($file);		
		$file->pdfdata = "";	// This is quite big, not needed at file upload
	}
	
	public function OnFileDelete(&$md5)
	{
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Delete from table
		$queryobj 	   = new QueryBuilder();
		$table_pdfdata = new QueryTable('samples_pdfdata');
		$table_pdfdata->setDelete(True);
		$table_pdfdata->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_pdfdata);	
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
		
		$table_pdfdata = new QueryTable('samples_pdfdata');
		$table_pdfdata->setJoinType('LEFT');
		$table_pdfdata->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$table_pdfdata->addWhere(new QueryWhere('data', 'NULL', 'IS', 'int'));
		$queryobj->addJoinTable($table_pdfdata);	
		
		$queryobj->setLimits(0, 10);
		$results = $database->Execute($queryobj);
		
		// Get files to update
		foreach($results as $result) 
		{
			$file = $this->execute_callback('on_get_file', array($result['md5']));
			if (!$file) continue;
			if ($file->locked) continue;
			
			// Write to output	
			echo "[PDFData] Processing: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
			
			$this->RefreshData($file, $database);
			
			// Write to output			
			echo "[PDFData] Processed: " . $file->md5 . "<br/>\n";
			ob_flush();
		    flush();
	    }
	}
	
	public function pdfdatascan(&$data) 
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
	
	public function getpdfdata(&$data) 
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
		
		$data->response($results[0]["pdfdata"],200);
		return True;
	}
	
	public function dumppdfstream(&$data) 
	{
		global $user;
		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "GET"){ return false; }	
		
		$md5 = $data->getParameter("hash");		
		if (!$md5) { $data->response('missing hash parameter',400); return false; }	
		
		$stream_id = $data->getParameter("stream_id");		
		if (!$stream_id) { $data->response('missing stream_id parameter',400); return false; }	
		
		$file = $this->execute_callback('on_get_file', array($md5));
		if (!$file) { $data->response('unable to dump stream',400); return false; }	
		
		$stream = $this->DumpPDFStreamImpl($file, $stream_id);
		$stream = rtrim($stream, "\r\n");
		
		header('Content-Description: File Transfer');
	    header('Content-Type: application/octet-stream');
	    header('Content-Disposition: attachment; filename="stream.dmp"');
		
	    echo $stream;
		return True;
	}
	
	public function submitpdfstream(&$data) 
	{
		global $user;
		
		// Called by API on unknown Action, $data is a RESTAPI object
		if($data->get_request_method() != "GET"){ return false; }	
		
		$md5 = $data->getParameter("hash");		
		if (!$md5) { $data->response('missing hash parameter',400); return false; }	
		
		$stream_id = $data->getParameter("stream_id");		
		if (!$stream_id) { $data->response('missing stream_id parameter',400); return false; }	
		
		$file = $this->execute_callback('on_get_file', array($md5));
		if (!$file) { $data->response('unable to dump stream',400); return false; }	
		
		$stream = $this->DumpPDFStreamImpl($file, $stream_id);
		$stream = rtrim($stream, "\r\n");
		
		// Dump to file
		$path = tempnam($GLOBALS["config"]["path"]["tmp"], "dump_");	
		$name = $md5 . '_stream' . strval($stream_id);
		if (!file_put_contents($path, $stream)) { $data->response('unable to dump stream',400); return false; }	
		
		// Submit as file
		$file = $this->execute_callback('on_submit_file', array($path, $name));
		if ($file) {
			$data->response(json_encode($file),200);
			return true;
		}
		$data->response('unable to dump stream',400);
		return false;
	}
	
	public function GetData($md5, MRFDatabase &$database)
	{
		$queryobj      = new QueryBuilder();
		$table_pdfdata = new QueryTable('samples_pdfdata');
		$table_pdfdata->setSelect(array('data' => 'pdfdata'));			
		$table_pdfdata->addWhere(new QueryWhere('md5', $database->escape_string($md5), '='));
		$queryobj->addTable($table_pdfdata);	
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
			$table_pdfdata = new QueryTable('samples_pdfdata');
			$table_pdfdata->addSelect('data', 'pdfdata');		
			$table_pdfdata->setJoinType('LEFT');
			$table_pdfdata->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
			$data['query']->addJoinTable($table_pdfdata);
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
		$file->pdfdata  = $this->GeneratePDFData($file); 	// Get PE data with pefile lib
		
		$database = $this->execute_callback('on_get_database_object');
		if (!$database) return;
		
		// Update table
		$queryobj 	  = new QueryBuilder();
		$table_pdfdata = new QueryTable('samples_pdfdata');
		if ($create) 
		{
			$table_pdfdata->setInsert(array(
				new QueryUpdate('md5', 	$database->escape_string($file->md5)),
				new QueryUpdate('data', $database->escape_string($file->pdfdata))
			));
		}
		else 
		{
			$table_pdfdata->setUpdate(array(
				new QueryUpdate('data', $database->escape_string($file->pdfdata))
			));
			$table_pdfdata->addWhere(new QueryWhere('md5', $database->escape_string($file->md5), '='));
		}				
		$queryobj->addTable($table_pdfdata);	
		$results = $database->Execute($queryobj);	
		return True;
	}
	
	private function GeneratePDFData($file)
	{
		// Filter
		if (isset($file->mime) && $file->mime != "application/pdf") 
		{
			$data = new stdClass();
			$data->valid = false;
			$data->error = "Not a PDF file";
			return json_encode($data);
		}
		
		$command = 'python "'.__DIR__.'/pdffile/pdfparse.py" --file "'.$file->path.'"';
		ob_start();
		system($command, $retcode);
		$output = ob_get_contents();
		ob_end_clean();
		if ($retcode == 0 || $retcode == 1)
			return $output;
		return '';
	}
	
	private function DumpPDFStreamImpl($file, $stream_id)
	{		
		$command = 'python "'.__DIR__.'/pdffile/pdfparse.py" --file "'.$file->path.'" --dump ' . strval($stream_id);
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
		CREATE TABLE IF NOT EXISTS `samples_pdfdata` (
		  `md5` varchar(32) NOT NULL,
		  `data` longtext NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>pdfdata table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing pdfdata table.</p>";
			$success = false;
		}
		
		$table_sql = "
		ALTER TABLE `samples_pdfdata`
  		  ADD PRIMARY KEY (`md5`);
		";	
		
		if($database->ExecuteQuery($table_sql))
		{
			echo "<p>pdfdata table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing pdfdata table.</p>";
			$success = false;
		}
		
		return $success;
	}
}