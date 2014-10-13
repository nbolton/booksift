<?php

switch ($mod->action) {
	
	default:
		
		if ($sec->runlevel > 1) {
			header("Location: ./?mod=tsearch");
			exit;
			
		} else {
			header("Location: ./?mod=search&action=register");
			exit;
		}
		
	break;
	
	case "register":
		
		if ($sec->runlevel > 1) {
			header("Location: ./?mod=search&action=choose");
		}
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		$mod->parse();
		
	break;
}

?>