<?php

class Language {
	
	var $lang_dir			=	"";
	var $lang_type		=	"";
	var $module_name	= "";
	var $global_path	=	"";
	var $module_path	= "";
	var $global_lang	=	array();
	var $module_lang	=	array();
	var $mixed				=	array();
	var $revised			=	array();
	
	function Language($type, $mod, $root) {
		
		/*
			array load_global_lang ()
			Creates arrays of language vars & values.
		*/
		
		$this->module_name = $mod;
		$this->lang_type = $type;
		$this->lang_dir = $root . (strstr("/", $root) ? "" : "/");
		
		$this->global_lang = $this->load_global_lang();
		$this->module_lang = $this->load_module_lang();
		$this->mixed = array_merge($this->global_lang, $this->module_lang);
	}
	
	function load_global_lang() {
		
		/*
			array load_global_lang ()
			Loads global lang file to be used in all modules.
		*/
		
		$this->global_path = $this->lang_dir . $this->lang_type;
		$this->global_path .= "/lang_global.php";
		
		if (file_exists($this->global_path)) {
			include $this->global_path;
		}
		
		if ($lang) {
			return $lang;
		} else {
			return array();
		}
	}
	
	function load_module_lang() {
		
		/*
			array load_module_lang ()
			Loads the module specific language file.
		*/
		
		$this->module_path = $this->lang_dir . $this->lang_type;
		$this->module_path .= "/lang_" . $this->module_name . ".php";
		
		if (file_exists($this->module_path)) {
			include $this->module_path;
		}
		
		if (isset($lang)) {
			return $lang;
		} else {
			return array();
		}
	}
	
	function replace($var, $flush, $search = '', $replace = '') {
		
		/*
			string replace ( string var , bool flush [, string search [, string replace]])
			
			Replaces a segement of string within `var` wrapped by '%',
			and returns the new value of `var` within the `mixed` array.
			
			If the `flush` variable is set to TRUE, all remaining segments
			of the returned string will be removed.
		*/
		
		if (is_array($search)) {
			
			$this->revised[$var] = $this->mixed[$var];
			
			foreach ($search as $search => $replace) {
				$this->revised[$var] = str_replace("%{$search}%", $replace, $this->revised[$var]);
			}
			
		} else {
			$this->revised[$var] = str_replace("%{$search}%", $replace, $this->mixed[$var]);
		}
		
		if ($flush) {
			return preg_replace("/%.*%/i", '', $this->revised[$var]);
			
		} else {
			return $this->revised[$var];
		}
	}
}

?>
