<?php

class Security {
	
	/* ==========================================
	| Author: Nick "r3n" Bolton
	| Handles all the security in the ordering
	| system, such as checking for valid users
	| and setting user sessions.
	+ ======================================== */
	
	var $uid								= 0;
	var $sesh_id						= 0;
	var $sesh_life					=	0;
	var $max_sessions				=	0;
	var $sesh_key						= "";
	var $cookie_name				= "sesh";
	var $last_action				=	"";
	var $last_login					=	"";
	var $sesh_expire				=	0;
	var $runlevel					= 1;
	
	var $sesh_win_timeouts	= array(1 => 52254720000, 2 => 86400, 3 => 0);
	var $sesh_act_timeouts	= array(1 => 52254720000, 2 => 86400, 3 => 600);
	
	/*
	var $sesh_win_timeouts	= array(1 => 600, 2 => 300, 3 => 0);
	var $sesh_act_timeouts	= array(1 => 1800, 2 => 1200, 3 => 600);
	*/
	
	function Security() {
		
		/* ===========================================
		| Checks if user is logged in, set values used
		| later to either asociate the user to his
		| set CMS info, if not, ask them to create an
		| account.
		+ ========================================= */
		
		// If a session exists...
		if ($this->get_session()) {
			
			// Enter runlevel 2.
			$this->runlevel = 2;
			
			// Load uid, etc.
			$this->load_session();
			
			// Set last_update and new cookie.
			$this->update_session();
			
			// Run session garbage collection.
			$this->session_gc();
		}
		
		return TRUE;
	}
	
	function get_session() {
		
		/* ===========================================
		| Checks if user is logged in by looking to see
		| if they have a cookie AND an entry in the DB.
		+ ========================================= */
		
		global $_COOKIE, $db;
		
		// Check for a cookie, if they have a cookie with a valid session, which is also in the DB,
		// then return this value, if there is no value, then $this->sesh_key will remain empty.
		if (isset($_COOKIE[$this->cookie_name])) {
			return $db->lookup(
				"SELECT sesh_key FROM sessions
				WHERE sesh_key = '{$_COOKIE[$this->cookie_name]}'",
				"Find session in database."
			);
		} else {
			return false;
		}
	}
	
	function set_session($uid = "") {
		
		/* ===========================================
		| Makes a new session for the current user
		| by creating a cookie on their computer and
		| creating a record in the DB.
		+ ========================================= */
		
		global $_COOKIE, $REMOTE_ADDR, $db;
		
		// Destroy the current session.
		$this->destroy_session();
		
		$this->uid = $uid;
		
		// Generates a string of characters which will never occur more than once.
		$this->sesh_key = substr(md5(uniqid(microtime(),1)), 0, 30);
		
		// Set the cookie needed to store the session client-side.
		setcookie($this->cookie_name, $this->sesh_key, $this->get_sesh_life());
		
		// Create session in database.
		$db->query(
			"INSERT sessions SET
			uid = '$this->uid',
			ip_addr = '{$_SERVER['REMOTE_ADDR']}',
			sesh_key = '$this->sesh_key',
			date_create = NOW(),
			date_update = NOW()",
			"Create session in database."
		);
		
		return TRUE;
	}
	
	function load_session() {
		
		/* ===========================================
		| Loads the session data according to this
		| session's md5 hash.
		+ ========================================= */
		
		global $db;
		
		// Get this first as everything depends on it.
		$this->sesh_key = $this->get_session();
		$this->sesh_id = $db->lookup("SELECT id FROM sessions WHERE sesh_key = '$this->sesh_key'");
		$this->uid = $db->lookup("SELECT uid FROM sessions WHERE sesh_key = '$this->sesh_key'");
		
		// Get session data...
		$sesh_inf = $db->query(
			"SELECT date_update AS last_action, date_create AS last_login,
			UNIX_TIMESTAMP(date_update) as unix_last_action
			FROM sessions WHERE id = '$this->sesh_id'",
			"Get session data."
		);
		
		// Get user data...
		$user_inf = $db->query(
			"SELECT id AS uid, username,
			locked, email, profile_notify
			FROM users WHERE id = '$this->uid'",
			"Get user data."
		);
		
		// Merge the 2 arrays together...
		$sesh_inf = array_merge($db->fetch_row($user_inf), $db->fetch_row($sesh_inf));
		
		if ($db->get_num_rows()) {
			// For each item returned set the array values as objects.
			foreach($sesh_inf as $k => $v) {
				$this->$k = $v;
			}
		}
		
		// Last action must be at least this to be valid...
		$this->sesh_expire = time() - $this->sesh_act_timeouts[$this->get_security_level()];
		
		/*
			Now we have confirmed the user is still "valid",
			lets check that they aren't surposed to have timed out.
		*/
		if ($this->unix_last_action <= $this->sesh_expire) {
			// You have timed out...
			$this->destroy_session();
			
			// Must re-load page to make cookie effective!
			header("Location: ./");
			exit;
		}
		
		return TRUE;
	}
	
	function update_session() {
		
		/* ===========================================
		| Updates existing session by setting the
		| last_action and creating a new cookie so
		| the last one dosen't expire.
		+ ========================================= */
		
		global $db;
		
		// Set the cookie needed to store the session client-side.
		setcookie($this->cookie_name, $this->sesh_key, $this->get_sesh_life());
		
		// Set last action in sessions database.
		$db->query(
			"UPDATE sessions SET date_update = NOW() WHERE sesh_key = '$this->sesh_key'",
			"Update session in database."
		);
		
		return TRUE;
	}
	
	function reload_session() {
		
		/* ===========================================
		| Reload the session to apply new timeout.
		+ ========================================= */
		
		$this->destroy_session();
		$this->set_session($this->uid);
		return TRUE;
	}
	
	function destroy_session() {
		
		/* ===========================================
		| Destroys the current session in use.
		+ ========================================= */
		
		global $db;
		
		// Destroy the cookie.
		setcookie($this->cookie_name);
		
		// Check the sesh_key is set and delete the session from the DB.
		if ($this->sesh_key) {
			$db->query(
				"DELETE FROM sessions WHERE sesh_key = '$this->sesh_key'",
				"Destroy session data."
			);
		}
		
		return TRUE;
	}
	
	function destroy_user_sessions() {
		
		/* ===========================================
		| Destroys the current session in use.
		+ ========================================= */
		
		global $db;
		
		// Destroy the cookie.
		setcookie($this->cookie_name);
		
		// Check the sesh_key is set and delete the session from the DB.
		if ($this->sesh_key) {
			$db->query(
				"DELETE FROM sessions WHERE uid = '$this->uid'",
				"Destroy session data."
			);
		}
		
		return TRUE;
	}
	
	function session_gc() {
		
		/* ===========================================
		| Session garbage collection function; removes
		| all expired sessions from session table.
		+ ========================================= */
		
		global $db;
		
		/*
			Retrieve the oldest possible session life,
			and remove it from the current time.
		*/
		$max_timeout = $this->sesh_win_timeouts[$this->get_security_level()];
		$expiry_threshold = time() - $max_timeout;
		
		$db->query(
			"DELETE FROM sessions
			WHERE UNIX_TIMESTAMP(date_update) < '$expiry_threshold'
			AND uid = '$this->uid'",
			"Remove expired sessions."
		);
		
		return TRUE;
	}
	
	function get_security_level() {
		
		/* ===========================================
		| Lookup security level.
		+ ========================================= */
		
		global $db;
		return $db->lookup(
			"SELECT sec_level FROM users WHERE id = '$this->uid'",
			"Lookup security level."
		);
	}
	
	function get_sesh_life() {
		
		/* ===========================================
		| Returns the session life used for cookies.
		+ ========================================= */
		
		$this->sesh_life = $this->sesh_win_timeouts[$this->get_security_level()];
		
		if ($this->sesh_life) {
			return $this->sesh_life + time();
			
		} else { return FALSE; }
	}
	
	function nosesh_warn() {
		
		/* ===========================================
		| Tells the user they are not logged in.
		+ ========================================= */
		
		global $mod, $lang, $cfg;
		$cfg['target'] = $_SERVER['REQUEST_URI'];
		$mod->notify($lang->replace('nosesh_warn', TRUE, 'back', $_SERVER['HTTP_REFERER']));
		$mod->parse();
	}
}

?>