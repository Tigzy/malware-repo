<?php

class UCUser 
{
	private $display_name			= NULL;
	private $title					= NULL;
	private $email 					= NULL;
	private $hash_pw 				= NULL;
	private $user_id 				= NULL;
	private $avatar 				= NULL;
	private $remember_me 			= NULL;
	private $remember_me_sessid 	= NULL;
	private $signup_timestamp		= NULL;
	private $activation_token		= NULL;
	private $user_name				= NULL;
	private $active					= NULL;
	private $lost_password_request  = NULL;
	private $last_sign_in_stamp     = NULL;
	private $last_activation_request = NULL;
	
	// New user only
	public $username_taken 			= False;
	public $displayname_taken		= False;
	public $email_taken				= False;
	public $has_errors				= False;
	public $registration_type		= NULL;
	
	private static $current_user;
	
	public static function getCurrentUser()
	{
		if (self::$current_user == NULL) {
			return NULL;
		}
		return self::$current_user;
	}
	
	public static function setCurrentUser(UCUser $user)
	{
		self::$current_user = $user;
		return self::$current_user;
	}
	
	public static function setCurrentUserById($user_id)
	{
		if ($user_id == NULL) {
			self::$current_user = NULL;
		}
		else {
			self::$current_user = new self($user_id);
		}		
		return self::$current_user;
	}
	
	public static function IsUserLoggedIn()
	{
		if (self::getCurrentUser() == NULL) {
			return False;
		}		
		return self::getCurrentUser()->IsLoggedIn();
	}
	
	public static function IsUserAdmin()
	{
		if (self::getCurrentUser() == NULL) {
			return False;
		}	
		return self::getCurrentUser()->IsAdmin();
	}
	
	public static function CanUserAccessUrl($uri)
	{
		// Separate document name from uri
		$tokens = explode('/', $uri);
		$page_name = $tokens[sizeof($tokens)-1];
		
		// Retrieve page
		$page = UCPage::GetByName($page_name);
		$user = self::getCurrentUser();
		
		//If page does not exist in DB, allow access
		if (!$page){
			return true;
		}
		//If page is public, allow access
		elseif (!$page->IsPrivate()) {
			return true;
		}
		//If user is not logged in, deny access
		elseif(!$user || !$user->IsLoggedIn())
		{
			header("Location: ".$GLOBALS["config"]["urls"]["baseUrl"]."login.php");
			return false;
		}
		else
		{
			//Retrieve list of permission levels with access to page
			$pagePermissions = $page->Permissions();
			$pagePermissionsArr = array();
			foreach($pagePermissions as $permission) {
				$pagePermissionsArr[] = $permission->Id();
			}
			
			//Check if user's permission levels allow access to page
			if ($user->ValidatePermission($pagePermissionsArr)){
				return true;
			}
			else {
				header("Location: ".$GLOBALS["config"]["urls"]["baseUrl"]."account.php");
				return false;
			}
		}
	}
	
	public static function ValidateAPIKey($key)
	{
		global $user_db;
		return $user_db->UserValidateAPIKey($key);
	}
	
	public static function GetByAPIKey($key)
	{
		global $user_db;
		return $user_db->UserGetByAPIKey($key);
	}
	
	public static function GetByUserName($name)
	{
		global $user_db;
		return $user_db->UserGetByName($name);
	}
	
	public static function GetByPermission($permission_id)
	{
		global $user_db;
		return $user_db->UsersGetByPermission($permission_id);
	}
	
	public static function GetUsersIds()
	{
		global $user_db;
		return $user_db->Users();
	}
	
	public static function SetActive($token)
	{
		global $user_db;
		return $user_db->UserSetActive($token);
	}
	
	public static function UpdateLastActivationRequest($new_activation_token,$username,$email)
	{
		global $user_db;
		return $user_db->UserUpdateLastActivationRequest($new_activation_token,$username,$email);
	}
	
	public function UpdatePasswordFromToken($pass,$token)
	{		
		global $user_db;
		return $user_db->UserUpdatePasswordFromToken($pass, $token);
	}
	
	public static function GetUsers()
	{
		global $user_db;
		$users = $user_db->UsersFullData();
		$users_arr = array();
		foreach ($users as $user) {
			$user_obj = new UCUser($user['id'], False);
			$user_obj->SetFullData($user);
			$users_arr[] = $user_obj;
		}
		return $users_arr;
	}
	
	public static function IdExists($user_id)
	{
		global $user_db;
		return $user_db->UserIdExists($user_id);
	}
	
	public static function UserNameExists($username)
	{
		global $user_db;
		return $user_db->UserUserNameExists($username);
	}
	
	public static function ValidateUserPermission($user_id, $permission)
	{
		global $user_settings;
		if ($user_id == $user_settings->MasterAccount()) {
			return True;
		}
		
		global $user_db;
		return $user_db->UserValidatePermission($user_id, $permission);		
	}
	
	public function AddPermission($permission)
	{		
		return UCPermission::AddUserPermission($this->user_id);
	}
	
	public function Permissions()
	{
		return UCPermission::GetUserPermissions($this->user_id);
	}
	
	public static function GetFromSession()
	{
		global $session;
		if($session->_isset("userCakeUser") && is_object($session->_get("userCakeUser")))
		{
			return $session->_get("userCakeUser");
		}
		else if(isset($_COOKIE["userCakeUser"]))
		{
			global $user_db, $user_settings;
			$data = $user_db->GetSessionDataFromCookie($_COOKIE['userCakeUser']);					
			if(empty($data)) {
				if(!empty($user_settings->RememberMeLength())) {
					setcookie("userCakeUser", "", -parseLength($user_settings->RememberMeLength()), "/");
				}
				return NULL;
			}
			else {
				return unserialize($data);
			}
		}
		else
		{
			global $user_db, $user_settings;
			if(!empty($user_settings->RememberMeLength())) {
				$data = $user_db->DeleteExpiredSessions(parseLength($user_settings->RememberMeLength()));	
			}
			return NULL;
		}
	}	
	
	private function DestroySession($name)
	{
		global $session;
		if(!isset($this->remember_me) || $this->remember_me == 0)
		{
			if($session->_isset($name))
			{
				$session->_set($name, NULL);
				$session->_unset($name);
				self::setCurrentUserById(NULL);
			}
		}
		else if($this->remember_me == 1)
		{
			if(isset($_COOKIE[$name]))
			{
				global $user_db, $user_settings;
				$data = $user_db->DeleteSession($this->RememberMeSessionId());
				setcookie($name, "", time() - parseLength($user_settings->RememberMeLength()), "/");
				self::setCurrentUserById(NULL);
			}
		}
	}
	
	//================================================
	
	public function __construct($id = NULL, $fetch_data = True)
	{
		$this->user_id = $id;
		if ($fetch_data && $id != NULL) {
			global $user_db;
			$data = $user_db->UserFullData($this->user_id);			
			$this->SetFullData($data);
		}
	}
	
	public function __destruct()
	{
		
	}
	
	public function SetFullData($data)
	{
		if (isset($data['display_name'])) $this->display_name       = $data['display_name'];
		if (isset($data['title'])) $this->title 					= $data['title'];
		if (isset($data['email'])) $this->email						= $data['email'];
		if (isset($data['password'])) $this->hash_pw 				= $data['password'];		
		if (isset($data['avatar'])) $this->avatar					= $data['avatar'];		
		if (isset($data['sign_up_stamp'])) $this->signup_timestamp  = $data['sign_up_stamp'];
		if (isset($data['activation_token'])) $this->activation_token = $data['activation_token'];		
		if (isset($data['user_name'])) $this->user_name 			= $data['user_name'];
		if (isset($data['active'])) $this->active					= $data['active'];	
		if (isset($data['lost_password_request'])) $this->lost_password_request = $data['lost_password_request'];	
		if (isset($data['last_sign_in_stamp'])) $this->last_sign_in_stamp = $data['last_sign_in_stamp'];		
		if (isset($data['last_activation_request'])) $this->last_activation_request = $data['last_activation_request'];			
	}
	
	public function UpdateLastSignIn()
	{
		global $user_db;		
		return $user_db->UserUpdateLastSignIn($this->user_id);
	}
	
	public function LastSignIn() {
		return $this->last_sign_in_stamp;
	}
	
	public function LastActivationRequest() {
		return $this->last_activation_request;
	}
	
	public function ToggleLostPasswordRequest($enable)
	{
		global $user_db;
		return $user_db->UserToggleLostPasswordRequest($this->user_id, $enable);
	}
	
	public function SignupTimeStamp($refresh = False)
	{
		if (!$refresh && !$this->signup_timestamp) {
			return $this->signup_timestamp;
		}
		
		global $user_db;
		$this->signup_timestamp= $user_db->UserSignupTimeStamp($this->user_id);
		return $this->signup_timestamp;
	}
	
	public function Password() {
		return $this->hash_pw;
	}
	
	public function Id() {
		return $this->user_id;
	}
	
	public function DisplayName() {
		return $this->display_name;
	}
	
	public function Email() {
		return $this->email;
	}
	
	public function Active() {
		return $this->active;
	}
	
	public function Title() {
		return $this->title;
	}
	
	public function UserName() {
		return $this->user_name;
	}
	
	public function LostPasswordRequest() {
		return $this->lost_password_request;
	}
	
	public function Activationtoken($refresh = False)
	{
		if (!$refresh && !empty($this->activation_token)) {
			return $this->activation_token;
		}
		
		global $user_db;
		$this->activation_token = $user_db->UserActivationtoken($this->user_id);
		return $this->activation_token;
	}	
	
	public function RememberMe() {
		return $this->remember_me;
	}
	
	public function RememberMeSessionId() {
		return $this->remember_me_sessid;
	}	
	
	public function UpdatePassword($new_pass)
	{
		global $user_db;
		$securepass	= generateHash($new_pass);
		if ($user_db->UserUpdatePassword($this->user_id, $securepass)) {		
			$this->hash_pw = $securepass;
			return True;
		}
		return False;
	}
	
	public function UpdateAvatar($avatar_base64)
	{
		global $user_db;
		if ($user_db->UserUpdateAvatar($this->user_id, $avatar_base64)) {
			$this->avatar = $avatar_base64;
			return True;
		}
		return False;
	}
	
	public function UpdateDisplayName($display)
	{
		global $user_db;
		if ($user_db->UserUpdateDisplayName($this->user_id, $display)) {
			$this->display_name = $display;
			return True;
		}
		return False;
	}
	
	public function UpdateEmail($email)
	{
		global $user_db;
		if ($user_db->UserUpdateEmail($this->user_id, $email)) {
			$this->email = $email;
			return True;
		}
		return False;
	}
	
	public function UpdateTitle($title)
	{
		global $user_db;
		if ($user_db->UserUpdateTitle($this->user_id, $title)) {
			$this->title = $title;
			return True;
		}
		return False;
	}
	
	public function UpdateRememberMe($remember_me) {
		$this->remember_me = $remember_me;
	}
	
	public function UpdateRememberMeSessionId($remember_me_session_id) {
		$this->remember_me_sessid = $remember_me_session_id;
	}
	
	public function Avatar($refresh = False)
	{
		if (!$refresh && !empty($this->avatar)) {
			return $this->avatar;
		}
		
		global $user_db;
		$this->avatar = $user_db->UserAvatar($this->user_id);
		return $this->avatar;
	}
	
	public function Name($refresh = False)
	{
		if (!$refresh && !empty($this->user_name)) {
			return $this->user_name;
		}
		
		global $user_db;
		$this->user_name= $user_db->UserName($this->user_id);
		return $this->user_name;
	}
	
	public function ValidatePermission($permission)
	{		
		return UCUser::ValidateUserPermission($this->user_id, $permission);
	}	
	
	public function LogOut()
	{
		$this->DestroySession("userCakeUser");
	}	
	
	public function IsLoggedIn()
	{		
		global $user_db;
		if(!$user_db->UserVerifyPassword($this->user_id, $this->hash_pw)) {
			$this->LogOut();
			return False;
		}
		return True;
	}
	
	public function IsAdmin()
	{
		if ($this->IsLoggedIn() && $this->ValidatePermission(array(2))) {
			return true;
		}
		return false;
	}	
	
	public static function NewUser($username, $display, $pass, $email)
	{
		global $user_db;
		$user = new UCUser();
		$user->display_name = $display;
		
		//Sanitize
		$user->email 		= sanitize($email);
		$user->hash_pw 		= generateHash(trim($pass));
		$user->user_name	= sanitize($username);		
		$user->activation_token = $user_db->GenerateActivationToken();
				
		if(!empty(self::GetByUserName($user->user_name))) {
			$user->username_taken = True;
			$user->has_errors = True;
		}
		else if($user_db->UserDisplayNameInUse($user->display_name)) {
			$user->displayname_taken = True;
			$user->has_errors = True;
		}
		else if($user_db->UserEmailInUse($user->email)) {
			$user->email_taken = True;
			$user->has_errors = True;
		}
		else {
			//No problems have been found.
			$user->has_errors = False;
		}
		return $user;
	}
	
	public function Add()
	{		
		if ($this->user_id!= NULL || $this->has_errors) {
			return False;
		}	
		
		global $user_settings;
		//Do we need to send out an activation email?
		if($user_settings->EmailActivation() == "true")
		{
			//User must activate their account first
			$this->active= 0;				
			
			$activation_message = lang("ACCOUNT_ACTIVATION_MESSAGE", array($user_settings->WebsiteUrl(), $this->activation_token));
			$hooks 				= array( array("#ACTIVATION-MESSAGE","#ACTIVATION-KEY","#USERNAME#"), array($activation_message, $this->activation_token, $this->display_name) );			
			$mail 				= new UCMail("new-registration.txt", $hooks);
						
			if (!$mail->IsValid()) {
				return False;
			}
			
			if (!$mail->send($this->email,"New User")) {
				return False;
			}				
			$this->registration_type = 2;
		}
		else
		{
			//Instant account activation
			$this->active= 1;
			$this->registration_type = 1;
		}		
		
		global $user_db;
		return $user_db->UserAdd($this);
	}
}

?>