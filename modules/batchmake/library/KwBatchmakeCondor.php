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
    
  public function parseDagJob($workDir)
    {
    $properties = array('Output'=>'outputPath','Error'=>'errorPath','Log'=>'logPath','Executable'=>'executable','Arguments'=>'arguments');
    $dagJobPath = $workDir.'/'.$this->jobFile;
    $contents = file_get_contents($dagJobPath);
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
    echo $this->webApiToken;
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
    $token = $xmlObj->token;
    return $token;
    }
  
    
  public function parseDag($dagName)
    {
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
      $dagJob->parseDagJob($this->workDir);
      }
     
    }
        
  public function parseScalar($outputFile)
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
      echo $scalar;
    }
    

    
    
    
}


/*
//    public static function 

$kwBC = new KwBatchmakeCondor();


$app = 'Default';
$baseURL = 'http://localhost/midas3';
$email = 'michael.grauer@kitware.com';
$apiKey = 'YRnxITpEmr8x9NvJ3AbUBKQCi0KKEgasXnQv650j';

$token = $kwBC->getMidasWebAPIToken($baseURL, $email, $app, $apiKey);//'http://localhost/midas3','user1@user1.com','Default','9c4ae85257285cdbb6431ad4af9cee2e');

echo $token;



exit();

*/


//dag parser: parse job definition, parse dag, create dag in memory, parent, childern, read log of overall dag, refresh/replace
        
//        job parser, read output, error, log, executable, args, read the 3 logs
        
// want to parse out scalar value        



?>
