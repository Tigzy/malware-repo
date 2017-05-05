<?php

require_once(__DIR__.'/lib/querybuilder.php');

class MRFDatabase
{
	private $host;
	private $name;
	private $user;
	private $pass;
	private $items_per_page;
	private $mysqli;
	private $last_error;
	
	
	public function __construct($db_host, $db_name, $db_user, $db_pass, $items_per_page = 50) 
	{
		$this->host 		= $db_host;
		$this->name 		= $db_name;
		$this->user 		= $db_user;
		$this->pass 		= $db_pass;
		$this->items_per_page = $items_per_page;
		$this->mysqli 		= NULL;
		$this->last_error 	= 0;
		
		$this->Connect();
	}
	
	public function __destruct() 
	{
		if ( $this->mysqli != NULL )
		{
			$this->mysqli->close();
			$this->mysqli = NULL;
		}
	}
	
	private function Connect() 
	{		
		// Create a new mysqli object with database connection parameters
		$this->mysqli = new mysqli($this->host, $this->user, $this->pass, $this->name);
		if($this->mysqli->connect_errno) 
		{
			$this->last_error 	= $this->mysqli->connect_errno;
			$this->mysqli 		= NULL;
			return False;
		}
		return True;
	}
	
	private static function utf8_encode_deep(&$input) 
	{
		if (is_string($input)) {
			$input = utf8_encode($input);
		} else if (is_array($input)) {
			foreach ($input as &$value) {
				self::utf8_encode_deep($value);
			}
			unset($value);
		} else if (is_object($input)) {
			$vars = array_keys(get_object_vars($input));
			foreach ($vars as $var) {
				self::utf8_encode_deep($input->$var);
			}
		}
	}
	
	public function escape_string($str){
		return $this->mysqli->real_escape_string($str);	
	}
	
	//========================================================== 
	// PUBLIC part
	
	public function IsConnected() {
		return $this->mysqli != NULL;
	}
	
	public function LastError() {
		return $this->last_error;
	}
	
	public function Execute(QueryBuilder $queryobj)
	{
		$query 	= $queryobj->build();
		$stmt 	= $this->mysqli->query($query);
		$results = array();
		while (is_object($stmt) && $result = $stmt->fetch_assoc()) {
			$results[] = $result;	
		}
		if (is_object($stmt)) $stmt->close();		
		return $results;
	}
	
	public function ExecuteQuery($query)
	{
		$stmt = $this->mysqli->prepare($query);
		if($stmt->execute()) {
			return True;
		}
		return False;
	}
	
	//==================================================
	
	public function GetFilesCount() 
	{
		$stmt = $this->mysqli->prepare("SELECT count(*) as count FROM samples");
		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->close();		
		return (int) $count;
	}
	
	public function GetFilesTotalSize() 
	{
		$stmt = $this->mysqli->prepare("SELECT SUM(size) as total FROM samples");
		$stmt->execute();
		$stmt->bind_result($total);
		$stmt->fetch();
		$stmt->close();		
		return (int) $total;
	}	
	
	public function UpdateThreat($md5, $new_vendor)
	{		
		$stmt = $this->mysqli->prepare("UPDATE samples SET threat=? WHERE md5=?");
		$stmt->bind_param("ss", $new_vendor, $md5);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UpdateSha256($md5, $sha256)
	{		
		$stmt = $this->mysqli->prepare("UPDATE samples SET sha256=? WHERE md5=?");
		$stmt->bind_param("ss", $sha256, $md5);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UpdateUploader($md5, $new_user)
	{
		$stmt = $this->mysqli->prepare("UPDATE samples SET uploader=? WHERE md5=?");
		$stmt->bind_param("is", $new_user, $md5);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UpdateComment($md5, $new_comment)
	{
		$stmt = $this->mysqli->prepare("UPDATE samples_metas SET comment=? WHERE md5=?");
		$stmt->bind_param("ss", $new_comment, $md5);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UpdateLocked($md5, $locked)
	{
		$is_locked = $locked ? 1 : 0;
		$stmt = $this->mysqli->prepare("UPDATE samples_metas SET locked=? WHERE md5=?");
		$stmt->bind_param("is", $is_locked, $md5);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function DeleteTags($md5)
	{		
		$stmt = $this->mysqli->prepare("DELETE FROM samples_tag WHERE md5=?");		
		$stmt->bind_param("s", $md5);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UpdateTags($md5, $new_tags)
	{		
		$this->DeleteTags($md5);
		$stmt = $this->mysqli->prepare("INSERT INTO samples_tag (md5,tag) VALUES (?,?)");		
		$tags = explode(",", $new_tags);
		foreach( $tags as $tag ) {
			$stmt->bind_param("ss", $md5, $tag);
			$stmt->execute();
		}		
		$stmt->close();
		return True;
	}
	
	public function DeleteUrls($md5)
	{		
		$stmt = $this->mysqli->prepare("DELETE FROM samples_url WHERE md5=?");		
		$stmt->bind_param("s", $md5);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UpdateUrls($md5, $new_urls)
	{		
		$this->DeleteUrls($md5);
		$stmt = $this->mysqli->prepare("INSERT INTO samples_url (md5,name,url) VALUES (?,?,?)");		
		$urls = explode(",", $new_urls);
		foreach( $urls as $url ) {
			$url_splitted = explode("|", $url);
			if (count($url_splitted) != 2) continue;			
			$stmt->bind_param("sss", $md5, $url_splitted[0], $url_splitted[1]);
			$stmt->execute();
		}		
		$stmt->close();
		return True;
	}
	
	public function UpdateFavorite($hash, $user, $favorite)
	{
		if ($favorite) {
			$stmt = $this->mysqli->prepare("INSERT INTO samples_favorite (md5, user) VALUES (?,?)");
		} else {
			$stmt = $this->mysqli->prepare("DELETE FROM samples_favorite WHERE md5=? AND user=?");
		}		
		$stmt->bind_param("ss", $hash, $user);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	function DeleteFile($md5)
	{
		$stmt = $this->mysqli->prepare("DELETE FROM samples WHERE md5=?");		
		$stmt->bind_param("s", $md5);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function GetFile($md5, $user = null, $filter_function = null)
	{	    
		$filters = new stdClass();
		$filters->md5 = $md5;
		$results = $this->GetFiles($filters, $user, $filter_function, True);
		if (!empty($results)) {
			return reset($results);
		}
		return NULL;
	}
	
	public function GetFiles($filters, $user = null, $filter_function = null, $extended = False)
	{		
		//=================== pagination
		$page = 1; 
		if(isset($filters->page)) {	
			$page = $filters->page;
			if ($page < 1) $page = 1;
		}			
		
		$offset 	= ($page - 1) * $this->items_per_page;				
		$queryobj 	= new QueryBuilder();
		$queryobj->setLimits($offset, $this->items_per_page);
		
		//=================== Main table
		$table_samples = new QueryTable('samples');
		$table_samples->setSelect(array('md5' => '', 'filename' => '', 'threat' => '', 'size' => '', 'date' => '', 'uploader' => '', 'sha256' => ''));		
		$table_samples->addGroupBy('md5');
		$table_samples->addOrderBy(new QueryOrderBy('date', 'DESC'));		
		if (isset($filters->date) && !empty($filters->date)) 			$table_samples->addWhere(new QueryWhere('date', '%' . $this->escape_string($filters->date) . '%', 'LIKE', 'text'));
		if (isset($filters->md5) && !empty($filters->md5)) 				$table_samples->addWhere(new QueryWhere('md5', $this->escape_string($filters->md5), '=', 'text'));
		if (isset($filters->sha256) && !empty($filters->sha256)) 	    $table_samples->addWhere(new QueryWhere('sha256', $this->escape_string($filters->sha256), '=', 'text'));
		if (isset($filters->filename) && !empty($filters->filename)) 	$table_samples->addWhere(new QueryWhere('filename', '%' . $this->escape_string($filters->filename) . '%', 'LIKE', 'text'));
		if (isset($filters->threat) && !empty($filters->threat)) 		$table_samples->addWhere(new QueryWhere('threat', '%' . $this->escape_string($filters->threat) . '%', 'LIKE', 'text'));
		if (isset($filters->size) && !empty($filters->size)) 
		{
			if (0 === strpos($filters->size, '>')) 			$table_samples->addWhere(new QueryWhere('size', $this->escape_string(substr($filters->size, 1)), '>=', 'int'));
			else if (0 === strpos($filters->size, '<')) 	$table_samples->addWhere(new QueryWhere('size', $this->escape_string(substr($filters->size, 1)), '<=', 'int'));
			else 											$table_samples->addWhere(new QueryWhere('size', $this->escape_string($filters->size), '<=', 'int'));
			
		}
		if (isset($filters->uploader) && !empty($filters->uploader)) 	 $table_samples->addWhere(new QueryWhere('uploader', '(' . $this->escape_string(implode(',', array_map('intval', $filters->uploader)), false) . ')', 'IN', 'int'));
		else if (isset($filters->uploader) && empty($filters->uploader)) $table_samples->addWhere(new QueryWhere('uploader', '(-2)', 'IN', 'int'));	
		$queryobj->addTable($table_samples);	
		
		//=================== Favorite table
		$table_favorite = new QueryTable('samples_favorite');
		$table_favorite->setSelect(array('user' => 'favorite'));		
		if (isset($filters->favorite) && !empty($filters->favorite) && $filters->favorite != 'none' && $user != null) 
		{			
			if ($filters->favorite == 'fav') 	$table_favorite->addWhere(new QueryWhere('user', 'NULL', 'IS NOT', 'int'));
			else 								$table_favorite->addWhere(new QueryWhere('user', 'NULL', 'IS', 'int'));
		}
		$table_favorite->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$table_favorite->addJoinWhere(new QueryWhere('user', strval($user), '=', 'int'));
		$table_favorite->setJoinType('LEFT');
		$queryobj->addJoinTable($table_favorite);
		
		//=================== Metas table
		$table_metas = new QueryTable('samples_metas');
		$table_metas->setSelect(array('locked' => ''));	
		if ($extended) $table_metas->addSelect('comment','');	
		if (isset($filters->comment) && !empty($filters->comment)) 	$table_metas->addWhere(new QueryWhere('comment', '%' . $this->escape_string($filters->comment) . '%', 'LIKE', 'text'));
		$table_metas->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$queryobj->addJoinTable($table_metas);
		
		//=================== Tags table
		$table_tags = new QueryTable('samples_tag');
		$table_tags->setRawSelect(array("IFNULL(GROUP_CONCAT(samples_tag.tag SEPARATOR ','), '')" => 'tags'));	
		if (isset($filters->tags) && !empty($filters->tags))  $table_tags->addWhere(new QueryWhere('tag', '%' . $this->escape_string($filters->tags) . '%', 'LIKE', 'text'));
		$table_tags->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
		$table_tags->setJoinType('LEFT');
		$queryobj->addJoinTable($table_tags);
		
		//=================== Urls table
		if ($extended)
		{
			$table_urls = new QueryTable('samples_url');
			$table_urls->setRawSelect(array("IFNULL(GROUP_CONCAT(CONCAT(samples_url.name, '|', samples_url.url) SEPARATOR ','), '')" => 'urls'));	
			if (isset($filters->urls) && !empty($filters->urls))  $table_urls->addWhere(new QueryWhere('url', '%' . $this->escape_string($filters->urls) . '%', 'LIKE', 'text'));
			$table_urls->addJoinWhere(new QueryWhere('md5', 'samples.md5', '=', 'field'));
			$table_urls->setJoinType('LEFT');
			$queryobj->addJoinTable($table_urls);
		}
			
		//=================== Call filter
		if ($filter_function) 
		{
			$data = array('name' => 'pre_get_files', array('filters' => &$filters, 'query' => &$queryobj, 'database' => &$this, 'extended' => $extended));
			call_user_func_array($filter_function, $data);
		}
			
		//=================== Execute query
		$query = $queryobj->build();
		$stmt = $this->mysqli->query($query);
		$results = array();
		while ($result = $stmt->fetch_assoc()) {
			$result['favorite'] 	= (int)$result['favorite'];
	        $result["size"]			= (int)$result["size"];
	        $result["uploader"]		= (int)$result["uploader"];
	        $result["locked"]		= (int)$result["locked"];
			$results[] 				= $result;	
		}
		$stmt->close();		
		
		//=================== Call filter
		if ($filter_function) 
		{
			$data = array('name' => 'post_get_files', array('results' => &$results, 'extended' => $extended));
			call_user_func_array($filter_function, $data);
		}
		
		return $results;
	}
		
	public function AddFile($file)
	{	
		$stmt = $this->mysqli->prepare("INSERT INTO samples (md5,filename,threat,size,date,uploader,sha256) VALUES (?,?,?,?,NOW(),?,?)");
		$stmt->bind_param("sssiis", $file->md5, $file->filename, $file->threat, $file->size, $file->uploader, $file->sha256);
		$stmt->execute();
		$stmt->close();
		
		$stmt = $this->mysqli->prepare("INSERT INTO samples_metas (md5,comment,locked) VALUES (?,?,0)");
		$stmt->bind_param("ss", $file->md5, $file->comment);
		$stmt->execute();
		$stmt->close();
		
		$this->UpdateTags($file->md5, $file->tags);
		$this->UpdateUrls($file->md5, $file->urls);	
	}
	
	public function FileExists($md5)
	{		
		$stmt = $this->mysqli->prepare("SELECT count(*) as count FROM samples WHERE md5=?");
		$stmt->bind_param("s", $md5);
		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->close();		
		return $count > 0;
	}
	
	public function GetSubmissions($days_count = -1) 
	{
		$stmt = $this->mysqli->prepare("SELECT DATE(date) as date, count(*) as count FROM samples" 
				. ($days_count == -1 ? "" : " WHERE DATE(date) > DATE_SUB(NOW(), INTERVAL " . strval($days_count) . " DAY) ") . " GROUP BY DATE(date) ORDER BY date ASC");
		$stmt->execute();
		$stmt->bind_result($date, $count);
		$results = array();
		while ($stmt->fetch()) {
			$results[] = array('date' => $date, 'count' => $count);
		}
		$stmt->close();	
		return $results;
	}
	
	public function GetTags() 
	{
		$stmt = $this->mysqli->prepare("SELECT tag, COUNT(*) as count FROM samples_tag GROUP BY tag");
		$stmt->execute();
		$stmt->bind_result($tag, $count);
		$results = array();
		while ($stmt->fetch()) {
			$results[] = array('tag' => $tag, 'count' => $count);
		}
		$stmt->close();	
		return $results;
	}
	
	public function GetSubmissionsPerUser() 
	{
		$stmt = $this->mysqli->prepare("SELECT uploader, COUNT(*) as count FROM samples GROUP BY uploader ORDER BY count DESC");
		$stmt->execute();
		$stmt->bind_result($uploader, $count);
		$results = array();
		while ($stmt->fetch()) {
			$results[] = array('uploader' => $uploader, 'count' => $count);
		}
		$stmt->close();	
		return $results;
	}
	
	public function Create()
	{
		$success = true;
		
		$samples_sql = "
		CREATE TABLE IF NOT EXISTS `samples` (
		  `md5` varchar(32) NOT NULL,
		  `filename` text NOT NULL,
		  `threat` text NOT NULL,
		  `size` int(11) NOT NULL,
		  `date` datetime NOT NULL,
		  `uploader` text NOT NULL,
		  `sha256` varchar(64) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";
		
		$stmt = $this->mysqli->prepare($samples_sql);
		if($stmt->execute())
		{
			echo "<p>samples table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing samples table.</p>";
			$success = false;
		}
		
		$samples_sql = "
		ALTER TABLE `samples`
		  ADD PRIMARY KEY (`md5`),
		  ADD KEY `sha256` (`sha256`) USING BTREE;
		";	
		
		$stmt = $this->mysqli->prepare($samples_sql);
		if($stmt->execute())
		{
			echo "<p>samples table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing samples table.</p>";
			$success = false;
		}
		
		//=========================================
		
		$samples_favorite_sql = "
		CREATE TABLE IF NOT EXISTS `samples_favorite` (
		  `md5` varchar(32) NOT NULL,
		  `user` int(11) NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		$stmt = $this->mysqli->prepare($samples_favorite_sql);
		if($stmt->execute())
		{
			echo "<p>favorite table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing favorite table.</p>";
			$success = false;
		}
		
		$samples_favorite_sql = "
		ALTER TABLE `samples_favorite`
  		  ADD UNIQUE KEY `unique_fav` (`md5`,`user`) USING BTREE;
		";	
		
		$stmt = $this->mysqli->prepare($samples_favorite_sql);
		if($stmt->execute())
		{
			echo "<p>favorite table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing favorite table.</p>";
			$success = false;
		}
		
		//=========================================
		
		$samples_metas_sql = "
		CREATE TABLE IF NOT EXISTS `samples_metas` (
		  `md5` varchar(32) NOT NULL,
		  `comment` longtext NOT NULL,
		  `locked` int(11) NOT NULL DEFAULT '0'
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		$stmt = $this->mysqli->prepare($samples_metas_sql);
		if($stmt->execute())
		{
			echo "<p>metas table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing metas table.</p>";
			$success = false;
		}
		
		$samples_metas_sql = "
		ALTER TABLE `samples_metas`
		  ADD PRIMARY KEY (`md5`);
		";	
		
		$stmt = $this->mysqli->prepare($samples_metas_sql);
		if($stmt->execute())
		{
			echo "<p>metas table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing metas table.</p>";
			$success = false;
		}
		
		//=========================================
		
		$samples_tags_sql = "
		CREATE TABLE `samples_tag` (
		  `md5` varchar(32) NOT NULL,
		  `tag` text NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		$stmt = $this->mysqli->prepare($samples_tags_sql);
		if($stmt->execute())
		{
			echo "<p>tags table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing tags table.</p>";
			$success = false;
		}
		
		$samples_tags_sql = "
		ALTER TABLE `samples_tag`
  		  ADD UNIQUE KEY `unique_tag` (`md5`,`tag`(32)) USING BTREE;
		";	
		
		$stmt = $this->mysqli->prepare($samples_tags_sql);
		if($stmt->execute())
		{
			echo "<p>tags table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing tags table.</p>";
			$success = false;
		}
		
		//=========================================
		
		$samples_urls_sql = "
		CREATE TABLE `samples_url` (
		  `md5` varchar(32) NOT NULL,
		  `name` text NOT NULL,
		  `url` text NOT NULL
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";	
		
		$stmt = $this->mysqli->prepare($samples_urls_sql);
		if($stmt->execute())
		{
			echo "<p>urls table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing urls table.</p>";
			$success = false;
		}
		
		$samples_urls_sql = "
		ALTER TABLE `samples_url`
		  ADD KEY `md5` (`md5`);
		";	
		
		$stmt = $this->mysqli->prepare($samples_urls_sql);
		if($stmt->execute())
		{
			echo "<p>urls table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing urls table.</p>";
			$success = false;
		}
		
		return $success;
	}
}
