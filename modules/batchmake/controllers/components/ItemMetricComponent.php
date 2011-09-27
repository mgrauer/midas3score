<?php
/*=========================================================================
MIDAS Server
Copyright (c) Kitware SAS. 20 rue de la Villette. All rights reserved.
69328 Lyon, FRANCE.

See Copyright.txt for details.
This software is distributed WITHOUT ANY WARRANTY; without even
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
PURPOSE.  See the above copyright notices for more information.
=========================================================================*/
?>
<?php
/**
 *  Batchmake_ItemMetricComponent
 *  provides utility methods to list, create and execute ItemMetrics.
 */
class Batchmake_ItemMetricComponent extends AppComponent
{

  protected $batchmakeComponent;
  protected $exportComponent;
  
  
  /**
   * Constructor, loads ini from standard config location, unless a
   * supplied alternateConfig.
   * @param string $alternateConfig path to alternative config ini file
   */
  public function __construct($alternateConfig = null)
    {
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $this->batchmakeComponent = new Batchmake_KWBatchmakeComponent($alternateConfig);
    require_once BASE_PATH.'/core/controllers/components/ExportComponent.php';
    $this->exportComponent = new ExportComponent();
    } // end __construct($alternateConfig)


  /**
   * @TODO better docs
   * @return type
   * getMetrics() : will return a list of metricIds and corresponding names
   */
  public function getMetrics()
    {
    //@TODO possibly some paging/limits on the fetch
    $modelLoad = new MIDAS_ModelLoader();
    $itemmetricModel = $modelLoad->loadModel('Itemmetric', 'batchmake');
    $itemmetricDaos = $itemmetricModel->getAll();
    $idsAndNames = array();
    foreach($itemmetricDaos as $dao)
      {
      $itemMetric = array();
      $itemMetric['itemmetric_id'] = $dao->getItemmetricId();
      $itemMetric['metric_name'] = $dao->getMetricName();
      $idsAndNames[] = $itemMetric;
      }

    return $idsAndNames;
    }

  /**
   * @TODO better docs
   * @return type
   */
  public function addMetric($userDao, $itemId, $metricName)
    {
    $bmConfig = $this->batchmakeComponent->getCurrentConfig(); 
    $appDir = $bmConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    $itemmetricDir = $appDir .'/'. 'itemmetric';

    $modelLoad = new MIDAS_ModelLoader();
    $itemmetricModel = $modelLoad->loadModel('Itemmetric', 'batchmake');
    $itemmetricDao = $itemmetricModel->createItemmetric($userDao, $metricName, $itemId, $itemmetricDir);
    $metricId = $itemmetricDao->getItemmetricId();
    /*
    want to create a bms from a template, replacing exe name
    want to create a bmm from a template, replacing exename and path
    assume for now the exe is in the app dir, we'll want it to be loaded
    from an item into the itemmetrics dir, created under tmp and batchmake
    */
    
    return $metricId;
    }

  public function createItemmetricBms($workDir, $itemmetricName, $configVars)
    {
    // load the template bms file
    // TODO constantize paths
    $templateFile = BASE_PATH.'/modules/batchmake/templates/template.bms';
    $contents = file_get_contents($templateFile);
    // now substitute
    $placeholder = '$ItemMetricName$';
    $replacedContents = str_replace($placeholder,$itemmetricName,$contents);
    // TODO check response of write
    $bmScript = $itemmetricName . '.bms';
    file_put_contents($workDir . $bmScript, $configVars . $replacedContents);
      
      
    // TODO set a template dir constant property  
    // load the template bms
    // replace the $ItemMetricName$ with the actual value
    // write out the file as itemmetric.bms to the work dir 
    return $bmScript;
    }
    
    
  public function createItemmetricBmm($workDir, $itemmetricName)
    {
    $bmConfig = $this->batchmakeComponent->getCurrentConfig(); 
    $appDir = $bmConfig[MIDAS_BATCHMAKE_APP_DIR_PROPERTY];
    // TODO constantize paths
    $templateFile = BASE_PATH.'/modules/batchmake/templates/template.bmm';
    $contents = file_get_contents($templateFile);
    $replacedContents = str_replace('$ItemMetricName$',$itemmetricName,$contents);
    $replacedContents2 = str_replace('$ItemMetricPath$',$appDir,$replacedContents);
    // TODO check response of write
    $bmm = $workDir . $itemmetricName . '.bmm';
    file_put_contents($bmm,$replacedContents2);
    // while we are here, symlink in the PHP bmm
    $bmmTarget = $appDir . '/PHP.bmm';
    $bmmLink = $workDir . '/PHP.bmm';
    symlink($bmmTarget, $bmmLink);
    }
    

  public function createMidasCallBackScripts($workDir, $email, $baseURL, $apiKey, $dagName, $taskId, $callBackParams)
    {
    // set propery values for the daguploader from template
      
    // CLEAN THIS UP, method maybe  
    $templateFile = BASE_PATH.'/modules/batchmake/templates/daguploader.php';
    $contents = file_get_contents($templateFile);
    $replacedContents = str_replace('$BASE_PATH$',BASE_PATH,$contents);
    $replacedContents2 = str_replace('$WORK_DIR$', $workDir, $replacedContents);
    $replacedContents3 = str_replace('$EMAIL$', $email, $replacedContents2);
    $replacedContents4 = str_replace('$API_KEY$', $apiKey, $replacedContents3);
    $replacedContents5 = str_replace('$DAG_NAME$', $dagName, $replacedContents4);
    $replacedContents6 = str_replace('$TASK_ID$', $taskId, $replacedContents5);
    $outPath = $workDir .'/daguploader.php';
    file_put_contents($outPath,$replacedContents6);    
    
    
    
    // still need to do postscript one
    // for post script one, add in log times
    // 
    // 
    // for post script one, add in scalar values
    // .. finished boolean
    
    // really want to be able to pass in a function that can set script, vars
    // to replace, replacement vals, and call back functions (web api)
    $scalarValueName = $callBackParams['SCALAR_VALUE_NAME'];
    $webApiMethod = $callBackParams['WEB_API_METHOD'];
    $webApiParams = $callBackParams['WEB_API_PARAMS'];
    $webApiParamStr = 'array(';
    foreach($webApiParams as $key=>$value)
      {
      $webApiParamStr .= "'".$key . "'=>" . $value . ',';
      }
    $webApiParamStr .= ')';

    
    $templateFile = BASE_PATH.'/modules/batchmake/templates/scalarvaluepostscript.php';
    $contents = file_get_contents($templateFile);
    $replacedContents = str_replace('$BASE_PATH$',BASE_PATH,$contents);
    $replacedContents2 = str_replace('$WORK_DIR$', $workDir, $replacedContents);
    $replacedContents3 = str_replace('$EMAIL$', $email, $replacedContents2);
    $replacedContents4 = str_replace('$API_KEY$', $apiKey, $replacedContents3);
    $replacedContents5 = str_replace('$TASK_ID$', $taskId, $replacedContents4);
    $replacedContents6 = str_replace('$SCALAR_VALUE_NAME$', $scalarValueName, $replacedContents5);
    $replacedContents7 = str_replace('$WEB_API_METHOD$', $webApiMethod, $replacedContents6);
    $replacedContents8 = str_replace('$WEB_API_PARAMS$', $webApiParamStr, $replacedContents7);
 
    
    $outPath = $workDir .'/scalarvaluepostscript.php';
    file_put_contents($outPath,$replacedContents8);    
    
    
    
    
    
    
    
/*    
   * Set a single scalar result value
   * @param dashboard_id the id of the target dashboard
   * @param folder_id the id of the target result folder
   * @param item_id the id of the result item
   * @param value the value of the result being set
   * @return the id of the created scalar result

    
   $callBackParams should be:
       params:  name=>value,
       scalarValueName,
       webAPICall
       
    
    extra_params, scalarValueVariableName, web.api.call
    
    param vals, web ApiCall
    
           
           
    dashboard_id, $DASHBOARD_ID$
    folder_id, $FOLDER_ID$
    item_id, $ITEM_ID$
    value, $SCALAR_VALUE$???
           
           
           
    web api call
    
  */  
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
  /**
   * 
   * @TODO better docs
   * @return type
   */
  public function compareItems($userDao, $itemId1, $itemId2, $metricId, $callBackParams, $webApiApplication = 'Default')
    {
    if(!$userDao)
      {
      throw new Zend_Exception('You need to have a valid user session to compare items with an ItemMetric');
      }
    // get an api key for this user
    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $userApiDao = $userApiModel->getByAppAndUser($webApiApplication,$userDao);
    if(!$userApiDao)
      {
      throw new Zend_Exception('You need to create a web-api key for this user for application: '.$webApiApplication);
      }

    // what to do if there ain't a key?

      
      
      
    $modelLoad = new MIDAS_ModelLoader();
    $itemmetricModel = $modelLoad->loadModel('Itemmetric', 'batchmake');
    $itemmetricDao = $itemmetricModel->load($metricId);
    if(!$itemmetricDao)
      {
      throw new Zend_Exception('You need to pass in a valid ItemMetric ID');
      }
    $metricName = $itemmetricDao->getMetricName();

    $taskDao = $this->batchmakeComponent->createTask($userDao);
    $workDir = $taskDao->getWorkDir();

    // export the items
    $shouldSymLink = true;
    require_once BASE_PATH.'/core/controllers/components/ExportComponent.php';
    $exportComponent = new ExportComponent();
    $exportComponent->exportBitstreams($userDao, $workDir, array($itemId1, $itemId2), $shouldSymLink);
        
    // for now assume compare same filename per each item
    
    // TODO this section should be revisited/revised/cleaned
    // 
    // want to write out item names as config 
    $configVars = "# Imported parameters from Midas\n";
    $configVars .= "Set(cfg_item1 '".$workDir.$itemId1."')\n";
    $configVars .= "Set(cfg_item2 '".$workDir.$itemId2."')\n";
    // choice of policies
    //$configVars .= "Set(cfg_compare_mode 'FILENAME_MATCH')\n";
    $configVars .= "Set(cfg_compare_mode 'CARTESIAN_PRODUCT')\n";
    

    $configVars .= "Set(cfg_apikey '".$userApiDao->getApikey()."')\n";
    $configVars .= "Set(cfg_appname 'Default')\n";
    $configVars .= "Set(cfg_email '".$userDao->getEmail()."')\n";
    // TODO Where TF to get this?
    //http://localhost/midas3
    $baseURL = 'http://localhost/midas3';
    $configVars .= "Set(cfg_midas_baseURL '".$baseURL."')\n";
    $configVars .= "Set(cfg_condorpostscript '".$workDir."scalarvaluepostscript.php')\n";
    $configVars .= "Set(cfg_work '".$workDir."')\n";
    $configVars .= "Set(cfg_midasbasepath '".BASE_PATH."')\n";
    $configVars .= "Set(cfg_taskId '".$taskDao->getKey()."')\n";
    $configVars .= "Set(cfg_php_path '/usr/bin/php')\n"; //TODO get this from somewhere
    
    
    
    $bmScript = $this->createItemmetricBms($workDir, $metricName, $configVars);
//echo "wrote bmScript[$bmScript]";
    $this->createItemmetricBmm($workDir, $metricName);
   
    // symlink in the PHP bmm

    
    // TODO error checking for these following methods
    $this->batchmakeComponent->compileBatchMakeScript($workDir, $bmScript);
    $dagScript = $this->batchmakeComponent->generateCondorDag($workDir, $bmScript);
    
    
    
    
    $this->createMidasCallBackScripts($workDir, $userDao->getEmail(), $baseURL, $userApiDao->getApikey(), $dagScript, $taskDao->getKey(), $callBackParams);
    
    
    
    
    $this->batchmakeComponent->condorSubmitDag($workDir, $dagScript);



//    $this->assertTrue(false);
//    - compareItems(itemId_1, itemId_2, metricId) : will export item1 and item2, and run a batchmake exe reference by metricId on those two items, storing the value(s) in some database, returning the comparisonId
    return $taskDao;
    }






} // end class
?>