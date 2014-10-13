<?php

if ($sec->runlevel > 1) {
	$mod->notify($lang->mixed['fpass_use_profile']);
	$mod->parse();
	exit;
}

function generate_trans_id($uid, $key) {
	global $db;
	
	$db->query(
		"SELECT `id`, `email`, `date_create` FROM
		`users` WHERE `id` = '$uid'",
		"Get user details for generating trans key."
	);
	
	if ($db->get_num_rows()) {
		$row = $db->fetch_row();
		return md5($row['id'] . $row['email'] . $row['date_create'] . $key);
	}
}

function challenge_trans_id($uid, $key, $trans_id) {
	global $db;
	
	$db->query(
		"SELECT `id`, `email`, `date_create` FROM
		`users` WHERE `id` = '$uid'",
		"Get user details for generating trans key."
	);
	
	if ($db->get_num_rows()) {
		$row = $db->fetch_row();
		
		if ($trans_id == md5($row['id'] . $row['email'] . $row['date_create'] . $key)) {
			return TRUE;
		}
	} else {
		return FALSE;
	}
}

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=fpass&action=email");
		exit;
		
	break;
	
	case "email":
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		$mod->parse();
		
	break;
	
	case "get_sq":
		
		$db->query(
			"SELECT `id`, `sec_ques` FROM `users`
			WHERE `email` = '{$_REQUEST['email']}'",
			"Lookup uid and secret question."
		);
		
		if ($db->get_num_rows()) {
			$row = $db->fetch_row();
			
			if ($row['sec_ques']) {
				$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
				$t->set_block('SubTemplate', 'SubTemplate');
				$t->set_var('secret_question', $row['sec_ques']);
				$t->set_var('uid', $row['id']);
				$mod->parse();
				exit;
				
			} else {
				// Trans ID for if SQ is skipped.
				$trans_id = generate_trans_id($row['id'], 'a9dh3cs1');
				header("Location: ./?mod=fpass&action=send&trans=$trans_id&uid={$row['id']}");
				exit;
			}
			
		} else {
			$mod->notify($lang->mixed['email_not_found']);
			$mod->parse();
			exit;
		}
		
	break;
	
	case "check_sq":
		
		$sa_match = $db->lookup(
			"SELECT COUNT(*) FROM `users` WHERE
			UPPER(TRIM(`sec_ans`)) = MD5(UPPER(TRIM('{$_POST['sec_ans']}')))
			AND id = '{$_POST['uid']}'",
			"Check that Secret Answer is correct."
		);
		
		if ($sa_match) {
			$trans_id = generate_trans_id($_POST['uid'], 'olc9v0xn');
			header("Location: ./?mod=fpass&action=send&trans=$trans_id&uid={$_POST['uid']}");
			
		} else {
			$mod->notify($lang->mixed['sec_ans_bad']);
			$mod->parse();
			exit;
		}
		
	break;
	
	case "send":
		
		function email_conf() {
			global $db, $cfg, $lang;
			
			$db->query(
				"SELECT `username`, `email` FROM `users` WHERE `id` = '{$_GET['uid']}'",
				"Retrieve user's email address from database."
			);
			
			$row = $db->fetch_row();
			
			$replace['conf_url'] = "http://{$_SERVER['HTTP_HOST']}/site/?mod=fpass&action=change";
			$replace['conf_url'] .= "&uid={$_GET['uid']}&trans=" . generate_trans_id($_GET['uid'], 'x92lc2sx');
			$replace['username'] = $row['username'];
			$replace['signature']	= $lang->mixed['email_signature'];
			
			sendmail(
				$row['email'], $lang->mixed['fpass_email_title'],
				$lang->replace('fpass_conf_email', TRUE, $replace)
			);
		}
		
		if (challenge_trans_id($_GET['uid'], 'a9dh3cs1', $_GET['trans'])) {
			email_conf();
			$mod->notify($lang->mixed['no_sec_ques'] . $lang->mixed['email_sent']);
			$mod->parse();
			exit;
			
		} elseif (challenge_trans_id($_GET['uid'], 'olc9v0xn', $_GET['trans'])) {
			email_conf();
			$mod->notify($lang->mixed['sec_ans_ok'] . $lang->mixed['email_sent']);
			$mod->parse();
			exit;
			
		} else {
			$mod->notify($lang->mixed['bad_trans_id']);
			$mod->parse();
			exit;
		}
		
	break;
	
	case "change":
		
		if (challenge_trans_id($_GET['uid'], 'x92lc2sx', $_GET['trans'])) {
			$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
			$t->set_block('SubTemplate', 'SubTemplate');
			$t->set_var('trans', generate_trans_id($_GET['uid'], 'p10y89x4'));
			$t->set_var('uid', $_GET['uid']);
			$mod->parse();
			exit;
			
		} else {
			$mod->notify($lang->mixed['bad_trans_id']);
			$mod->parse();
			exit;
		}
		
	break;
	
	case "finalize":
		
		if (challenge_trans_id($_POST['uid'], 'p10y89x4', $_POST['trans'])) {
			
			$db->query(
				"UPDATE `users` SET `password` = MD5('{$_POST['password']}'),
				`date_update` = NOW() WHERE `id` = '{$_POST['uid']}'",
				"Set user's new password."
			);
			
			// Log the user in.
			$sec->set_session($_POST['uid']);
			
			// Test that cookies work on user's browser.
			setcookie('REQUEST_URL', './?mod=security');
			header("Location: ./?mod=security&action=test");
			
		} else {
			$mod->notify($lang->mixed['bad_trans_id']);
			$mod->parse();
			exit;
		}
		
	break;
}

?>