<?php

// Main Function
function git_branch_execute($repo, $exArgs = array())
{
  $command = "git branch";
  if (is_array($exArgs) && !empty($exArgs)) $command .= " " . implode(' ', $exArgs);
  return executeCommand($command, $repo);
}

// Validate Function
function git_branch_validate($repo = null)
{
  if (is_null($repo) || !is_string($repo)) throw new Exception('Repository folder argument not set', 400);
  if (!file_exists($repo)) throw new Exception('Repository folder \'' . $repo . '\' not found!', 400);
  if (!file_exists($repo . DIRECTORY_SEPARATOR . '.git')) throw new Exception('Invalid git repository \'' . $repo . '\'', 400);
  return true;
}

?>
