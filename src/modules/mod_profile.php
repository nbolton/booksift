<?php

if ($sec->runlevel < 2) {
	$sec->nosesh_warn();
	exit;
}

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=profile&action=edit");
		exit;
		
	break;
	
	case "edit":
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		
		$db->query(
			"SELECT username, realname AS real_name,
			email AS email_address, password,
			password AS pass_conf,sec_ques AS sec_ques,
			sec_ans AS sec_ans, sec_ans AS sec_ans_conf,
			profile_notify, search_notify
			FROM users WHERE id = '$sec->uid'",
			"Fetch user's profile details."
		);
		
		if ($db->get_num_rows()) {
			$t->set_block('SubTemplate', 'InputRow', 'InputsBlock');
			
			$pass_fields = array(
				'password', 'pass_conf',
				'sec_ans', 'sec_ans_conf'
			);
			
			$enum_fields = array(
				'profile_notify', 'search_notify'
			);
			
			foreach ($db->fetch_row() as $field => $value) {
				
				$t->set_var('title', '{lang_' . $field . '}');
				$t->set_var('help', '{lang_' . $field . '_help}');
				$t->set_var('name', $field);
				
				if (in_array($field, $pass_fields)) {
					$t->set_file("FieldRow", "profile_edit_text.ihtml");
					$t->set_block("InputRow", "FieldRow", "FieldRow");
					$t->set_var('type', 'password');
					$t->set_var('value', 'dummy');
					$t->parse('FieldRow', 'FieldRow');
					
				} elseif (in_array($field, $enum_fields)) {
					$t->set_file("FieldRow", "profile_edit_radio.ihtml");
					$t->set_block("InputRow", "FieldRow", "FieldRow");
					
					if ($value == "Yes") {
						$t->set_var('yes_checked', 'checked');
						$t->set_var('no_checked');
					} else {
						$t->set_var('no_checked', 'checked');
						$t->set_var('yes_checked');
					}
					
					$t->parse('FieldRow', 'FieldRow');
					
				} else {
					$t->set_file("FieldRow", "profile_edit_text.ihtml");
					$t->set_block("InputRow", "FieldRow", "FieldRow");
					$t->set_var('type', 'text');
					$t->set_var('value', $value);
					$t->parse('FieldRow', 'FieldRow');
				}
				
				$t->parse('InputsBlock', 'InputRow', TRUE);
			}
		}
		
		$mod->parse();
		
	break;
	
	case "update":
		
		$query = "UPDATE `users` SET ";
		
		if (($_POST['password'] != 'dummy') && $_POST['password']) {
			$query .= "`password` = md5('{$_POST['password']}'), ";
			
			// All users must now be logged out.
			$sec->destroy_user_sessions();
			$sec->set_session($sec->uid);
		}
		
		if (($_POST['sec_ans'] != 'dummy') && $_POST['sec_ans']) {
			$query .= "`sec_ans` = MD5(UPPER(TRIM('{$_POST['sec_ans']}'))), ";
		}
		
		$query .= "`sec_ques` = '{$_POST['sec_ques']}', ";
		$query .= "`username` = '{$_POST['username']}', ";
		$query .= "`realname` = '{$_POST['real_name']}', ";
		$query .= "`email` = '{$_POST['email_address']}', ";
		$query .= "`profile_notify` = '{$_POST['profile_notify']}', ";
		$query .= "`search_notify` = '{$_POST['search_notify']}' ";
		$query .= "WHERE `id` = '$sec->uid' ";
		$query .= "AND `locked` = 'No'";
		
		// Avoid user from re-running query.
		if (isset($_COOKIE['last_query'])) {
			if ($_COOKIE['last_query'] != $query) {
				$ExecQuery = true;
			}
		} else {
			$ExecQuery = true;
		}
		
		if ($ExecQuery == true) {
			$db->query($query, "Update user's profile details.");
			setcookie('last_query', $query);
			
			// The username will need to be re-loaded.
			$sec->username = $_POST['username'];
			$new_email = $_POST['email_address'];
		}
		
		if ($db->get_affected_rows()) {
			// A change in the database has been detected.
			$mod->notify($lang->replace('update_ok', TRUE, 'back', $_SERVER['HTTP_REFERER']));
			
			if ($_POST['profile_notify'] == "Yes") {
				if ($new_email != $sec->email) {
					$replace['username'] = $sec->username;
					$replace['old_email'] = $sec->email;
					$replace['new_email'] = $new_email;
					$replace['signature']	= $lang->mixed['email_signature'];
					
					// Tell the user their password has been changed!
					sendmail(
						$sec->email, $lang->mixed['your_profile'],
						$lang->replace('changed_email', TRUE, $replace)
					);
					
				} else {
					$replace['username'] = $sec->username;
					$replace['signature']	= $lang->mixed['email_signature'];
					
					// Tell the user their account has been modified.
					sendmail(
						$sec->email, $lang->mixed['your_profile'],
						$lang->replace('changed_profile', TRUE, $replace)
					);
				}
			}
			
			// Make sure the new email is used.
			$sec->email = $new_email;
			
		} elseif ($sec->locked == "Yes") {
			// No change has been made, this is because the account is locked.
			$values['support_email'] = $cfg['support_email'];
			$values['back'] = $_SERVER['HTTP_REFERER'];
			$mod->notify($lang->replace('account_locked', TRUE, $values));
			
		} else {
			// No change has been maded because a duplicate query was run.
			$mod->notify($lang->replace('no_changes', TRUE, 'back', $_SERVER['HTTP_REFERER']));
		}
		
		$mod->parse();
		
	break;
}

?>