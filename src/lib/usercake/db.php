<?php

class UCDatabase
{
	private $host;
	private $name;
	private $user;
	private $pass;
	private $prefix;
	private $mysqli;
	private $last_error;
	
	public function __construct($db_host, $db_name, $db_user, $db_pass, $db_table_prefix) 
	{
		$this->host 		= $db_host;
		$this->name 		= $db_name;
		$this->user 		= $db_user;
		$this->pass 		= $db_pass;
		$this->prefix 		= $db_table_prefix;	
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
	
	//========================================================== 
	// PUBLIC part
	
	public function IsConnected() {
		return $this->mysqli != NULL;
	}
	
	public function LastError() {
		return $this->last_error;
	}
	
	public function Prefix() {
		return $this->prefix;
	}
	
	//==================================================
	// MISC	
	
	//==================================================
	// Settings
	
	public function Settings()
	{
		$stmt = $this->mysqli->prepare("SELECT id, name, value FROM ".$this->prefix."configuration");
		if (!$stmt) return array();
		$stmt->execute();
		$stmt->bind_result($id, $name, $value);
		
		$settings = array();
		while ($stmt->fetch()){
			$settings[$name] = array('id' => $id, 'name' => $name, 'value' => $value);
		}
		$stmt->close();		
		return $settings;
	}
	
	public function SettingSet($id, $value)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."configuration SET value = ? WHERE id = ?");
		foreach ($id as $cfg) {
			$stmt->bind_param("si", $value[$cfg], $cfg);
			$stmt->execute();
		}
		$stmt->close();
		return True;
	}	
	
	//===================================================
	// Sessions Table
	
	public function GetSessionDataFromCookie($cookie_value)
	{
		$stmt = $this->mysqli->prepare("SELECT sessionData FROM ".$this->prefix."sessions WHERE sessionID = ?");
		$stmt->bind_param("s", $cookie_value);
		$stmt->execute();
		$stmt->bind_result($sessionData);
		$row = array();
		while ($stmt->fetch()) {
			$row = array('sessionData' => $sessionData);
			break;
		}
		$stmt->close();
		return isset($row['sessionData']) ? $row['sessionData'] : $row;
	}
	
	public function DeleteExpiredSessions($session_duration)
	{
		$now  = time();
		$stmt = $this->mysqli->prepare("DELETE FROM ".$this->prefix."sessions WHERE ? >= (`sessionStart` + ?)");
		$stmt->bind_param("ii", $now, $session_duration);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function DeleteSession($session)
	{
		$stmt = $this->mysqli->prepare("DELETE FROM ".$this->prefix."sessions WHERE `sessionID` = ?");
		$stmt->bind_param("s", $session);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UpdateSession($session, $data)
	{		
		$newObj = serialize($data);		
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."sessions SET sessionData = ? WHERE sessionID = ?");
		$stmt->bind_param("ss", $newObj, $session);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function CreateSession($session, $data)
	{
		$newObj = serialize($data);
		$stmt = $this->mysqli->prepare("INSERT INTO ".$this->prefix."sessions VALUES (?,?,?)");
		$stmt->bind_param("iss", time(), $newObj, $session);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	//===================================================
	// Users Table
	
	public function UserUpdateLastSignIn($user_id)
	{		
		$time = time();
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET last_sign_in_stamp = ? WHERE id = ?");
		$stmt->bind_param("ii", $time, $user_id);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UserSignupTimeStamp($user_id)
	{		
		$stmt = $this->mysqli->prepare("SELECT sign_up_stamp FROM ".$this->prefix."users WHERE id = ?");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt->bind_result($timestamp);
		$stmt->fetch();
		$stmt->close();
		return ($timestamp);
	}
	
	public function UserActivationtoken($user_id)
	{		
		$stmt = $this->mysqli->prepare("SELECT activation_token FROM ".$this->prefix."users WHERE id = ?");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt->bind_result($activation_token);
		$stmt->fetch();
		$stmt->close();
		return ($activation_token);
	}
	
	public function UserValidateAPIKey($key)
	{		
		$stmt = $this->mysqli->prepare("SELECT count(*) FROM ".$this->prefix."users WHERE activation_token = ?");
		$stmt->bind_param("s", $key);
		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->close();
		return ($count > 0);
	}
	
	public function UserUpdatePassword($user_id, $secure_pass)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET password = ? WHERE id = ?");
		$stmt->bind_param("si", $secure_pass, $user_id);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UserUpdateAvatar($user_id, $avatar)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET avatar = ? WHERE	id = ?");
		$stmt->bind_param("si", $avatar, $user_id);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	public function UserUpdateDisplayName($user_id, $display)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET display_name = ?	WHERE id = ? LIMIT 1");
		$stmt->bind_param("si", $display, $user_id);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	public function UserUpdateEmail($user_id, $email)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET email = ? WHERE id = ?");
		$stmt->bind_param("si", $email, $user_id);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	public function UserUpdateTitle($user_id, $title)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET title = ? WHERE id = ?");
		$stmt->bind_param("si", $title, $user_id);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	public function UserUpdateLastActivationRequest($new_activation_token, $username, $email)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET activation_token = ?, last_activation_request = ? WHERE email = ? AND user_name = ?");
		$stmt->bind_param("ssss", $new_activation_token, time(), $email, $username);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	public function UserUpdatePasswordFromToken($pass, $token)
	{
		$new_token = $this->GenerateActivationToken();		
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET password = ?, activation_token = ? WHERE activation_token = ?");
		$stmt->bind_param("sss", $pass, $new_token, $token);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	public function UserAvatar($user_id)
	{
		$stmt = $this->mysqli->prepare("SELECT avatar FROM ".$this->prefix."users WHERE id = ?");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt->bind_result($avatar);
		$stmt->fetch();
		$stmt->close();
		return ($avatar);
	}
	
	public function UserName($user_id)
	{
		$stmt = $this->mysqli->prepare("SELECT user_name FROM ".$this->prefix."users WHERE id = ?");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt->bind_result($name);
		$stmt->fetch();
		$stmt->close();
		return ($name);
	}
	
	public function UserSetActive($token)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET active = 1 WHERE activation_token = ? LIMIT 1");
		$stmt->bind_param("s", $token);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	public function GenerateActivationToken()
	{
		do {
			$gen = md5(uniqid(mt_rand(), false));
		}
		while($this->ValidateActivationToken($gen));
		return $gen;
	}
	
	public function ValidateActivationToken($token, $lostpass = False)
	{
		if(!$lostpass) {
			$stmt = $this->mysqli->prepare("SELECT active	FROM ".$this->prefix."users WHERE active = 0 AND activation_token = ? LIMIT 1");
		}
		else {			
			$stmt = $this->mysqli->prepare("SELECT active	FROM ".$this->prefix."users WHERE active = 1 AND activation_token = ? AND lost_password_request = 1 LIMIT 1");
		}
		$stmt->bind_param("s", $token);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function UserValidatePermission($user_id, $permission)
	{		
		//Grant access if master user		
		$stmt = $this->mysqli->prepare("SELECT id FROM ".$this->prefix."user_permission_matches WHERE user_id = ? AND permission_id = ? LIMIT 1");
		$access = 0;
		foreach($permission as $check)
		{
			$stmt->bind_param("ii", $user_id, $check);
			$stmt->execute();
			$stmt->store_result();
			if ($stmt->num_rows > 0)
			{
				$access = 1;
				break;
			}
		}
		$stmt->close();
		return ($access == 1);
	}
	
	public function UserToggleLostPasswordRequest($user_id, $enable)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."users SET lost_password_request = ? WHERE id = ? LIMIT 1");
		$stmt->bind_param("si", $enable, $user_id);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	public function UserVerifyPassword($user_id, $password)
	{
		$stmt = $this->mysqli->prepare("SELECT id, password FROM ".$this->prefix."users WHERE id = ? AND password = ? AND active = 1 LIMIT 1");
		$stmt->bind_param("is", $user_id, $password);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		return ($num_returns > 0);
	}
	
	public function UserGetByAPIKey($key)
	{
		$stmt = $this->mysqli->prepare("SELECT id FROM ".$this->prefix."users WHERE activation_token = ?");
		$stmt->bind_param("s", $key);
		$stmt->execute();
		$stmt->bind_result($user);
		$stmt->fetch();
		$stmt->close();
		return ($user);
	}
	
	public function UserGetByName($name)
	{
		$name_wild = '%' . $name . '%';
		$stmt = $this->mysqli->prepare("SELECT id FROM ".$this->prefix."users WHERE user_name LIKE ?");
		$stmt->bind_param("s", $name_wild);
		$stmt->execute();
		$stmt->bind_result($user);
		$users = array();
		while($stmt->fetch()) {
			$users[] = $user;
		}
		$stmt->close();
		return ($users);
	}	
	
	public function UserDisplayNameInUse($displayname)
	{
		$stmt = $this->mysqli->prepare("SELECT active FROM ".$this->prefix."users WHERE display_name = ? LIMIT 1");
		$stmt->bind_param("s", $displayname);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function UserEmailInUse($email)
	{
		$stmt = $this->mysqli->prepare("SELECT active FROM ".$this->prefix."users WHERE email = ? LIMIT 1");
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function UserNameLinkedToEmail($email, $username)
	{
		$stmt = $this->mysqli->prepare("SELECT active	FROM ".$this->prefix."users WHERE user_name = ? AND email = ? LIMIT 1");
		$stmt->bind_param("ss", $username, $email);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}	
	
	public function UserIdExists($user_id)
	{
		$stmt = $this->mysqli->prepare("SELECT active FROM ".$this->prefix."users WHERE id = ? LIMIT 1");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function UserUserNameExists($username)
	{
		$stmt = $this->mysqli->prepare("SELECT active FROM ".$this->prefix."users WHERE user_name = ? LIMIT 1");
		$stmt->bind_param("s", $username);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public static function UserBindDataToArray($id, $user, $display, $password, $email, $token, $activationRequest, $passwordRequest, $active, $title, $signUp, $signIn, $avatar)
	{
		return array(
			'id' => $id,
			'user_name' => $user,
			'display_name' => $display,
			'password' => $password,
			'email' => $email,
			'activation_token' => $token,
			'last_activation_request' => $activationRequest,
			'lost_password_request' => $passwordRequest,
			'active' => $active,
			'title' => $title,
			'sign_up_stamp' => $signUp,
			'last_sign_in_stamp' => $signIn,
			'avatar' => $avatar
		);
	}
	
	public function UsersFullData()
	{
		$stmt = $this->mysqli->prepare("SELECT id, user_name, display_name, password, email, activation_token, last_activation_request,
			lost_password_request, active, title, sign_up_stamp, last_sign_in_stamp, avatar FROM ".$this->prefix."users");
		$stmt->execute();
		$stmt->bind_result($id, $user, $display, $password, $email, $token, $activationRequest, $passwordRequest, $active, $title, $signUp, $signIn, $avatar);
		
		while ($stmt->fetch()) {
			$row[] = self::UserBindDataToArray($id, $user, $display, $password, $email, $token, $activationRequest, $passwordRequest, $active, $title, $signUp, $signIn, $avatar);
		}
		$stmt->close();
		return ($row);
	}
	
	public function UserFullData($id = NULL, $username = NULL, $token = NULL)
	{
		if($username!=NULL) {
			$column = "user_name";
			$data = $username;
		}
		elseif($token!=NULL) {
			$column = "activation_token";
			$data = $token;
		}
		elseif($id!=NULL) {
			$column = "id";
			$data = $id;
		}		
		$stmt = $this->mysqli->prepare("SELECT id, user_name, display_name, password, email, activation_token, last_activation_request,
			lost_password_request, active, title, sign_up_stamp, last_sign_in_stamp, avatar FROM ".$this->prefix."users WHERE $column = ? LIMIT 1");
		$stmt->bind_param("s", $data);
		$stmt->execute();
		$stmt->bind_result($id, $user, $display, $password, $email, $token, $activationRequest, $passwordRequest, $active, $title, $signUp, $signIn, $avatar);
		$stmt->fetch();
		$user = self::UserBindDataToArray($id, $user, $display, $password, $email, $token, $activationRequest, $passwordRequest, $active, $title, $signUp, $signIn, $avatar);
		$stmt->close();
		return ($user);
	}
	
	public function Users()
	{
		$stmt = $this->mysqli->prepare("SELECT id FROM ".$this->prefix."users");
		$stmt->execute();
		$stmt->bind_result($user);
		$users = array();
		while($stmt->fetch()) {
			$users[] = $user;
		}
		$stmt->close();
		return ($users);
	}
	
	public function UsersDelete($users) 
	{
		$i 		= 0;
		$stmt 	= $this->mysqli->prepare("DELETE FROM ".$this->prefix."users WHERE id = ?");
		$stmt2 	= $this->mysqli->prepare("DELETE FROM ".$this->prefix."user_permission_matches WHERE user_id = ?");
		foreach($users as $id)
		{
			$stmt->bind_param("i", $id);
			$stmt->execute();
			$stmt2->bind_param("i", $id);
			$stmt2->execute();
			$i++;
		}
		$stmt->close();
		$stmt2->close();
		return $i;
	}
	
	public function UserAdd(UCUser $user)
	{
		//Insert the user into the database providing no errors have been found.
		$stmt = $this->mysqli->prepare("INSERT INTO ".$this->prefix."users (user_name, display_name, password, email, activation_token, last_activation_request, lost_password_request,
					active,	title, sign_up_stamp, last_sign_in_stamp, avatar) VALUES (?,?,?,?,?,'".time()."','0',?,'New Member','".time()."','0','')");
		
		$name 		= $user->Name();
		$display 	= $user->DisplayName();
		$pass 		= $user->Password();
		$email		= $user->Email();
		$token 		= $user->Activationtoken();
		$active 	= $user->Active();
		
		$stmt->bind_param("sssssi", $name, $display, $pass, $email, $token, $active);
		$stmt->execute();
		$inserted_id = $this->mysqli->insert_id;
		$stmt->close();
		
		//Insert default permission into matches table
		$stmt = $this->mysqli->prepare("INSERT INTO ".$this->prefix."user_permission_matches  (user_id, permission_id) VALUES (?,'1')");
		$stmt->bind_param("s", $inserted_id);
		$stmt->execute();
		$stmt->close();
		return True;
	}
	
	//===================================================
	// Permissions Table
	
	public function PermissionCreate($permission)
	{		
		$stmt = $this->mysqli->prepare("INSERT INTO ".$this->prefix."permissions (name) VALUES (?)");
		$stmt->bind_param("s", $permission);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	public function PermissionsDelete($permissions, &$errors) 
	{
		$i = 0;
		$stmt = $this->mysqli->prepare("DELETE FROM ".$this->prefix."permissions WHERE id = ?");
		$stmt2 = $this->mysqli->prepare("DELETE FROM ".$this->prefix."user_permission_matches WHERE permission_id = ?");
		$stmt3 = $this->mysqli->prepare("DELETE FROM ".$this->prefix."permission_page_matches WHERE permission_id = ?");
		foreach($permissions as $perm)
		{
			if ($perm== 1) {
				$errors[] = lang("CANNOT_DELETE_NEWUSERS");
			}
			elseif ($perm== 2) {
				$errors[] = lang("CANNOT_DELETE_ADMIN");
			}
			else{
				$stmt->bind_param("i", $perm);
				$stmt->execute();
				$stmt2->bind_param("i", $perm);
				$stmt2->execute();
				$stmt3->bind_param("i", $perm);
				$stmt3->execute();
				$i++;
			}
		}
		$stmt->close();
		$stmt2->close();
		$stmt3->close();
		return $i;
	}
	
	public function PermissionsFullData()
	{
		$stmt = $this->mysqli->prepare("SELECT id, name FROM ".$this->prefix."permissions");
		$stmt->execute();
		$stmt->bind_result($id, $name);
		$row = array();
		while ($stmt->fetch()){
			$row[] = array('id' => $id, 'name' => $name);
		}
		$stmt->close();
		return ($row);
	}
	
	public function PermissionFullData($id = NULL, $name = NULL)
	{
		if($name!=NULL) {
			$column = "name";
			$data = $name;
		}
		elseif($id!=NULL) {
			$column = "id";
			$data = $id;
		}
		$stmt = $this->mysqli->prepare("SELECT id, name FROM ".$this->prefix."permissions WHERE $column = ? LIMIT 1");
		$stmt->bind_param("s", $data);
		$stmt->execute();
		$stmt->bind_result($id, $name);
		$stmt->fetch();
		$perm = array('id' => $id, 'name' => $name);
		$stmt->close();
		return ($perm);
	}
	
	public function PermissionExists($id)
	{
		$stmt = $this->mysqli->prepare("SELECT id FROM ".$this->prefix."permissions WHERE id = ? LIMIT 1");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function PermissionNameExists($perm)
	{
		$stmt = $this->mysqli->prepare("SELECT id FROM ".$this->prefix."permissions WHERE name = ? LIMIT 1");
		$stmt->bind_param("s", $perm);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function PermissionUpdate($id, $name)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."permissions SET name = ? WHERE id = ? LIMIT 1");
		$stmt->bind_param("si", $name, $id);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	//===================================================
	// User Permissions Table
	
	public function UserAddPermission($user_id, $permission) 
	{
		$i = 0;
		$stmt = $this->mysqli->prepare("INSERT INTO ".$this->prefix."user_permission_matches (permission_id, user_id) VALUES (?,?)");
		if (is_array($permission))
		{
			foreach($permission as $id)
			{
				$stmt->bind_param("ii", $id, $user_id);
				$stmt->execute();
				$i++;
			}
		}
		elseif (is_array($user_id))
		{
			foreach($user_id as $id)
			{
				$stmt->bind_param("ii", $permission, $id);
				$stmt->execute();
				$i++;
			}
		}
		else 
		{
			$stmt->bind_param("ii", $permission, $user_id);
			$stmt->execute();
			$i++;
		}
		$stmt->close();
		return $i;
	}
	
	public function UserRemovePermission($user_id, $permission)
	{
		$i = 0;
		$stmt = $this->mysqli->prepare("DELETE FROM ".$this->prefix."user_permission_matches WHERE permission_id = ? AND user_id =?");
		if (is_array($permission))
		{
			foreach($permission as $id)
			{
				$stmt->bind_param("ii", $id, $user_id);
				$stmt->execute();
				$i++;
			}
		}
		elseif (is_array($user_id))
		{
			foreach($user_id as $id)
			{
				$stmt->bind_param("ii", $permission, $id);
				$stmt->execute();
				$i++;
			}
		}
		else 
		{
			$stmt->bind_param("ii", $permission, $user_id);
			$stmt->execute();
			$i++;
		}
		$stmt->close();
		return $i;
	}
	
	public function UserPermissions($user_id)
	{
		$stmt = $this->mysqli->prepare("SELECT id, permission_id FROM ".$this->prefix."user_permission_matches WHERE user_id = ?");
		$stmt->bind_param("i", $user_id);
		$stmt->execute();
		$stmt->bind_result($id, $permission);
		$row = array();
		while ($stmt->fetch()){
			$row[$permission] = array('id' => $id, 'permission_id' => $permission);
		}
		$stmt->close();
		return ($row);
	}
	
	public function UsersGetByPermission($permission_id)
	{
		$stmt = $this->mysqli->prepare("SELECT id, user_id FROM ".$this->prefix."user_permission_matches WHERE permission_id = ?");
		$stmt->bind_param("i", $permission_id);
		$stmt->execute();
		$stmt->bind_result($id, $user);
		$row = array();
		while ($stmt->fetch()){
			$row[$user] = array('id' => $id, 'user_id' => $user);
		}
		$stmt->close();
		return ($row);
	}
	
	//===================================================
	// Pages Table
	
	public function PageCreate($pages) 
	{
		$stmt = $this->mysqli->prepare("INSERT INTO ".$this->prefix."pages (page) VALUES (?)");
		foreach($pages as $page)
		{
			$stmt->bind_param("s", $page);
			$stmt->execute();
		}
		$stmt->close();
	}
	
	public function PageDelete($pages) 
	{
		$stmt = $this->mysqli->prepare("DELETE FROM ".$this->prefix."pages WHERE id = ?");
		$stmt2 = $this->mysqli->prepare("DELETE FROM ".$this->prefix."permission_page_matches WHERE page_id = ?");
		foreach($pages as $id)
		{
			$stmt->bind_param("i", $id);
			$stmt->execute();
			$stmt2->bind_param("i", $id);
			$stmt2->execute();
		}
		$stmt->close();
		$stmt2->close();
	}
	
	public function PagesFullData()
	{
		$stmt = $this->mysqli->prepare("SELECT id, page, private FROM ".$this->prefix."pages");
		$stmt->execute();
		$stmt->bind_result($id, $page, $private);
		$row = array();
		while ($stmt->fetch()){
			$row[] = array('id' => $id, 'page' => $page, 'private' => $private);
		}
		$stmt->close();
		return ($row);
	}
	
	public function PageFullData($id = NULL, $page = NULL)
	{
		if($page!=NULL) {
			$column = "page";
			$data = $page;
		}
		elseif($id!=NULL) {
			$column = "id";
			$data = $id;
		}
		$stmt = $this->mysqli->prepare("SELECT id, page, private FROM ".$this->prefix."pages WHERE $column = ? LIMIT 1");
		$stmt->bind_param("s", $data);
		$stmt->execute();
		$stmt->bind_result($id, $page, $private);
		$stmt->fetch();
		$page = array('id' => $id, 'page' => $page, 'private' => $private);
		$stmt->close();
		return ($page);
	}
	
	public function PageExists($id)
	{
		$stmt = $this->mysqli->prepare("SELECT private FROM ".$this->prefix."pages WHERE id = ? LIMIT 1");
		$stmt->bind_param("i", $id);
		$stmt->execute();
		$stmt->store_result();
		$num_returns = $stmt->num_rows;
		$stmt->close();
		
		if ($num_returns > 0) {
			return true;
		}
		else {
			return false;
		}
	}
	
	public function PageUpdatePrivate($id, $private)
	{
		$stmt = $this->mysqli->prepare("UPDATE ".$this->prefix."pages SET private = ? WHERE id = ?");
		$stmt->bind_param("ii", $private, $id);
		$result = $stmt->execute();
		$stmt->close();
		return $result;
	}
	
	//===================================================
	// Page permissions Table
	
	public function PageAddPermission($page_id, $permission) 
	{
		$i = 0;
		$stmt = $this->mysqli->prepare("INSERT INTO ".$this->prefix."permission_page_matches (permission_id,page_id) VALUES (?,?)");
		if (is_array($permission))
		{
			foreach($permission as $id)
			{
				$stmt->bind_param("ii", $id, $page_id);
				$stmt->execute();
				$i++;
			}
		}
		elseif (is_array($page_id))
		{
			foreach($page_id as $id)
			{
				$stmt->bind_param("ii", $permission, $id);
				$stmt->execute();
				$i++;
			}
		}
		else 
		{
			$stmt->bind_param("ii", $permission, $page_id);
			$stmt->execute();
			$i++;
		}
		$stmt->close();
		return $i;
	}
	
	public function PagePermissions($page_id)
	{
		$stmt = $this->mysqli->prepare("SELECT id, permission_id FROM ".$this->prefix."permission_page_matches WHERE page_id = ?");
		$stmt->bind_param("i", $page_id);
		$stmt->execute();
		$stmt->bind_result($id, $permission);
		$row = array();
		while ($stmt->fetch()){
			$row[$permission] = array('id' => $id, 'permission_id' => $permission);
		}
		$stmt->close();
		return ($row);
	}
	
	public function PagesGetByPermission($permission_id)
	{
		$stmt = $this->mysqli->prepare("SELECT id, page_id FROM ".$this->prefix."permission_page_matches WHERE permission_id = ?");
		$stmt->bind_param("i", $permission_id);
		$stmt->execute();
		$stmt->bind_result($id, $page);
		$row = array();
		while ($stmt->fetch()){
			$row[$page] = array('id' => $id, 'page_id' => $page);
		}
		$stmt->close();
		return ($row);
	}
	
	public function PageRemovePermission($page_id, $permission) 
	{
		$i = 0;
		$stmt = $this->mysqli->prepare("DELETE FROM ".$this->prefix."permission_page_matches WHERE page_id = ? AND permission_id =?");
		if (is_array($page_id))
		{
			foreach($page_id as $id)
			{
				$stmt->bind_param("ii", $id, $permission);
				$stmt->execute();
				$i++;
			}
		}
		elseif (is_array($permission))
		{
			foreach($permission as $id)
			{
				$stmt->bind_param("ii", $page_id, $id);
				$stmt->execute();
				$i++;
			}
		}
		else 
		{
			$stmt->bind_param("ii", $page_id, $permission);
			$stmt->execute();
			$i++;
		}
		$stmt->close();
		return $i;
	}
	
	public function Execute($query)
	{
		$stmt = $this->mysqli->prepare($query);
		if($stmt->execute()) {
			return True;
		}
		return False;
	}
	
	public function Create()
	{
		$db_issue = false;
		
		$permissions_sql = "
		CREATE TABLE IF NOT EXISTS `".$this->prefix."permissions` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(150) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;
		";
		
		$permissions_entry = "
		INSERT INTO `".$this->prefix."permissions` (`id`, `name`) VALUES
		(1, 'New Member'),
		(2, 'Administrator');
		";
		
		$users_sql = "
		CREATE TABLE IF NOT EXISTS `".$this->prefix."users` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`user_name` varchar(50) NOT NULL,
		`display_name` varchar(50) NOT NULL,
		`password` varchar(225) NOT NULL,
		`email` varchar(150) NOT NULL,
		`activation_token` varchar(225) NOT NULL,
		`last_activation_request` int(11) NOT NULL,
		`lost_password_request` tinyint(1) NOT NULL,
		`active` tinyint(1) NOT NULL,
		`title` varchar(150) NOT NULL,
		`sign_up_stamp` int(11) NOT NULL,
		`last_sign_in_stamp` int(11) NOT NULL,
		`avatar` text NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
		";
		
		$user_permission_matches_sql = "
		CREATE TABLE IF NOT EXISTS `".$this->prefix."user_permission_matches` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`user_id` int(11) NOT NULL,
		`permission_id` int(11) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;
		";
		
		$user_permission_matches_entry = "
		INSERT INTO `".$this->prefix."user_permission_matches` (`id`, `user_id`, `permission_id`) VALUES
		(1, 1, 2);
		";
		
		$configuration_sql = "
		CREATE TABLE IF NOT EXISTS `".$this->prefix."configuration` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(150) NOT NULL,
		`value` varchar(150) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;
		";
		
		$configuration_entry = "
		INSERT INTO `".$this->prefix."configuration` (`id`, `name`, `value`) VALUES
		(1, 'website_name', 'UserCake'),
		(3, 'email', 'noreply@ILoveUserCake.com'),
		(4, 'activation', 'false'),
		(5, 'resend_activation_threshold', '0'),
		(6, 'language', 'models/languages/en.php'),
		(8, 'website_short_name', 'UCake'),
		(9, 'remember_me_length', '1wk');
		";
		
		$pages_sql = "CREATE TABLE IF NOT EXISTS `".$this->prefix."pages` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`page` varchar(150) NOT NULL,
		`private` tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=18 ;
		";
		
		$pages_entry = "INSERT INTO `".$this->prefix."pages` (`id`, `page`, `private`) VALUES
		(1, 'account.php', 1),
		(2, 'activate-account.php', 0),
		(3, 'admin_configuration.php', 1),
		(4, 'admin_page.php', 1),
		(5, 'admin_pages.php', 1),
		(6, 'admin_permission.php', 1),
		(7, 'admin_permissions.php', 1),
		(8, 'admin_user.php', 1),
		(9, 'admin_users.php', 1),
		(10, 'forgot-password.php', 0),
		(11, 'index.php', 0),
		(12, 'left-nav.php', 0),
		(13, 'login.php', 0),
		(14, 'logout.php', 1),
		(15, 'register.php', 0),
		(16, 'resend-activation.php', 0),
		(17, 'user_settings.php', 1);
		";
		
		$permission_page_matches_sql = "CREATE TABLE IF NOT EXISTS `".$this->prefix."permission_page_matches` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`permission_id` int(11) NOT NULL,
		`page_id` int(11) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=23 ;
		";
		
		$permission_page_matches_entry = "INSERT INTO `".$this->prefix."permission_page_matches` (`id`, `permission_id`, `page_id`) VALUES
		(1, 1, 1),
		(2, 1, 14),
		(3, 1, 17),
		(4, 2, 1),
		(5, 2, 3),
		(6, 2, 4),
		(7, 2, 5),
		(8, 2, 6),
		(9, 2, 7),
		(10, 2, 8),
		(11, 2, 9),
		(12, 2, 14),
		(13, 2, 17);
		";
		
		$sessions_sql = "
		CREATE TABLE IF NOT EXISTS `".$this->prefix."sessions` (
		`sessionStart` int(11) NOT NULL,
		`sessionData` text NOT NULL,
		`sessionID` varchar(255) NOT NULL,
		PRIMARY KEY (`sessionID`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		";
		
		$stmt = $this->mysqli->prepare($configuration_sql);
		if($stmt->execute())
		{
			$cfg_result = "<p>".$this->prefix."configuration table created.....</p>";
		}
		else
		{
			$cfg_result = "<p>Error constructing ".$this->prefix."configuration table.</p>";
			$db_issue = true;
		}
		
		echo $cfg_result;
		$stmt = $this->mysqli->prepare($configuration_entry);
		if($stmt->execute())
		{
			echo "<p>Inserted basic config settings into ".$this->prefix."configuration table.....</p>";
		}
		else
		{
			echo "<p>Error inserting config settings access.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($permissions_sql);
		if($stmt->execute())
		{
			echo "<p>".$this->prefix."permissions table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing ".$this->prefix."permissions table.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($permissions_entry);
		if($stmt->execute())
		{
			echo "<p>Inserted 'New Member' and 'Admin' groups into ".$this->prefix."permissions table.....</p>";
		}
		else
		{
			echo "<p>Error inserting permissions.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($user_permission_matches_sql);
		if($stmt->execute())
		{
			echo "<p>".$this->prefix."user_permission_matches table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing ".$this->prefix."user_permission_matches table.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($user_permission_matches_entry);
		if($stmt->execute())
		{
			echo "<p>Added 'Admin' entry for first user in ".$this->prefix."user_permission_matches table.....</p>";
		}
		else
		{
			echo "<p>Error inserting admin into ".$this->prefix."user_permission_matches.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($pages_sql);
		if($stmt->execute())
		{
			echo "<p>".$this->prefix."pages table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing ".$this->prefix."pages table.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($pages_entry);
		if($stmt->execute())
		{
			echo "<p>Added default pages to ".$this->prefix."pages table.....</p>";
		}
		else
		{
			echo "<p>Error inserting pages into ".$this->prefix."pages.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($permission_page_matches_sql);
		if($stmt->execute())
		{
			echo "<p>".$this->prefix."permission_page_matches table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing ".$this->prefix."permission_page_matches table.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($permission_page_matches_entry);
		if($stmt->execute())
		{
			echo "<p>Added default access to ".$this->prefix."permission_page_matches table.....</p>";
		}
		else
		{
			echo "<p>Error adding default access to ".$this->prefix."user_permission_matches.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($users_sql);
		if($stmt->execute())
		{
			echo "<p>".$this->prefix."users table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing users table.</p>";
			$db_issue = true;
		}
		
		$stmt = $this->mysqli->prepare($sessions_sql);
		if($stmt->execute())
		{
			echo "<p>".$this->prefix."sessions table created.....</p>";
		}
		else
		{
			echo "<p>Error constructing sessions table.</p>";
			$db_issue = true;
		}
		return $db_issue;
	}
}

?>