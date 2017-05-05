<?php

class UCPage 
{		
	private $page_id	= NULL;
	private $page		= NULL;
	private $is_private = False;
	
	public static function Create($pages)
	{
		global $user_db;
		return $user_db->PageCreate($pages);
	}
	
	public static function Delete($pages)
	{
		global $user_db;
		return $user_db->PageDelete($pages);
	}
	
	public static function Exists($id)
	{
		global $user_db;
		return $user_db->PageExists($id);
	}
		
	public static function GetPages()
	{
		global $user_db;
		$pages = $user_db->PagesFullData();
		$pages_arr = array();
		foreach ($pages as $page) {
			$page_obj = new UCPage($page['id'], False);
			$page_obj->SetFullData($page);
			$pages_arr[] = $page_obj;
		}
		return $pages_arr;
	}
	
	public static function GetByPermission($permission_id)
	{
		global $user_db;
		return $user_db->PagesGetByPermission($permission_id);
	}
	
	public static function GetByName($page)
	{
		global $user_db;
		$data = $user_db->PageFullData(NULL, $page);
		if (empty($data)) {
			return NULL;
		}		
		$page_obj = new UCPage($data['id'], False);
		$page_obj->SetFullData($data);
		return $page_obj;
	}
		
	//================================================
	
	public function __construct($id, $fetch_data = True)
	{
		$this->page_id= $id;
		if ($fetch_data) {
			global $user_db;
			$data = $user_db->PageFullData($this->page_id);
			$this->SetFullData($data);
		}
	}
	
	public function __destruct()
	{
		
	}
	
	public function IsPrivate() {
		return $this->is_private;
	}
	
	public function SetFullData($data)
	{
		if (isset($data['page'])) 		$this->page 		= $data['page'];
		if (isset($data['private'])) 	$this->is_private 	= $data['private'];
	}
	
	public function UpdatePrivate($new_private)
	{
		global $user_db;
		return $user_db->PageUpdatePrivate($this->page_id, $new_private);
	}
	
	public function AddPermission($permission)
	{
		return UCPermission::AddPagePermission($this->page_id, $permission);
	}
	
	public function RemovePermission($permission)
	{
		return UCPermission::RemovePagePermission($this->page_id, $permission);
	}
	
	public function Permissions()
	{
		return UCPermission::GetPagePermissions($this->page_id);
	}
	
	public function Name() {
		return $this->page;
	}
	
	public function Id() {
		return $this->page_id;
	}
}

?>