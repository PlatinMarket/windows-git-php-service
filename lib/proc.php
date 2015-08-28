<?php

// Execute Environment
if (!function_exists('get_env'))
{
  function get_env()
  {
    $tmp = array_merge($_SERVER, $_ENV);
    $env = array();
    foreach ($tmp as $key => $value) if (!is_array($value)) $env[$key] = $value;
    if (defined('HOMEDIR') && file_exists(HOMEDIR)) $env['USERPROFILE'] = HOMEDIR;
    return $env;
  }
}

// Bypass Process Execute
if (!function_exists('executeCommand'))
{
  function executeCommand($command, $run_dir)
  {
    // Prototype result
    $result = array('std_out' => '', 'std_err' => '', 'exit_code' => -1);

    // pipes 0 => stdIn, 1 => stdOut, 2 => stdErr
    $descriptorspec = array(
       0 => array("pipe", "r"),
       1 => array("pipe", "w"),
       2 => array("pipe", "w")
    );

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
    {
      // für Windows
      $options = array(
        'bypass_shell' => true,
        'suppress_errors' => true
      );
      $descriptorspec[2] = array("pipe", "a");
    }

    // Process execute
    $process = proc_open($command, $descriptorspec, $pipes, $run_dir, get_env(), $options);

    stream_set_blocking($pipes[2], 0);

    // Check if resource created
    if (is_resource($process))
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
        {
          // Propage STDIN
          fwrite($pipes[0], "\n");
          fwrite($pipes[0], "\n");
          fwrite($pipes[0], "\n");
        }

        // Close StdIn Future Use
        fclose($pipes[0]);

        // Capture StdErr
        while($s = fgets($pipes[2], 1024)) $result['std_err'] .= $s;

        // Close StdErr
        fclose($pipes[2]);

        // Capture StdOut
        while($s = fgets($pipes[1], 1024)) $result['std_out'] .= $s;

        // Close StdOut
        fclose($pipes[1]);

        // Capture ExitCode
        $result['exit_code'] = proc_close($process);

        // Parse StdOut(s)
        $result['std_out'] = trim(str_replace("***done***", "", $result['std_out']));
        $result['std_err'] = trim($result['std_err']);
    }

    return $result;
  }
}

// Powershell Execute
if (!function_exists('powershell'))
{
  function powershell($file, $args = array())
  {
    $command = BASE . DIRECTORY_SEPARATOR . 'powershell_scripts' . DIRECTORY_SEPARATOR . $file . '.ps1';
    if (is_array($args) && !empty($args))
    {
      $tmp = $args;
      $args = array();
      foreach ($tmp as $key => $value)
      {
        if (is_string($value) || is_numeric($value))
          $args[] = '-' . $key . ' ' . $value;
        elseif (is_bool($value))
          $args[] = '-' . $key . ' ' . ($value === true ? "$true" : "$false");
      }
      $command .= ' ' . (implode(' ', $args));
    }
    $result = executeCommand('powershell.exe -Mta -NoLogo -NonInteractive -executionpolicy remotesigned -File ' . $command, BASE);
    return $result;
  }
}

?>
