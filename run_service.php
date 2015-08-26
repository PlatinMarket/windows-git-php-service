<?php

// Set Execution Time Limit
set_time_limit(0);

// Bootstrap
require_once "/bootstrap.php";

// Get queue
$tasks = ReadQueue();

foreach ($tasks as $file => $task)
{
  try
  {
    $result = RunCommand($task['command'], $task['args']);
    LogWrite('debug', date('Y-m-d H:i:s') . "\r\n" . print_r(array('request' => $task, 'response' => $result), true));
    CallTaskHooks($task, $result);
  }
  catch(Exception $e)
  {
    LogWrite('error', 'Error occured when processing command \'' . $task['command'] . '\'' . "\r\n" . $e->getMessage());
    rename($file, str_replace(QUEUE_DIR, QUEUE_ERROR_DIR, $file));
    file_put_contents(str_replace(QUEUE_DIR, QUEUE_ERROR_DIR, $file), "\r\n\r\n" . $e->getCode() . " " . $e->getMessage() . "\r\n" . $e->getTraceAsString(), FILE_APPEND);
    continue;
  }
  unlink($file);
}

// Trigger Stdout Flush
fwrite(STDOUT, '***done***');

// Exit Sub Command
exit(0);
?>
