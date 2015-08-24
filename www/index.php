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

// Get Repository Info
if (preg_match_all('/^repository_info\/?$/', $path)) {

  // Get & Parse Request Body
  $requestBody = @file_get_contents('php://input');
  $requestJson = @json_decode($requestBody, true);

  // Set Values
  $path = isset($requestJson['remote_path']) ? $requestJson['remote_path'] : null;

  // Check Values
  if (is_null($path) || !is_string($path))
  {
    http_response_code(400);
    $body = array('success' => 'error', 'message' => 'Bad request', 'code' => 400);
  }
  elseif (!empty($path) && is_string($path) && !file_exists($path))
  {
    http_response_code(404);
    $body = array('success' => 'error', 'message' => 'Repository \'' . $path . '\' not found', 'code' => 404);
  }
  else
  {
    // Check Repository
    try
    {
      // Check if bare
      $is_bare = executeCommand("git rev-parse --is-bare-repository", $path);
      if ($is_bare['exit_code'] !== 0) throw new Exception("Not a git repository (or any of the parent directories)", 404);
      $is_bare = $is_bare['std_out'] == "true" ? true : false;
      // Get Head Hash
      $hash_code = executeCommand("git rev-parse HEAD", $path);
      if ($hash_code['exit_code'] !== 0) throw new Exception("Repository getting HEAD hash failed", 500);
      $hash_code = trim($hash_code['std_out']);
      // Get Last Head Timespan
      try
      {
        $headFile = $path . DIRECTORY_SEPARATOR . ".git" . DIRECTORY_SEPARATOR . "FETCH_HEAD";
        if (!file_exists($headFile))
          $head_timespan = 0;
        else
          $head_timespan = @filemtime($headFile);
      }
      catch (Exception $e)
      {
        $head_timespan = null;
      }
      // Check origin url
      $repository_url = executeCommand("git config --get remote.origin.url", $path);
      if ($repository_url['exit_code'] !== 0) throw new Exception("Repository has not any remote origin", 404);
      $repository_url = parseUrl($repository_url['std_out']);
      // Check Branch
      $branch = executeCommand("git branch", $path);
      if ($branch['exit_code'] !== 0) throw new Exception("Repository has not any branch", 404);
      $branch = parseBranches($branch['std_out']);
      $body = array('success' => 'ok', 'repository' => array_merge($repository_url, array('is_bare' => $is_bare, 'branch' => $branch, 'hash' => $hash_code, 'head_timespan' => $head_timespan)));
    }
    catch (Exception $e)
    {
      $code = $e->getCode() ? $e->getCode() : 500;
      http_response_code($code);
      $body = array('success' => 'error', 'message' => $e->getMessage(), 'code' => $code);
    }
  }
}

// Get SSH Key
if (preg_match_all('/^public_key\/?$/', $path)) {

  // Check If Already Created
  $sshPublicKeyFile = HOMEDIR . DIRECTORY_SEPARATOR . '.ssh' . DIRECTORY_SEPARATOR . 'id_rsa.pub';
  $sshPrivKeyFile = HOMEDIR . DIRECTORY_SEPARATOR . '.ssh' . DIRECTORY_SEPARATOR . 'id_rsa';
  $comment = isset($_GET['mail']) ? $_GET['mail'] : APP_USER . '@' . gethostname();

  $sshPublicKey = null;
  if (file_exists($sshPublicKeyFile) && file_exists($sshPrivKeyFile))
  {
    // Read Public Key
    $sshPublicKey = trim(file_get_contents($sshPublicKeyFile));
    // Check Key Comment Match If Not Create New One
    if (strpos($sshPublicKey, $comment) === false) $sshPublicKey = null;
  }

  if (is_null($sshPublicKey))
  {
    try
    {
      // Generate New Public Key
      $sshPublicKey = generateSSHKeys($sshPublicKeyFile, $sshPrivKeyFile, $comment);
    }
    catch (Exception $e)
    {
      $body = array('success' => 'error', 'message' => $e->getMessage());
    }
  }

  if ($body['success'] !== 'error')
    $body = array('success' => 'ok', 'public_key' => $sshPublicKey);

} // END: Register New Ticket

// Ping Service
if (preg_match_all('/^ping\/?$/', $path)) {
  $body = array('success' => 'ok', 'computer_name' => gethostname(), 'user' => APP_USER);
} // END: Ping Service

// Check directory exists
if (preg_match_all('/^check_directory\/?$/', $path)) {

  // Get & Parse Request Body
  $requestBody = @file_get_contents('php://input');
  $requestJson = @json_decode($requestBody, true);

  // Set Values
  $directory = isset($requestJson['directory']) ? $requestJson['directory'] : null;

  // Check Values
  if (is_null($directory) || !is_string($directory))
  {
    http_response_code(400);
    $body = array('success' => 'error', 'message' => 'Bad Request', 'code' => 400);
  }
  else
  {
    try
    {
      $folder_exists = file_exists($directory);
      $dirname = dirname($directory);
      $folder = null;
      if (!$folder_exists)
      {
        $folder = str_replace($dirname . DIRECTORY_SEPARATOR, '', $directory);
        if (!file_exists($dirname)) mkdir($dirname, null, true);
      }
      $body = array('success' => 'error', 'exists' => $folder_exists, 'dirname' => $dirname, 'folder' => $folder);
    }
    catch (Exception $e)
    {
      http_response_code(500);
      $body = array('success' => 'error', 'message' => $e->getMessage(), 'code' => $e->getCode());
    }
  }

} // END: Check directory exists

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

// Generate SSH Key Pair OPEN_SSH Format
function generateSSHKeys($sshPublicKeyFile, $sshPrivKeyFile, $comment)
{
  try
  {
    // Create new one
    $rsa = new phpseclib\Crypt\RSA();
    // Change Output format to OpenSSH
    $rsa->setPublicKeyFormat(phpseclib\Crypt\RSA::PUBLIC_FORMAT_OPENSSH);
    // Set Comment
    $rsa->setComment($comment);
    // Set Key Length And Create
    $result = $rsa->createKey(2048);
    // Save Public Key
    $sshPublicKey = $result['publickey'];
    if (file_exists($sshPublicKeyFile)) unlink($sshPublicKeyFile);
    file_put_contents($sshPublicKeyFile, $sshPublicKey);
    // Save Private Key
    $sshPrivKey = $result['privatekey'];
    if (file_exists($sshPrivKeyFile)) unlink($sshPrivKeyFile);
    file_put_contents($sshPrivKeyFile, $sshPrivKey);
    // Return Public Key
    return $sshPublicKey;
  }
  catch (Exception $e)
  {
    throw $e;
  }
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

// Add User to Response
$body['user'] = APP_USER;

// Pump Json To Response
echo json_encode($body);

?>
