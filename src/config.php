<?php

/*
	Global configuration file.
*/

$cfg = array(
	'site_name'				=>	"BookSift.com", // Note: Used in "from" field for emails.
	'html_title'			=>	"BookSift.com",
	'default_module'	=>	"home",
	
	'admin_email'			=>	"admin@booksift.com",
	'support_email'		=>	"support@booksift.com",
	
	'templates_root'	=>	"../templates",
	'modules_root'		=>	"../src/modules",
	'language_root'		=>	"../lang",
	
	'amazon_token'		=>	"D254D3V09F4BQ",
	'amazon_assoc_id'	=>	"wwwbooksiftco-21",
	'amazon_wdsl'			=>	"http://soap-eu.amazon.com/schemas3/AmazonWebServices.wsdl",
	
	'user_currency'		=>	"£",
	
	'dev_server'			=>	"booksift.foxy.renhome.net",
	'db_conf'					=>	array(
													"user" => "booksift",
													"pass" => "jIns2bKsox7gVx",
													"database" => "booksift"
												),
												
	'http_proxy'			=>	"wwwcache.aber.ac.uk",
);

if (get_magic_quotes_gpc() == 0) {
	die("Security Error: Set magic_quotes_gpc to true!");
}

?>