<?php

// Include SharedLib
require_once '_shared.php';

// Main Function
function git_pull($repo, $exArgs = array())
{
  $command = "git pull";
  if (is_array($exArgs) && !empty($exArgs)) $command .= " " . implode(' ', $exArgs);
  return executeCommand($command, $repo);
}

?>
