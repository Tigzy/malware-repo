<?php

require_once(__DIR__.'/modules/module.php');

// This class stores an array of modules to be called for certain actions
class Modules
{
	protected $modules = array();
	
	public function __construct(array $mods_conf = array(), $callbacks = null)
	{
		// Create instances
		foreach($mods_conf as $mod_name => $mod_conf)
		{
			if (!isset($mod_conf["enabled"]) || !isset($mod_conf["class"]) || !$mod_conf["enabled"])
				continue;
			
			$mod_code = __DIR__.'/modules/' . $mod_name . '.php';
			include_once($mod_code);
			
			$builder 	= new ReflectionClass($mod_conf["class"]);
			$module 	= $builder->newInstanceArgs(array($mod_conf, $callbacks));			
			$this->Add($module);
		}
		
		// Sort by priority
		usort($this->modules, array("Modules", "ComparePriority"));
	}
	
	private static function ComparePriority(IModule $module1, IModule $module2)
	{
		$prio1 = isset($module1->GetConfig()["priority"]) ? $module1->GetConfig()["priority"] : 100;
		$prio2 = isset($module2->GetConfig()["priority"]) ? $module2->GetConfig()["priority"] : 100;
		
		if ($prio1 < $prio2) 
			return -1;
		else if ($prio1 > $prio2)  
			return 1;
		else 
			return 0;
	}
	
	// Register a new module
	public function Add( IModule $module )
	{
		$this->modules[] = $module;
	}
	
	// Notify all modules on a gieven event
	// Data is passed by reference, so it can be modified
	public function Notify( $event, &$data = null )
	{
		$has_candidate = False;
		foreach( $this->modules as $module )
		{
			if( is_callable(array($module, $event) ) ) {
				$has_candidate = True;
				$module->$event( $data );
			}
		}
		return $has_candidate;
	}
}