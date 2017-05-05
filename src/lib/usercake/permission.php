<?php

class UCPermission 
{		
	private $perm_id	= NULL;
	private $name		= NULL;
	
	public static function Create($permission)
	{
		global $user_db;
		return $user_db->PermissionCreate($permission);
	}
	
	public static function PermissionsDelete($permissions, &$errors) 
	{
		global $user_db;
		return $user_db->PermissionsDelete($permissions, $errors);		
	}
	
	public static function GetPermissions()
	{
		global $user_db;
		$perms = $user_db->PermissionsFullData();
		$perms_arr = array();
		foreach ($perms as $perm) {
			$perm_obj = new UCPermission($perm['id'], False);
			$perm_obj->SetFullData($perm);
			$perms_arr[] = $perm_obj;
		}
		return $perms_arr;
	}
	
	public static function GetUserPermissions($user_id)
	{
		global $user_db;
		$perms = $user_db->UserPermissions($user_id);
		$perms_arr = array();
		if(!$perms) return $perms_arr;
		foreach ($perms as $perm) {
			$perm_obj = new UCPermission($perm['permission_id'], False);
			$perm_obj->SetFullData($perm);
			$perms_arr[] = $perm_obj;
		}
		return $perms_arr;
	}
	
	public static function GetPagePermissions($page_id)
	{
		global $user_db;
		$perms = $user_db->PagePermissions($page_id);
		$perms_arr = array();
		foreach ($perms as $perm) {
			$perm_obj = new UCPermission($perm['permission_id'], False);
			$perm_obj->SetFullData($perm);
			$perms_arr[] = $perm_obj;
		}
		return $perms_arr;
	}
	
	public static function AddUserPermission($user_id, $permission)
	{
		global $user_db;
		return $user_db->UserAddPermission($user_id, $permission);
	}
	
	public static function RemoveUserPermission($user_id, $permission) 
	{
		global $user_db;
		return $user_db->UserRemovePermission($user_id, $permission);
	}
	
	public static function AddPagePermission($page_id, $permission)
	{
		global $user_db;
		return $user_db->PageAddPermission($page_id, $permission);
	}
	
	public static function RemovePagePermission($page_id, $permission)
	{
		global $user_db;
		return $user_db->PageRemovePermission($page_id, $permission);
	}
	
	public static function Exists($id)
	{
		global $user_db;
		return $user_db->PermissionExists($id);	
	}
	
	public static function NameExists($perm)
	{
		global $user_db;
		return $user_db->PermissionNameExists($perm);
	}
	
	public static function IsPermissionSet(UCPermission $perm_to_find, array $perms)
	{
		foreach ($perms as $perm)
		{
			if ($perm->Id() == $perm_to_find->Id()) {
				return True;	
			}			
		}
		return False;
	}
	
	//================================================
	
	public function __construct($id, $fetch_data = True)
	{
		$this->perm_id = $id;
		if ($fetch_data) {
			global $user_db;
			$data = $user_db->PermissionFullData($this->perm_id);
			$this->SetFullData($data);
		}
	}
	
	public function __destruct()
	{
		
	}
	
	public function SetFullData($data)
	{
		if (isset($data['name'])) $this->name = $data['name'];
	}
	
	public function Update($name)
	{
		global $user_db;
		return $user_db->PermissionUpdate($this->perm_id, $name);
	}
	
	public function Id() {
		return $this->perm_id;
	}
	
	public function Name() {
		return $this->name;
	}
}

?>