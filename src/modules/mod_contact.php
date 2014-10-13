<?php

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=contact&action=view");
		exit;
		
	break;
	
	case "view":
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		$mod->parse();
		
	break;
}

?>