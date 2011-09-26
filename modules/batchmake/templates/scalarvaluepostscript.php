<?php

include_once '$BASE_PATH$modules/batchmake/library/KwBatchmakeCondor.php';
$workDir = '$WORK_DIR$';
$app = 'Default';
$baseURL = 'http://localhost/midas3'; // todo CHANGE
$email = '$EMAIL$';
$apiKey = '$API_KEY$';
$taskId = '$TASK_ID$';


$kwBC = new KwBatchmakeCondor($workDir, $app, $baseURL, $email, $apiKey);


// TODO possible count of args
if (!isset($argv) or !$argv)
  {
  return -999;
  }

$i = 1;  // 0 index is scriptname
$jobName       = $argv[$i++];
$jobId         = $argv[$i++];
$returnCode    = $argv[$i++];
//$itemId        = $argv[$i++];
$outputfile    = $argv[$i++];

// want to upload logs, or at least note that this dagjob is done


$kwBC->parseScalar($workDir.'/'.$outputfile);



return 0;


?>