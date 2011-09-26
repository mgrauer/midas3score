<?php

include_once '$BASE_PATH$modules/batchmake/library/KwBatchmakeCondor.php';
$workDir = '$WORK_DIR$';
$app = 'Default';
$baseURL = 'http://localhost/midas3'; // todo CHANGE
$email = '$EMAIL$';
$apiKey = '$API_KEY$';
$taskId = '$TASK_ID$';


$kwBC = new KwBatchmakeCondor($workDir, $app, $baseURL, $email, $apiKey);

$kwBC->parseDag('$DAG_NAME$');


?>