<?php

// Bootstrap
require_once "/bootstrap.php";

$result = powershell('task_state', array('taskName' => 'git_php_service'));
echo $result['std_out'];
?>
