<?php

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=policy&action=view");
		exit;
		
	break;
	
	case "view":
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		
		$t->set_var('privacy_policy', $lang->mixed['privacy_policy']);
		
		$mod->parse();
		
	break;
}

?>