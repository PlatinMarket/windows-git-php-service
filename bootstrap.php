<?php

// Check config.php
if (!file_exists('config/config.php')) throw new Exception('config.php is missing!');
require_once '/config/config.php';

// Include lib
require_once '/lib/lib.php';
require_once '/lib/proc.php';

?>
