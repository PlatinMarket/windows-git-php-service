<?php
try
{
  $workDir = dirname(__FILE__);
  $xmlFile = file_get_contents($workDir . DIRECTORY_SEPARATOR . 'git_php_service_skel.xml');
  $phpExec = PHP_BINARY;
  $xmlFile = mb_convert_encoding($xmlFile, "UTF-8", "UCS-2LE");
  $xmlFile = str_replace(array('{{PHP}}', '{{WORKDIR}}'), array($phpExec, $workDir), $xmlFile);
  $xmlFile = mb_convert_encoding($xmlFile, "UCS-2LE", "UTF-8");
  file_put_contents($workDir . DIRECTORY_SEPARATOR . 'git_php_service.xml', $xmlFile);
  exit(0);
}
catch(Exception $e)
{
  echo $e->getMessage();
  if (file_exists('git_php_service.xml')) unlink('git_php_service.xml');
  exit(1);
}
?>
