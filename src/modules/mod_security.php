<?php

switch ($mod->action) {
	
	default:
		
		if ($sec->runlevel < 2) {
			$mod->notify($lang->mixed['please_log_in']);
			$mod->parse();
			exit;
			
		} else {
			$mod->notify($lang->mixed['now_logged_in']);
			$mod->parse();
			exit;
		}
		
	break;
	
	case "auth":
		
		if ($_REQUEST['email'] && $_REQUEST['pass']) {
			// OK, so we have the username and password, now on to the auth...
			
			// Get the password's MD5 hash.
			$pass_enc = md5($_REQUEST['pass']);
			
			// Make sure no malicious code is used...
			$email = addslashes($_REQUEST['email']);
			
			$uid = $db->lookup(
				"SELECT id FROM users
				WHERE password = '$pass_enc'
				AND email = '$email'",
				"Check credentials."
			);
		}
		
		if (!$uid) {
			// Supplied credentials bad.
			$mod->notify($lang->mixed['bad_credentials']);
			$mod->parse();
			exit;
		}
		
		// Log the user in!
		$sec->set_session($uid);
		
		if ($_POST['target']) {
			setcookie('REQUEST_URL', $_POST['target']);
			
		} else {
			// Remove the error note trigger string from URL.
			$search = array("/\&+/", "/\?\&/");
			$replace = array("&", "?");
			setcookie('REQUEST_URL', preg_replace($search, $replace, $_SERVER['HTTP_REFERER']));
		}
		
		header("Location: ./?mod=security&action=test");
		exit;
		
	break;
	
	case "test":
		
		if ($_COOKIE['REQUEST_URL']) {
			header("Location: {$_COOKIE['REQUEST_URL']}");
			exit;
			
		} elseif ($sec->runlevel < 2) {
			$cfg['target'] = $_SERVER['HTTP_REFERER'];
			$mod->notify($lang->mixed['login_failed_help']);
			$mod->parse();
			
		} else {
			header("Location: {$_SERVER['HTTP_REFERER']}");
			exit;
		}
		
	break;
	
	case "logout":
		
		$sec->destroy_session();
		go_back();
		
	break;
	
	case "fpass":
		
		header("Location: ./?mod=fpass");
		exit;
		
	break;
}

?>