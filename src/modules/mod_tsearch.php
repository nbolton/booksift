<?php

if ($sec->runlevel < 2) {
	$sec->nosesh_warn();
	exit;
}

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=tsearch&action=browse");
		exit;
		
	break;
	
	case "browse":
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		
		$headers = array(
			array('running_since', 'date_create'), // Default
			array('earliest_date', 'early_date'), 'keywords',
			array('query_interval', 'interval')
		);
		
		include "../src/drivers/headers.php";
		$hs = new HeaderSort($headers);
		
		$t->parse('SubTemplate', 'SubTemplate');
		
		$db->query(
			"SELECT `id`, `keywords`, `interval`,
			DATE_FORMAT(`date_create`, '%e %M %Y %h:%i%p') AS running_since,
			DATE_FORMAT(`early_date`, '%e %M %Y') AS earliest_date
			FROM `queries` WHERE `uid_create` = '{$sec->uid}'
			ORDER BY `{$hs->sql['sort']}` {$hs->sql['dir']}",
			"Get user's search profiles."
		);
		
		if ($db->get_num_rows()) {
			$t->set_block('SubTemplate', 'QueryRow', 'QueriesBlock');
			
			while ($row = $db->fetch_row()) {
				$row['interval'] = $lang->mixed['IntervalsArray'][$row['interval']];
				$t->set_var($row);
				$t->parse('QueriesBlock', 'QueryRow', TRUE);
			}
		} else {
			$mod->notify($lang->mixed['browse_info']);
		}
		
		$mod->parse();
		
	break;
	
	case "details":
		
		$mod->notify("under_development");
		$mod->parse();
		exit;
		
	break;
	
	case "run":
		
		$keywords = $db->lookup(
			"SELECT `keywords` FROM `queries`
			WHERE `id` = '{$_REQUEST['id']}'
			AND `uid_create` = '{$sec->uid}'",
			"Lookup keywords."
		);
		
		header("Location: ?mod=isearch&action=results&keywords=$keywords");
		exit;
		
	break;
	
	case "new":
		
		$t->set_file('SubTemplate', "{$mod->mod}_edit.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		
		function Dropdown($DropdownKey, $Options, $DefaultValue = "") {
			// Generates a Dropdown box based on an array of options.
			global $mod, $t;
			
			if (!is_array($Options)) return FALSE;
			
			$t->set_file($DropdownKey, "{$mod->mod}_dropdown.ihtml");
			$t->set_block($DropdownKey, 'OptionRow', $DropdownKey . 'OptionsBlock');
			$t->set_var('DropdownKey', $DropdownKey);
			
			foreach ($Options as $OptionKey => $OptionValue) {
				$t->set_var('OptionKey', $OptionKey);
				$t->set_var('OptionValue', $OptionValue);
				$t->set_var('Selected', $OptionValue == $DefaultValue ? ' selected' : '');
				$t->parse($DropdownKey . 'OptionsBlock', 'OptionRow', true);
			}
			
			$t->parse($DropdownKey, $DropdownKey);
		}
		
		// Generate an array of 31 days.
		for ($i = 1; $i <= 31; $i++) $DropdownDayOptions[$i] = $i;
		
		// Generate an array starting from this year till 10 years time.
		for ($i = date('Y'); $i <= date('Y') + 10; $i++) $DropdownYearOptions[$i] = $i;
		
		// Set the dropdown boxes...
		Dropdown('Day', $DropdownDayOptions, date('d'));
		Dropdown('Month', $lang->mixed['MonthsArray'], date('F'));
		Dropdown('Year', $DropdownYearOptions);
		Dropdown('Interval', $lang->mixed['IntervalsArray']);
		
		$t->parse('SubTemplate', 'SubTemplate');
		$mod->parse();
		
	break;
	
	case "insert":
		
		$Data['early_date'] = "'{$_POST['Year']}-{$_POST['Month']}-{$_POST['Day']}'";
		$Data['interval'] = "'{$_POST['Interval']}'";
		$Data['keywords'] = "'{$_POST['Keywords']}'";
		$Data['uid_create'] = $sec->uid;
		$Data['uid_update'] = $sec->uid;
		$Data['date_create'] = 'NOW()';
		$Data['date_update'] = 'NOW()';
		
		$Data = $db->compile_db_insert_string($Data, FALSE);
		$Query = "INSERT INTO `queries` ({$Data['FIELD_NAMES']}) VALUES ({$Data['FIELD_VALUES']})";
		
		// Prevent user from re-running query.
		if ($_COOKIE['last_query'] != $Query) {
			$db->query($Query, "Insert tSearch query into database.");
			setcookie('last_query', $Query);
		}
		
		$mod->notify($lang->mixed['insert_done']);
		$mod->parse();
		
	break;
}

?>