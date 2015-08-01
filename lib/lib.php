<?php

// Define Debug if not defined
if (!defined('DEBUG')) define('DEBUG', 0);

// Write Log
function LogWrite($fileName = 'debug', $message) {
  // IF not in debug mode
  if (($fileName == 'debug' && DEBUG < 2) || ($fileName == 'error' && DEBUG < 1) || empty($fileName)) return;

  // Log File Name
  $fileName = $fileName . '_' . date('Y-m-d') . '.log';

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

function RunCommand($command, $args = array())
{
  $commandFile = $command . ".php";
  $commandFile = 'commands' . '/' . $commandFile;

  // Check Command File
  if (!file_exists($commandFile)) throw new Exception('Command File \'' . $commandFile . '\' not exists!');

  // Load Command File
  require_once $commandFile;

  // Check Function Exists
  if (!function_exists($command)) throw new Exception('Function \'' . $command . '\' not exists!');

  return call_user_func_array($command, $args);
}

?>
