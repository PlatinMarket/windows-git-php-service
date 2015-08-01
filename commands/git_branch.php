<?php

// Include SharedLib
require_once '/lib/proc.php';

// Main Function
function git_branch($repo, $exArgs = array())
{
  $command = "git branch";
  if (is_array($exArgs) && !empty($exArgs)) $command .= " " . implode(' ', $exArgs);
  return executeCommand($command, $repo);
}

?>
