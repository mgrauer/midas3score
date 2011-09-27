<?php

include_once '$BASE_PATH$/modules/batchmake/library/KwBatchmakeCondor.php';
$workDir = '$WORK_DIR$';
$app = 'Default';
$baseURL = 'http://localhost/midas3'; // todo CHANGE
$email = '$EMAIL$';
$apiKey = '$API_KEY$';
$taskId = '$TASK_ID$';

// this value is expected to be replaced by an array
$webApiParams = $WEB_API_PARAMS$;
$scalarValueName = '$SCALAR_VALUE_NAME$';
$webApiMethod = '$WEB_API_METHOD$';



$kwBC = new KwBatchmakeCondor($workDir, $app, $baseURL, $email, $apiKey);


// TODO possible count of args
if (!isset($argv) or !$argv)
  {
  return -999;
  }

$i = 1;  // 0 index is scriptname
$outputfile    = $argv[$i++];
$jobName       = $argv[$i++];
$jobId         = $argv[$i++];
$returnCode    = $argv[$i++];
//$itemId        = $argv[$i++];

// want to upload logs, or at least note that this dagjob is done
// do some work for task (general dagjob processing)

$kwBC->scalarValueCallback($outputfile, $webApiParams, $scalarValueName, $webApiMethod);



return 0;


?>