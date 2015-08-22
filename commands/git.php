<?php

// Main Function
function git_execute($repo)
{
  $command = "git";
  $exArgs = array_slice(func_get_args(), 1);
  if (is_array($exArgs) && !empty($exArgs)) $command .= " " . implode(' ', $exArgs);
  return executeCommand($command, $repo);
}

// Validate Function
function git_validate($repo = null)
{
  if (is_null($repo) || !is_string($repo)) throw new Exception('Repository folder argument not set', 400);
  if (!file_exists($repo)) throw new Exception('Repository folder \'' . $repo . '\' not found!', 400);  
  return true;
}

?>
