<?php

require_once(__DIR__."/db.php"); //Require DB connection

class UCSettings
{
	// Stored in DB
	private $emailActivation;
	private $websiteName;
	private $websiteShortName;
	private $emailAddress;
	private $resend_activation_threshold;
	private $language;
	private $remember_me_length;
	
	// Dynamic
	private $emailDate;
	private $websiteUrl;
	private $mail_templates_dir;
	private $master_account;
	private $default_hooks;
	private $default_replace;
	
	public function __construct(UCDatabase $db) 
	{
		$settings 							= $db->Settings();
		
		$this->emailActivation 				= isset($settings['activation']) ? $settings['activation']['value'] : "";		
		$this->websiteName 					= isset($settings['activation']) ? $settings['website_name']['value'] : "";
		$this->websiteShortName 			= isset($settings['activation']) ? $settings['website_short_name']['value'] : "";		
		$this->emailAddress 				= isset($settings['activation']) ? $settings['email']['value'] : "";
		$this->resend_activation_threshold 	= isset($settings['activation']) ? $settings['resend_activation_threshold']['value'] : "";		
		$this->language 					= isset($settings['activation']) ? $settings['language']['value'] : "";
		$this->remember_me_length 			= isset($settings['activation']) ? $settings['remember_me_length']['value'] : "";
		
		$this->emailActivation_dbid 		= isset($settings['activation']) ? $settings['activation']['id'] : "";
		$this->websiteName_dbid				= isset($settings['activation']) ? $settings['website_name']['id'] : "";
		$this->websiteShortName_dbid		= isset($settings['activation']) ? $settings['website_short_name']['id'] : "";
		$this->emailAddress_dbid			= isset($settings['activation']) ? $settings['email']['id'] : "";
		$this->resend_activation_threshold_dbid = isset($settings['activation']) ? $settings['resend_activation_threshold']['id'] : "";
		$this->language_dbid				= isset($settings['activation']) ? $settings['language']['id'] : "";
		$this->remember_me_length_dbid		= isset($settings['activation']) ? $settings['remember_me_length']['id'] : "";
		
		$this->emailDate 					= date('dmy');
		$this->websiteUrl 					= $GLOBALS["config"]["urls"]["baseUrl"];
		$this->mail_templates_dir 			= __DIR__."/mail-templates/";
		$this->master_account 				= -1;
		
		$this->default_hooks 				= array("#WEBSITENAME#","#WEBSITEURL#","#DATE#");
		$this->default_replace 				= array($this->websiteName,$this->websiteUrl,$this->emailDate);
		
		if (!file_exists($this->language)) {
			$this->language = __DIR__."/languages/en.php";
		}
		
		if(!isset($this->language)) {
			$this->language = __DIR__."/languages/en.php";
		}		
	}
	
	public function __destruct() 
	{
		
	}
	
	public function LanguageFiles()
	{
		$directory = __DIR__."/languages";
		$languages = glob($directory . "/*.php");
		return $languages;
	}
	
	public function EmailActivation() 	{ return $this->emailActivation; }
	public function WebsiteName() 		{ return $this->websiteName; }
	public function WebsiteShortName() 	{ return $this->websiteShortName; }
	public function EmailAddress() 		{ return $this->emailAddress; }
	public function ResendActivationThreshold() 	{ return $this->resend_activation_threshold; }	
	public function Language() 			{ return $this->language; }
	public function RememberMeLength() 	{ return $this->remember_me_length; }
	
	public function EmailActivationId() 	{ return $this->emailActivation_dbid; }
	public function WebsiteNameId() 		{ return $this->websiteName_dbid; }
	public function WebsiteShortNameId() 	{ return $this->websiteShortName_dbid; }
	public function EmailAddressId() 		{ return $this->emailAddress_dbid; }
	public function ResendActivationThresholdId() 	{ return $this->resend_activation_threshold_dbid; }
	public function LanguageId() 			{ return $this->language_dbid; }
	public function RememberMeLengthId() 	{ return $this->remember_me_length_dbid; }
	
	public function MasterAccount() 	{ return $this->master_account; }	
	public function MailTemplatesDir() 	{ return $this->mail_templates_dir; }
	public function WebsiteUrl() 		{ return $this->websiteUrl; }	
	public function DefaultHooks() 		{ return $this->default_hooks; }
	public function DefaultReplace() 	{ return $this->default_replace; }
	
	public function SetEmailActivation($value) 		{ $this->emailActivation = $value; }
	public function SetWebsiteName($value) 			{ $this->websiteName = $value; }
	public function SetWebsiteShortName($value) 	{ $this->websiteShortName = $value; }
	public function SetEmailAddress($value) 		{ $this->emailAddress = $value; }
	public function SetResendActivationThreshold($value) 	{ $this->resend_activation_threshold = $value; }
	public function SetLanguage($value) 			{ $this->language = $value; }
	public function SetRememberMeLength($value) 	{ $this->remember_me_length = $value; }	
}

?>