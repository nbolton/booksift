<?php

class HeaderSort {
	
	var $sql = array();
	var $html = array();
	var $raw = array();
	
	function HeaderSort($headers) {
		
		$this->raw['headers'] = $headers;
		$this->set_valids();
		$this->set_html();
		$this->set_sql();
	}
	
	function set_valids() {
		
		$this->sql['directions'] = array("DESC", "ASC");
		
		foreach ($this->raw['headers'] as $header) {
			if (is_array($header)) {
				$this->html['headers'][] = $header[0];
				$this->sql['headers'][] = $header[1];
				
			} else {
				$this->html['headers'][] = $header;
				$this->sql['headers'][] = $header;
			}
		}
		
		// Make sure variable is set.
		if (isset($_REQUEST['dir'])) {
			$this->raw['dir'] = strtoupper($_REQUEST['dir']);
		}
		
		// Make sure variable is set.
		if (isset($_REQUEST['sort'])) {
			$this->raw['sort'] = strtolower($_REQUEST['sort']);
			
			if (!in_array($this->raw['sort'], $this->html['headers'])) {
				$this->raw['sort'] = $this->html['headers'][0];
			}
		}
	}
	
	function set_html() {
		
		global $t, $lang;
		
		// Make sure variable is set.
		if (!isset($this->raw['dir'])) {
			$this->raw['dir'] = "";
		}
		
		// Make sure variable is set.
		if (!isset($this->raw['sort'])) {
			$this->raw['sort'] = "";
		}
		
		if ($this->raw['dir'] != $this->sql['directions'][0]) {
			$this->html['dir'] = strtolower($this->sql['directions'][1]);
			$this->html['inv_dir'] = strtolower($this->sql['directions'][0]);
		} else {
			$this->html['dir'] = strtolower($this->sql['directions'][0]);
			$this->html['inv_dir'] = strtolower($this->sql['directions'][1]);
		}
		
		// Remove old variables out of the URI.
		$REQUEST_URI = preg_replace("/&sort=.*&dir=.*/", "", $_SERVER['REQUEST_URI']);
		
		foreach ($this->html['headers'] as $header) {
			
			$url = $REQUEST_URI . "&sort={$header}&dir={$this->html['inv_dir']}";
			$header_html = "<a href='{$url}'>{$lang->mixed[$header]}</a>";
			
			if ($this->raw['sort'] == $header) {
				$image_html = "<img src='../images/icon_sort_{$this->html['dir']}.gif' align='absbottom' border='0'>";
				$header_html .= "&nbsp;<a href='{$url}'>$image_html</a>";
			}
			
			$t->set_var("header_{$header}", $header_html);
		}
	}
	
	function set_sql() {
		
		// As the raw input is from the webpage, check the html headers.
		foreach ($this->html['headers'] as $Key => $Header) {
			if ($this->raw['sort'] == $Header) {
				$Sort = $this->sql['headers'][$Key];
			}
		}
		
		// Make sure variable is set.
		if (!isset($Sort)) { $Sort = ""; }
		
		// Double check the value we found is ok.
		if (in_array($Sort, $this->sql['headers'])) {
			$this->sql['sort'] = $Sort;
		} else {
			$this->sql['sort'] = $this->sql['headers'][0];
		}
		
		// Directional flag is the same for HTML and SQL.
		if (in_array($this->raw['dir'], $this->sql['directions'])) {
			$this->sql['dir'] = $this->raw['dir'];
		} else {
			$this->sql['dir'] = $this->sql['directions'][0];
		}
	}
}
?>