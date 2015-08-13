<?php

// Define Debug if not defined
if (!defined('DEBUG')) define('DEBUG', 0);

// Write Log
function LogWrite($fileName = 'debug', $message) {
  // IF not in debug mode
  if (($fileName == 'debug' && DEBUG < 2) || ($fileName == 'error' && DEBUG < 1) || empty($fileName)) return;

  // Log File Name
  $fileName = $fileName . '_' . date('Y-m-d') . '.log';
  $message = date('Y-m-d H:i:s') . " " . $message;

  // If Object convert to string
  if (!is_string($message) && !is_numeric($message) && !is_bool($message)) $message = print_r($message, true);

  try
  {
    file_put_contents(LOG_DIR . DIRECTORY_SEPARATOR . $fileName, $message . "\n", FILE_APPEND | LOCK_EX);
  }
  catch (Exception $e)
  {
    echo "\n" . $e->__toString();
  }
}

function ReadQueue()
{
  $tasks = array();

  // check if defined
  if (!defined('QUEUE_DIR'))
  {
    LogWrite('error', 'QUEUE_DIR is not defined.');
    return $tasks;
  }

  // check if folder exists
  if (!file_exists(QUEUE_DIR))
  {
    LogWrite('error', 'QUEUE_DIR \'' . QUEUE_DIR . '\' is not exists.');
    return $tasks;
  }

  if ($handle = opendir(QUEUE_DIR))
  {
    while (false !== ($file = readdir($handle)))
    {
      if ($file == '.' || $file == '..' || $file == 'empty') continue;
      $file = QUEUE_DIR . DIRECTORY_SEPARATOR . $file;
      if (is_file($file))
      {
        try
        {
          if (is_file($file)) $tasks[$file] = json_decode(file_get_contents($file), true);
        }
        catch(Exception $e)
        {
          LogWrite('error', 'Json Parse Error at file \'' . $file . '\'.' . $e->getMessage());
        }
      }
    }
    closedir($handle);
  }
  else
  {
    LogWrite('error', "'" . QUEUE_DIR . "' can not open!");
  }

  return $tasks;
}

// Validate Command
function ValidateCommand($command, $args = array())
{
  // Load Command File
  UseCommand($command);

  // Check Function Exists
  if (!function_exists($command . '_validate')) return true;

  return call_user_func_array($command . '_validate', $args);
}

// Run Command
function RunCommand($command, $args = array())
{
  // Load Command File
  UseCommand($command);

  // Check Function Exists
  if (!function_exists($command . '_execute')) throw new Exception('Function \'' . $command . '_execute' . '\' not exists!');

  // Validate Arguments
  if (ValidateCommand($command, $args) !== true) throw new Exception('Command \'' . $command .'\' arguments \'{' . print_r($args, true) . '}\' not validated!');

  return call_user_func_array($command . '_execute', $args);
}

// Load Command
function UseCommand($command)
{
  // Memory Cache For Loaded Commands
  if (!isset($GLOBALS['loadedCommands'])) $GLOBALS['loadedCommands'] = array();
  $loadedCommands = $GLOBALS['loadedCommands'];

  // Check Command loaded before
  if (in_array($command, $loadedCommands)) return;

  // Check Command is exists
  if (!in_array($command, GetCommands())) throw new Exception('Command \'' . $command . '\' not found!', 404);

  // Get Command File
  $commandFile = BASE . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . $command . '.php';

  // Load Command File
  try
  {
    require_once $commandFile;
    $GLOBALS['loadedCommands'][] = $command;
  }
  catch (Exception $e)
  {
    throw $e;
  }
}

// Get Command List
function GetCommands()
{
  $commands = array();
  $commandDir = BASE . DIRECTORY_SEPARATOR . 'commands';
  if ($handle = opendir($commandDir))
  {
    while (false !== ($file = readdir($handle)))
    {
      if ($file == '.' || $file == '..' || $file == 'empty' || strpos($file, '.php') === false) continue;
      $commands[] = str_replace('.php', '', $file);
    }
    closedir($handle);
  }
  else
  {
    LogWrite('error', "'" . $commandDir . "' can not open!");
  }

  return $commands;
}

// Check Client
function CheckClient()
{
  if (FromLocal()) return true;
  if (!defined('ALLOW') || ALLOW == "*") return true;
  $clientIp = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
  return (strpos($clientIp, ALLOW) !== false);
}

// Check From Local
function FromLocal(){
  if (!isset($_SERVER['LOCAL_ADDR'])) return $_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'];
  if (!isset($_SERVER['SERVER_ADDR'])) return $_SERVER['LOCAL_ADDR'] == $_SERVER['REMOTE_ADDR'];
  return ($_SERVER['SERVER_ADDR'] == $_SERVER['REMOTE_ADDR'] || $_SERVER['LOCAL_ADDR'] == $_SERVER['REMOTE_ADDR']);
}

// Return Task hooks
function CallTaskHooks($task, $message)
{
  // Check Task Has Hook
  if (!isset($task['hooks']) || !is_array($task['hooks']) || empty($task['hooks'])) return true;

  // Set Default ContentType to text/plain
  $contentType = 'text/plain';

  // Set Default ContentType to json if message is array
  if (is_array($message))
  {
    $message = json_encode($message);
    $contentType = 'application/json';
  }

  foreach ($task['hooks'] as $hook_url)
  {
    // Validate Hook Url
    if (filter_var($hook_url, FILTER_VALIDATE_URL) === false)
    {
      LogWrite('error', "Task '" . $task['ticket'] ."' hook url '" . $hook_url . "' is not valid");
      continue;
    }

    // Setup cURL
    $ch = curl_init($hook_url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: ' . $contentType,
            'Ticket:' . $task['ticket']
        ),
        CURLOPT_POSTFIELDS => $message,
        CURLOPT_CONNECTTIMEOUT => 1,
        CURLOPT_TIMEOUT => 1
    ));

    // Get Response
    $response = curl_exec($ch);

    // Check Response
    if ($response === false)
    {
      $errorStr = curl_error($ch);
      LogWrite('error', "Task '" . $task['ticket'] ."' hook '" . $hook_url . "' occured curl error '" . $errorStr . "'");
    }
    else
    {
      $info = curl_getinfo($ch);
      $code = $info['http_code'];
      if ($code != 200)
      {
        LogWrite('error', "Task '" . $task['ticket'] ."' hook '" . $hook_url . "' got response " . $code . ", '" . trim(preg_replace('/\s*/', ' ', $response)) . "'");
      }
      LogWrite('debug', "Task '" . $task['ticket'] ."' hook '" . $hook_url . "' got response '" . trim(preg_replace('/\s\s+/', ' ', $response)) . "'");
    }

    // Close cURL
    curl_close($ch);
  }

}

?>
