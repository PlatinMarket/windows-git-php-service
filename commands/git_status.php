<?php

// Include SharedLib
require_once '/lib/proc.php';

// Main Function
function git_status($repo)
{
  return executeCommand("git status", $repo);
}

?>
