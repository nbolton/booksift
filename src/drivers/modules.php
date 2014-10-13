<?php

/*
	Module management.
*/

class Module {
	
	var $default_mod		=	"";
	var $mod_path				= "";
	var $mod						=	"";
	var $action					= "";
	var $mods_root			=	"";
	var $fname_prefix		=	"mod_";
	
	function Module($root, $default) {
		/*
			bool: module(string mod, string action);
			Sets variables and preps the module path validation.
		*/
		
		if (!is_dir($this->mods_root = $root)) {
			$this->halt("$root is not a valid path!");
		}
		
		// Set variables!
		$this->default_mod = $default;
		
		// Need's to be "default" if none for templates.
		if (!$this->action = @$_REQUEST['action']) {
			$this->action = "default";
		}
		
		// If no module, assign the default module.
		if (!$this->mod = $_REQUEST['mod']) {
			$this->mod = $this->default_mod;
		}
		
		return TRUE;
	}
	
  function halt($msg) {
		/*
			string: halt(string msg);
			Halts script to prevent crashing.
		*/
		
    die("<b>Module Error:</b> {$msg}");
  }
	
	function get_mod_path($mod = "") {
		/*
			string: get_module_path(string mod);
			Checks that module name is valid, in which
			case returns the path to that module.
		*/
		
		// If no mod specified, use existing.
		if (!$mod) { $mod = $this->mod; }
		
		// Check the path is valid, if so include it.
		if (file_exists($this->mods_root . "/{$this->fname_prefix}{$mod}.php")) {
			$this->mod_path = $this->mods_root . "/{$this->fname_prefix}{$mod}.php";
			
		} else {
			header("Location: ./?mod={$this->default_mod}");
			exit;
		}
		
		return $this->mod_path;
	}
	
	function parse() {
		
		global $t, $cfg, $lang;
		
		include "../src/drivers/panels.php";
		$panels = new Panels();
		
		// Load the master template.
		$t->set_file('MasterTemplate', 'site_master.ihtml');
		
		foreach ($lang->mixed as $k => $v) {
			$t->set_var("lang_{$k}", $v);
		}
		
		$cfg['mod'] = $this->mod;
		$t->set_var($cfg);
		
		$t->pparse('Output', 'MasterTemplate');
	}
	
	function notify($message) {
		/*
			void: notify(string message, array return);
			Sets the sub-template to a notify message for the user.
		*/
		
		global $t;
		$t->set_block('SubTemplate', 'SubTemplate');
		$t->set_var("SubTemplate", "<img src='../images/icon_books_small.gif' align='left' hspace='0'>{$message}");
	}
	
	function mparse() {
		$this->halt("mparse(): Function depreciated.");
	}
}

?>