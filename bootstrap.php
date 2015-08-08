<?php

// Base Dir For Temp Use
$baseDir = dirname(__FILE__);

// Check config.php
if (!file_exists($baseDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php')) throw new Exception('config.php is missing!');
require_once $baseDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Set Debug Level
if (!defined('DEBUG')) define('DEBUG', 2);

// Set BaseDir
if (!defined('BASE')) define('BASE', dirname(__FILE__));

// Set ALLOW
if (!defined('ALLOW')) define('ALLOW', '*');

// Set QueueDir
if (!defined('QUEUE_DIR')) define('QUEUE_DIR', BASE . DIRECTORY_SEPARATOR . 'queue');

// Set QueueErrorDir
if (!defined('QUEUE_ERROR_DIR')) define('QUEUE_ERROR_DIR', BASE . DIRECTORY_SEPARATOR . 'queue_error');

// Set TmpDir
if (!defined('TEMP_DIR')) define('TEMP_DIR', BASE . DIRECTORY_SEPARATOR . 'tmp');

// Set LogDir
if (!defined('LOG_DIR')) define('LOG_DIR', TEMP_DIR . DIRECTORY_SEPARATOR . 'log');

// Create Directories
$allFolders = array(QUEUE_DIR, QUEUE_ERROR_DIR, TEMP_DIR, LOG_DIR);
foreach ($allFolders as $folder) if (!file_exists($folder)) mkdir($folder, 0777, true);

// Include lib
require_once $baseDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'lib.php';
require_once $baseDir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'proc.php';

?>
