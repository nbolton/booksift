<?

class DBDriver {
	
	var $obj = array(
		"sql_user"				=> "",
		"sql_pass"				=> "",
		"sql_database"		=> "",
		"sql_host"				=> "localhost",
		"sql_port"				=> "",
		"persistent"			=> "0",
		"cached_queries"	=> array(),
		'debug'						=> 0,
	);
	
	var $query_id				= "";
	var $connection_id	= "";
	var $query_count		= 0;
	var $record_row			= array();
	var $return_die			= 0;
	var $error					= "";
	
	function DBDriver($config) {
		
		if (is_array($config)) {
			$this->obj['sql_user'] = $config['user'];
			$this->obj['sql_pass'] = $config['pass'];
			$this->obj['sql_database'] = $config['database'];
		}
		
		// Connect to the DB.
		$this->connect();
	}
	
	/*========================================================================*/
	// Connect to the database                 
	/*========================================================================*/  
	
	function connect() {
		
		if ($this->obj['persistent']) {
			$this->connection_id = mysql_pconnect(
				$this->obj['sql_host'],
				$this->obj['sql_user'],
				$this->obj['sql_pass']
			);
		} else {
			$this->connection_id = @mysql_connect(
				$this->obj['sql_host'],
				$this->obj['sql_user'],
				$this->obj['sql_pass']
			) or die ($this->fatal_error('', "Connect to database."));
		}
		
		if (!mysql_select_db($this->obj['sql_database'], $this->connection_id)) {
			echo ("ERROR: Cannot find database ".$this->obj['sql_database']);
		}
	}
	
	
	/*========================================================================*/
	// Process a query
	/*========================================================================*/
	
	function query($the_query, $info = "Unknown Location!", $bypass = 0, $bypass_error = 0) {
		
		//--------------------------------------
		// Change the table prefix if needed
		//--------------------------------------
		
		$this->query_info = $info;
		
		if (!$bypass) {
			
			$this->query_id = mysql_query($the_query, $this->connection_id);
			
			if (!$this->query_id && !$bypass_error) {
				$this->fatal_error("<b>mySQL query:</b> $the_query");
			}
		}
		
		$this->query_count++;
		$this->obj['cached_queries'][] = $the_query;
		return $this->query_id;
	}
	
	
	/*========================================================================*/
	// Get a value from a row which matches mysql query
	/*========================================================================*/
	
	function lookup($query, $info = "Unknown location"){  // returns a matching decscription from an sql table
		$result = $this->query($query, "lookup: $info");
		$row = $this->fetch_row();
		
		if($row) {
			foreach($row as $k => $v) {
				return ($v);
				break;
			}
		}
	}
	
	
	/*========================================================================*/
	// Fetch a row based on the last query
	/*========================================================================*/
	
	function fetch_row($query_id = "", $get_ords = "") {
		
		if ($query_id == "") {
			$query_id = $this->query_id;
		}
		
		if (!$get_ords) {
			$this->record_row = mysql_fetch_assoc($query_id);
		} else {
			$this->record_row = mysql_fetch_row($query_id);
		}
		
		return $this->record_row;
	}
	
	/*========================================================================*/
	// Fetch the number of rows affected by the last query
	/*========================================================================*/
	
	function get_affected_rows() {
		return mysql_affected_rows($this->connection_id);
	}
	
	/*========================================================================*/
	// Fetch the number of rows in a result set
	/*========================================================================*/
	
	function get_num_rows($query_id = "") {
		
		if ($query_id == "") {
			$query_id = $this->query_id;
		}
		
		if($query_id) return mysql_num_rows($query_id);
	}
	
	/*========================================================================*/
	// Fetch the last insert id from an sql autoincrement
	/*========================================================================*/
	
	function get_insert_id() {
		return mysql_insert_id($this->connection_id);
	}
	
	/*========================================================================*/
	// Return the amount of queries used
	/*========================================================================*/
	
	function get_query_cnt() {
		return $this->query_count;
	}
	
	/*========================================================================*/
	// Free the result set from mySQLs memory
	/*========================================================================*/
	
	function free_result($query_id = "") {
		
		if ($query_id == "") {
			$query_id = $this->query_id;
		}
		
		@mysql_free_result($query_id);
	}
	
	/*========================================================================*/
	// Shut down the database
	/*========================================================================*/
	
	function close_db() { 
		return mysql_close($this->connection_id);
	}
	
	/*========================================================================*/
	// Return an array of tables
	/*========================================================================*/
	
	function get_table_names() {
		
		$result = mysql_list_tables($this->obj['sql_database']);
		$num_tables = @mysql_numrows($result);
		
		for ($i = 0; $i < $num_tables; $i++) {
			$tables[] = mysql_tablename($result, $i);
		}
		
		mysql_free_result($result);
		
		return $tables;
	}
	
	/*========================================================================*/
	// Return an array of fields
	/*========================================================================*/
	
	function get_result_fields($query_id = "") {
		
		if ($query_id == "") {
			$query_id = $this->query_id;
		}
		
		while ($field = mysql_fetch_field($query_id)) {
			$Fields[] = $field;
		}
		
		return $Fields;
	}
	
	/*========================================================================*/
	// Basic error handler
	/*========================================================================*/
	
	function fatal_error($the_error = "", $info = "") {
		global $cfg, $mm, $HTTP_HOST;
		
		// Are we simply returning the error?
		if ($this->return_die == 1) {
			$this->error = mysql_error();
			return TRUE;
		}
		
		if (isset($this->query_info)) {
			$info = $this->query_info;
		}
		
		// Removes retarded info from newer versions of mySQL.
		$mysql_error = str_replace(".  Check the manual that corresponds to your MySQL server version for the right syntax to use", "", mysql_error());
		
		// Build up the error info...
		$the_error = "<b>Error Returned...</b><br>\n" . $the_error;
		$the_error .= "\n<br><b>MySQL error:</b> $mysql_error\n";
		$the_error .= "<br><b>MySQL error code:</b> ".mysql_errno()."\n";
		$the_error .= "<br><b>Date:</b> ".date("l dS of F Y h:i:s A") . "\n";
		$the_error .= "<br><b>Info:</b> $info\n";
		
		// Make it look all pretty :)
		$style = 
			"body {
				font-family: Verdana, Arial, Helvetica, sans-serif;
				font-size: 11px;
				color: #000000;
				text-decoration: none
			}
			
			a {
				color: #0000FF;
				text-decoration: underline;
			}
			
			a:hover {
				text-decoration: none; 
				color: #0000FF
			}";
		
		$out = 
			"<html><head><title>Fatal MySQL Error</title>
			<style type=\"text/css\"><!--
			$style
			--></style>
			<p><b>There appears to be an error with our database.</b>
    	<br><br>We have been alerted of this error, and will attempt to fix it asap.
			<br>In the meantime, for further information click <a href='mailto:{$cfg['admin_email']}?subject=MySQL Error'>here</a> to email us.";
		
		if (!stristr($_SERVER['HTTP_HOST'], "dev") && ($_SERVER['HTTP_HOST'] != $cfg['dev_server'])) {
			
			sendmail(
				$cfg['admin_email'],
				"MySQL Error!",
				"<style type=\"text/css\"><!-- $style --></style>".
				"$the_error",
				"text/html"
			);
		} else {
			$out .= "<br><br>$the_error";
		}
		
		$out .= "
			<br><br>We apologise for any inconvenience.
			</body></html>";
		
		echo($out);
		die("");
	}
	
	/*========================================================================*/
	// Create an array from a multidimensional array returning formatted
	// strings ready to use in an INSERT query, saves having to manually format
	// the (INSERT INTO table) ('field', 'field', 'field') VALUES ('val', 'val')
	/*========================================================================*/
	
	function compile_db_insert_string($data, $AddQuotes = TRUE) {
		
		foreach ($data as $k => $v) {
			$field_names[] = "`$k`";
			
			if ($AddQuotes) {
				$field_values[] = "'$v'";
			} else {
				$field_values[] = $v;
			}
		}
		
		return array(
			'FIELD_NAMES'  => implode(", ", $field_names),
			'FIELD_VALUES' => implode(", ", $field_values),
		);
	}
	
	/*========================================================================*/
	// Create an array from a multidimensional array returning a formatted
	// string ready to use in an UPDATE query, saves having to manually format
	// the FIELD='val', FIELD='val', FIELD='val'
	/*========================================================================*/
	
	function compile_db_update_string($data) {
		
		$return_string = "";
		
		foreach ($data as $k => $v) {
			$v = preg_replace( "/'/", "\\'", $v );
			$return_string .= $k . "='".$v."',";
		}
		
		$return_string = preg_replace( "/,$/" , "" , $return_string );
		
		return $return_string;
	}
}