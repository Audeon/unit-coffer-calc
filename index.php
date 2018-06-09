<?php

$webroot = realpath($_SERVER["DOCUMENT_ROOT"]);

//set_include_path($webroot);
define("PRIVATE_PATH", $webroot . '/hidden');
define("PROJECT_PATH", $webroot);
define("PUBLIC_PATH", PROJECT_PATH . '/public');

require_once PRIVATE_PATH . '/classes/stpObj.php';
require_once PRIVATE_PATH . '/classes/stpfunctions.php';
require_once $webroot . '/../vendor/autoload.php';