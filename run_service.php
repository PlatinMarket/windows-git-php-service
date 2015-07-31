<?php

// Check config.php
if (!file_exists('config.php')) throw new Exception('config.php is missing!');
require_once 'config.php';

// Include lib
require_once 'lib.php';

// Get queue
$tasks = ReadQueue();


foreach ($tasks as $file => $task) {

  try
  {
    $result = RunCommand($task['command'], $task['args']);
    $result = array('request' => $task, 'response' => $result);
    LogWrite('debug', date('Y-m-d H:i:s') . "\r\n" . print_r($result, true));
    print_r($result);
  }
  catch(Exception $e)
  {
    LogWrite('error', 'Error occured when processing command \'' . $task['command'] . '\'' . "\r\n" . $e->getMessage());
    continue;
  }

}

?>
