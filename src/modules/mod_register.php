<?php

if ($sec->runlevel > 1) {
	$reg_url = "<b>www.booksift.com/register</b>";
	$mod->notify($lang->replace('already_registered', TRUE, 'reg_url', $reg_url));
	$mod->parse();
	exit;
}

function get_reg_emails() {
	
	global $db, $mod;
	
	$db->query(
		"SELECT `id`, `email` FROM `users` WHERE `reg_state` = 'Yes'",
		"Get ID and Email for accounts in registration state."
	);
	
	if ($db->get_num_rows()) {
		// Build a list of email hashes in the reg state.
		while ($row = $db->fetch_row()) {
			$email_hash = md5($row['email']);
			$emails[$email_hash] = $row['id'];
		}
	}
	
	return $emails;
}

switch ($mod->action) {
	
	default:
		
		header("Location: ./?mod=register&action=start");
		exit;
		
	break;
	
	case "start":
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		$mod->parse();
		
	break;
	
	case "confirm":
		
		// Make sure the user hasn't added any extra space.
		$_POST['email'] = trim($_POST['email']);
		$_POST['username'] = trim($_POST['username']);
		
		// Did we get an email address?
		if (!$_POST['email']) {
			$mod->notify($lang->mixed['no_email_sent']);
			$mod->parse();
			exit;
		}
		
		// Did we get a username?
		if (!$_POST['username']) {
			$mod->notify($lang->mixed['no_user_sent']);
			$mod->parse();
			exit;
		}
		
		// Is the email address valid? i.e. does it have an '@' and has no spaces...
		if (preg_match("/\s/i", $_POST['email']) || !preg_match("/@|\./i", $_POST['email'])) {
			$mod->notify($lang->mixed['email_invalid']);
			$mod->parse();
			exit;
		}
		
		// First check if email address exists.
		$email_exists = $db->lookup(
			"SELECT COUNT(*) FROM `users`
			WHERE `email` = '{$_POST['email']}'",
			"Check for existing email address."
		);
		
		if ($email_exists) {
			// Suggest that the user tries another email address.
			$mod->notify($lang->replace('email_taken', TRUE, 'email', $_POST['email']));
			$mod->parse();
			exit;
		}
		
		$db->query(
			"INSERT `users` SET
			`username` = '{$_POST['username']}',
			`email` = '{$_POST['email']}',
			`date_create` = NOW(),
			`date_update` = NOW()",
			"Create temporary user account"
		);
		
		// These values will be part of the email.
		$replace['username'] = $_POST['username'];
		$replace['conf_link']	= "http://{$_SERVER['HTTP_HOST']}/site/?mod=register&action=finish&id=" . md5($_POST['email']);
		$replace['signature']	= $lang->mixed['email_signature'];
		
		// Send the confirmation email to the user.
		sendmail(
			$_POST['email'], $lang->mixed['registration'],
			$lang->replace('email_conf', TRUE, $replace)
		);
		
		// Tell the user everything has been done OK.
		$mod->notify($lang->replace('email_user_valid', TRUE, 'email', $_POST['email']));
		$mod->parse();
		
	break;
	
	case "finish":
		
		if (!$_GET['clean']) {
			// Remove annoying Hotmail advertising frames.
			$clean = "./?mod=register&action=finish&clean=1&id={$_GET['id']}";
			echo "<script>parent.location = '{$clean}'</script>";
		}
		
		$emails = get_reg_emails();
		
		if (!$emails[$_GET['id']]) {
			$mod->notify("invalid_reg_key");
			$mod->parse();
			exit;
		}
		
		$t->set_file('SubTemplate', "{$mod->mod}_{$mod->action}.ihtml");
		$t->set_block('SubTemplate', 'SubTemplate');
		
		$db->query(
			"SELECT `id`, `username`, `email`
			FROM `users` WHERE `id` = '{$emails[$_GET['id']]}'",
			"Select user's details with matching key."
		);
		
		$t->set_var('choose_password', $lang->replace('choose_password', TRUE, $db->fetch_row()));
		$t->set_var('reg_id', $_GET['id']);
		$mod->parse();
		
	break;
	
	case "finalize":
		
		$emails = get_reg_emails();
		
		if (!$emails[$_POST['reg_id']]) {
			$mod->notify("invalid_reg_key");
			$mod->parse();
			exit;
		}
		
		$db->query(
			"UPDATE `users` SET `password` = md5('{$_POST['password']}'),
			`reg_state` = 'No', `date_update` = NOW()
			WHERE `id` = '{$emails[$_POST['reg_id']]}'",
			"Set user's new password."
		);
		
		// Log the user in.
		$sec->set_session($emails[$_POST['reg_id']]);
		
		// Test that cookies work on user's browser.
		setcookie('REQUEST_URL', './?mod=profile&action=edit');
		header("Location: ./?mod=security&action=test");
		
	break;
}

?>