<?php

// Include SharedLib
require_once '_shared.php';

// Main Function
function git_status($repo)
{
  return executeCommand("git status", $repo);
}

?>
