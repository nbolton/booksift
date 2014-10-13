<?php

class Panels {
	
	function Panels() {
		
		global $t, $sec, $mod, $db, $lang;
		
		if (!$sec->sesh_key) {
			
			$t->set_file('UserPanel', 'login_panel.ihtml');
			$t->set_block('UserPanel', 'MasterTemplate');
			$t->set_var('login_status', $lang->mixed['not_logged_in']);
			
		} else {
			
			$t->set_file('UserPanel', 'user_panel.ihtml');
			$t->set_block('UserPanel', 'MasterTemplate');
			$t->set_var('login_status', $lang->replace('welcome_back', TRUE, 'username', $sec->username));
			
			$info_vars = array(
				'logout_link'	=>	"?mod=security&action=logout",
				'username'		=>	"<b>$sec->username</b>"
			);
			
			if (date("A", time()) == "AM") {
				$info_vars['greeting'] = $lang->replace('good_morning', TRUE, 'username', $sec->username);
			} else {
				$info_vars['greeting'] = $lang->replace('good_afternoon', TRUE, 'username', $sec->username);
			}
			
			$info_vars['greeting'] = "<span class='bodyTitle'>{$info_vars['greeting']}</span>";
			
			$t->set_var('login_info', $lang->replace('login_info', TRUE, $info_vars));
		}
		
		$t->set_file('MenuPanel', 'menu_panel.ihtml');
		$t->set_block('MenuPanel', 'LinkRow', 'LinkBlock');
		
		$links = $db->query(
			"SELECT `name`, `url`, `runlevs`, `external`
			FROM `links` ORDER BY `order` ASC",
			"Get links from database."
		);
		
		if ($db->get_num_rows($links)) {
			while ($link = $db->fetch_row($links)) {
				if (($link['runlevs'] == "*") || in_array($sec->runlevel, explode(",", $link['runlevs']))) {
					if ($size = @getimagesize("../images/table_menu_{$link['name']}.gif")) {
						$t->set_var('target', (($link['external'] == 'Yes') ? '_blank' : '_parent'));
						$t->set_var('name', $link['name']);
						$t->set_var('url', $link['url']);
						$t->set_var('width', $size[0]);
						$t->set_var('height', $size[1]);
						$t->parse('LinkBlock', 'LinkRow', TRUE);
					}
				}
			}
		}
		
		$t->set_file('Searching', 'searching.ihtml');
		$t->set_block('Searching', 'Searching');
		
		$t->set_block('MenuPanel', 'MasterTemplate');
		
		// Avoid conflict width login panel.
		$t->set_var('target');
	}
}

?>