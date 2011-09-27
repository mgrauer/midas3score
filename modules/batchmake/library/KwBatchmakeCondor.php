<?php
/*=========================================================================
Program:   MIDAS Server
Language:  PHP/HTML/Java/Javascript/SQL
Date:      $Date$
Version:   $Revision$

Copyright (c) Kitware Inc. 28 Corporate Drive. All rights reserved.
Clifton Park, NY, 12065, USA.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php

class DagJob
{
  protected $jobName;
  protected $jobFile;
  protected $parents;
  protected $children;
  protected $outputPath;
  protected $output;
  protected $errorPath;
  protected $error;
  protected $logPath;
  protected $log;
  protected $executable;
  protected $arguments;
  
  function __construct($jobName, $jobFile)
    {
    $this->jobName  = $jobName;
    $this->jobFile = $jobFile;
    $this->parents = array();
    $this->children = array();
    }  

  public function addParent($parentJob)
    {
    $this->parents[] = $parentJob;  
    }
   
  public function addChild($childJob)
    {
    $this->children[] = $childJob;  
    }
    
  protected function parseProperty($contents, $property, $variableName)
    {
    $pattern = '/'.$property.'\s*=\s*(\S*)/';
    preg_match($pattern, $contents, $matches);
    // expect only one match
    if($matches && count($matches) > 1 && isset($matches[1]))
      {
      $this->$variableName = $matches[1];  
      }    
    else
      {
      throw new Exception('Expected property['.$property.'] not found in '.$this->jobFile);  
      }
    }
    
  public function parseDagJob()
    {
    $properties = array('Output'=>'outputPath','Error'=>'errorPath','Log'=>'logPath','Executable'=>'executable','Arguments'=>'arguments');
//    $dagJobPath = $workDir.'/'.$this->jobFile;
    $contents = file_get_contents($this->jobFile);//$dagJobPath);
    foreach($properties as $property=>$variableName)
      {
      $this->parseProperty($contents,$property,$variableName);
      }
    }
}


class KwBatchmakeCondor
{

    
  protected $workDir;    
  protected $baseURL;  
  protected $webApiToken;  
  protected $dagJobs;
  
  
  /** Create the object */
  function __construct($workDir, $appName, $baseURL, $email, $apiKey)
    {
    $this->workDir  = $workDir;
    $this->baseURL = $baseURL;
    $this->webApiToken = $this->getMidasWebAPIToken($baseURL, $email, $appName, $apiKey);
    echo "token[$this->webApiToken]";
    // TODO some error checking
    }
      

  /**
    * utility function to call curl with the passed in options, 
    * returns the curl response.
    */
  function callCurl($optionsArray)
    {
    $curlObj = curl_init();
    foreach($optionsArray as $name => $value)
      {
      curl_setopt ($curlObj, $name, $value);
      }
    $response = curl_exec ($curlObj);
    curl_close ($curlObj);
    return $response;
    }

   /**
    * utility function to retrieve a Midas Web API token based on a 
    * passed in set of Web API params, returns the token.
    */
  function getMidasWebAPIToken($midasBaseURL,$email,$appName,$apiKey)
    {
    $midasREST = '/api/rest';//?method=midas.login';   
//echo "login[$midasBaseURL$midasLoginREST]";
//http://localhost/midas3/api/json?method=METHOD_NAME
    $curlOptions = array();
    $post = array();
    $post['method'] = 'midas.login';//$email;
    $post['email'] = $email;
    $post['appname'] = $appName;
    $post['apikey'] = $apiKey;

    $curlOptions[CURLOPT_URL] = $midasBaseURL.$midasREST;
    $curlOptions[CURLOPT_HEADER] = false;
    $curlOptions[CURLOPT_POST] = true;
    $curlOptions[CURLOPT_POSTFIELDS] = $post;
    $curlOptions[CURLOPT_RETURNTRANSFER] = true;


#var_dump($curlOptions);

    $loginResponse = $this->callCurl($curlOptions);
    //Set the token
#$echo $loginResponse;
#exit();
    $xmlObj = simplexml_load_string($loginResponse);
#    echo $xmlObj;
    $token = (string)$xmlObj->token;
    return $token;
    }
  
    
  public function parseDag($dagName)
    {
//echo "workdir[$this->workDir]";
    $dagPath = $this->workDir.'/'.$dagName;    
    // want to parse out all the jobs, figure out the DAG and the job names
    $contents = file_get_contents($dagPath);
    $pattern = '/Job\s*(\S*)\s*(\S*)/';
    preg_match_all($pattern, $contents, $matches);
    // keep a list of the dagJobs, indexed by name
    $dagJobs = array();
    // ensure that there actually are matches
    if($matches && count($matches) > 1)
      {
    
      $jobNames = $matches[1];
      $jobFiles = $matches[2];
      // both arrays should have same indexing
      foreach($jobNames as $ind=>$jobName)
        {
        $jobFile = $jobFiles[$ind];
        $dagJob = new DagJob($jobName,$jobFile);
        $dagJobs[$jobName] = $dagJob;          
        }
      }
    // create parent/child relationships
    // this may not be needed
    $pattern = '/PARENT\s*(\S*)\s*CHILD\s*(\S*)/';
    preg_match_all($pattern, $contents, $matches);
    // ensure that there actually are matches
    if($matches && count($matches) > 1)
      {
     
      $parents = $matches[1];
      $children = $matches[2];
      // both arrays should have same indexing
      foreach($parents as $ind=>$parentJobId)
        {
        $childJobId = $children[$ind];
        $childJob = $dagJobs[$childJobId];
        $parentJob = $dagJobs[$parentJobId];
        $childJob->addParent($parentJob);
        $parentJob->addChild($childJob);
        }
      }
    $this->dagJobs = $dagJobs;
    foreach($dagJobs as $dagJob)
      {
      $dagJob->parseDagJob();
      }
     
    }
        
  protected function parseScalar($outputFile)
    {
    $outputPath = $this->workDir.'/'.$outputFile;    
    // want to parse out all the jobs, figure out the DAG and the job names
    $contents = file_get_contents($outputPath);
    $pattern = '/\S*\s*=\s*(\S*)/';
    preg_match($pattern, $contents, $matches);
    // expect only one match
    if($matches && count($matches) > 1 && isset($matches[1]))
      {
      $scalar = $matches[1];  
      }    
    else
      {
      throw new Exception('Expected scalar value not found in '.$outputFile);  
      }
    return $scalar;
    }
    
    
  public function scalarValueCallback($outputFile, $webApiParams, $scalarValueName, $webApiMethod)
    {
    // get the scalar value
    $scalarValue = $this->parseScalar($outputFile);
echo "scalarValue[$scalarValue]";  
    // add it to the other params
    $webApiParams[$scalarValueName] = $scalarValue;
    // call the web api method to upload the scalar value
    
    $midasREST = '/api/rest';
    $curlOptions = array();
    $post = array();
    foreach($webApiParams as $name=>$value)
      {
      $post[$name] = $value;  
      }
    $post['token'] = $this->webApiToken;
    $post['method'] = $webApiMethod;
var_dump($post);
    //$curlOptions[CURLOPT_URL] = $this->baseURL.$midasREST;
    $curlOptions[CURLOPT_URL] = $this->baseURL.$midasREST;//.'?method='.$webApiMethod.'&token='.$this->webApiToken.'&dashboard_id='.$post['dashboard_id'].'&folder_id='.$post['folder_id'].'&item_id='.$post['item_id'].'&value='.$post['value'];
//echo $curlOptions[CURLOPT_URL];
    $curlOptions[CURLOPT_HEADER] = false;
    $curlOptions[CURLOPT_POST] = true;
    $curlOptions[CURLOPT_POSTFIELDS] = $post;
    $curlOptions[CURLOPT_RETURNTRANSFER] = true;
    $response = $this->callCurl($curlOptions);
//    $xmlObj = simplexml_load_string($response);

    // NOW WHAT??
    
    echo "response[$response]";
  //  echo "xmlObj[$xmlObj]";
    }

    
/**
      function uploadBitstreamToMidas($midasBaseURL,$jobName,$jobId,$returnCode,$parentItemId,$userEmail,$userAppName,$userApiKey,$pathToFile,$fileName)
    {

    // login to Midas via webAPI key
    $token = getMidasWebAPIToken($midasBaseURL,$userEmail,$userAppName,$userApiKey);
    if(!$token)
      {
      KwUtils::Error("Failed to obtain Midas WebAPI token\n");
      return -1;
      }

    list($uploadtoken,$userid) = getMidasUploadToken($midasBaseURL,$token,$parentItemId,$fileName);

    //get the file properties
    $filePath = $pathToFile .'/'. $fileName;
    $size = filesize($filePath);
    $pathInfo = pathinfo($filePath);
    $fp = fopen($filePath,'r');

    // set up the url with query params
    $midasUploadBitstreamREST = '/api/rest/midas.upload.bitstream';
    $url = $midasBaseURL . $midasUploadBitstreamREST . "?uuid=&itemid=".$parentItemId."&mode=stream&filename=".$fileName."&path=".$filePath."&size=".$size."&uploadtoken=".$uploadtoken."&userid=".$userid;
    $curlOptions = array();
    $curlOptions[CURLOPT_URL] = $url;
    $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
    $curlOptions[CURLOPT_SSL_VERIFYHOST] = 1;
    $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
    $curlOptions[CURLOPT_RETURNTRANSFER] = true;
    $curlOptions[CURLOPT_UPLOAD] = true;
    $curlOptions[CURLOPT_INFILESIZE] = $size;
    $curlOptions[CURLOPT_INFILE] = $fp;
    $uploadResponse = callCurl($curlOptions);
    // TODO some checking of $uploadResponse, a bit difficult because the response is full of junk and not an xml doc
    return 0;
    }
*/

    
    
}

?>
