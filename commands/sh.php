<?php

// Main Function
function sh_execute($command)
{
  sh_cleanMotd();
  $command = "sh --login -i -c \"" . $command;

  $exArgs = array_slice(func_get_args(), 1);
  if (is_array($exArgs) && !empty($exArgs)) $command .= " " . implode(' ', $exArgs);

  $command .= "\"";
  return executeCommand($command, "C:\\");
}

function sh_cleanMotd()
{
  try
  {
    if (isset($_SERVER['ProgramFiles']))
    {
      $motd = $_SERVER['ProgramFiles'] . DIRECTORY_SEPARATOR . 'Git' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'motd';
      if (file_exists($motd)) file_put_contents($motd, null);
    }
    if (isset($_SERVER['ProgramFiles(x86)']))
    {
      $motd = $_SERVER['ProgramFiles(x86)'] . DIRECTORY_SEPARATOR . 'Git' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'motd';
      if (file_exists($motd)) file_put_contents($motd, null);
    }
    return true;
  }
  catch (Exception $e)
  {
    return false;
  }
}

?>
