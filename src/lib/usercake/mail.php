<?php

class UCMail 
{
	private $contents 	= NULL;
	private $valid		= False;
	
	public static function isAddressValid($email)
	{
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return true;
		}
		else {
			return false;
		}
	}
	
	private static function replaceDefaultHook($str)
	{
		global $user_settings;
		return (str_replace($user_settings->DefaultHooks(),$user_settings->DefaultReplace(),$str));
	}
	
	public function __construct($template, $additionalHooks)
	{
		global $user_settings;
		$this->contents = file_get_contents($user_settings->MailTemplatesDir().$template);
		
		//Check to see we can access the file / it has some contents
		if(!$this->contents || empty($this->contents)) {
			$this->valid = False;
		}
		else
		{
			//Replace default hooks
			$this->contents = self::replaceDefaultHook($this->contents);
			
			//Replace defined / custom hooks
			$this->contents = str_replace($additionalHooks[0],$additionalHooks[1],$this->contents);
			$this->valid = True;
		}
	}
	
	public function __destruct()
	{
		
	}	
	
	public function IsValid() {
		return $this->valid;
	}
	
	public function send($email, $subject, $msg = NULL)
	{
		global $user_settings;		
		$header = "MIME-Version: 1.0\r\n";
		$header .= "Content-type: text/plain; charset=iso-8859-1\r\n";
		$header .= "From: ". $user_settings->WebsiteName() . " <" . $user_settings->EmailAddress() . ">\r\n";
		
		//Check to see if we sending a template email.
		if($msg == NULL)
			$msg = $this->contents; 
		
		$message = $msg;		
		$message = wordwrap($message, 70);		
		return mail($email, $subject, $message, $header);
	}
}

?>