<?php

// Set Default Encoding to Utf8
header("Content-Type: application/json; charset: utf-8");

// include Lib
try
{
  require_once '../bootstrap.php';
}
catch (Exception $e)
{
  $code = $e->getCode() ? $e->getCode() : 500;
  http_response_code($code);
  $body = array('success' => 'error', 'message' => $e->getMessage(), 'code' => $code);
  echo json_encode($body);
  exit();
}

// Check Client
if (!CheckClient())
{
  http_response_code(401);
  $body = array('success' => 'error', 'message' => 'Unauthorized', 'code' => 401);
  echo json_encode($body);
  exit();
}

// Get Rewrite Path Value
$path = isset($_GET['path']) ? $_GET['path'] : '';

// Get Rewrite Path Value
$body = array('success' => 'ok', 'message' => 'System Ready');

// Register New Ticket
if (preg_match_all('/^register\/?$/', $path)) {

  // Get & Parse Request Body
  $requestBody = @file_get_contents('php://input');
  $requestJson = @json_decode($requestBody, true);

  // Set Values
  $command = isset($requestJson['command']) ? $requestJson['command'] : null;
  $args = isset($requestJson['args']) ? $requestJson['args'] : array();
  $hooks = isset($requestJson['hooks']) ? $requestJson['hooks'] : array();

  // Check Values
  if (is_null($command) || !is_string($command) || !is_array($args) || !is_array($hooks))
  {
    http_response_code(400);
    $body = array('success' => 'error', 'message' => 'Bad Request', 'code' => 400);
  }
  else
  {
    // Create New Ticket
    try
    {
      $ticket = RegisterTicket($command, $args, $hooks);
      $body = array('success' => 'ok', 'ticket' => $ticket);
    }
    catch (Exception $e)
    {
      $code = $e->getCode() ? $e->getCode() : 500;
      http_response_code($code);
      $body = array('success' => 'error', 'message' => $e->getMessage(), 'code' => $code);
    }
  }
} // END: Register New Ticket


// Register Ticket Backend Function
function RegisterTicket($command, $args = array(), $hooks = array()){

  // Check Command is exists
  if (!in_array($command, GetCommands())) throw new Exception('Command \'' . $command . '\' not found!', 404);

  // Validate Arguments
  if (!ValidateCommand($command, $args)) throw new Exception('Command \'' . $command . '\' arguments not validated', 400);

  // Generate New Ticket Body
  $ticket = array(
    'ticket' => getGUID(),
    'command' => $command,
    'args' => $args,
    'hooks' => $hooks
  );

  $ticketFile = QUEUE_DIR . DIRECTORY_SEPARATOR . $ticket['ticket'] . '.json';

  if (file_put_contents($ticketFile, json_encode($ticket, JSON_PRETTY_PRINT)) == false)
  {
    throw new Exception('File \'' . $ticketFile . '\' write to queue failed!', 500);
  }

  // Register Task Run Before Exit Script
  register_shutdown_function('runTask');

  return $ticket['ticket'];
}

// Run Schulde Task For Run Tasks
function runTask()
{
  $null = powershell('start_task', array('taskName' => 'git_php_service'));
}

// Generate GUID
function getGUID(){
    if (function_exists('com_create_guid'))
    {
        return substr(com_create_guid(), 1, 36);
    }
    else
    {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }
}

// Add Host to Response
$body['host'] = gethostname();

// Pump Json To Response
echo json_encode($body);

?>
