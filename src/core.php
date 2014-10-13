<?php

// Load global configuration.
include "../src/config.php";

// Load global functions.
include "../src/libraries/functions.php";

// Open the DB connection with default values.
include "../src/drivers/mysqldb.php";
$db = new DBDriver($cfg['db_conf']);

// Load the templates management.
include "../src/drivers/templates.php";
$t = new Template("../templates");

// Start module management class & set vars.
include "../src/drivers/modules.php";
$mod = new Module($cfg['modules_root'], $cfg['default_module']);

// Load the language parser.
include "../src/drivers/language.php";
$lang = new Language("en-gb", $mod->mod, $cfg['language_root']);

// User login session management.
include "../src/drivers/security.php";
$sec = new Security();

// Proceed to load the module.
include $mod->get_mod_path();

// Tada!
exit;

?>