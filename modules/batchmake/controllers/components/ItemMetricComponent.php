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

  /**
   * Constructor, loads ini from standard config location, unless a
   * supplied alternateConfig.
   * @param string $alternateConfig path to alternative config ini file
   */
  public function __construct($alternateConfig = null)
    {
    require_once BASE_PATH.'/modules/batchmake/controllers/components/KWBatchmakeComponent.php';
    $this->batchmakeComponent = new Batchmake_KWBatchmakeComponent($alternateConfig);
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
    $idsToNames = array();
    foreach($itemmetricDaos as $dao)
      {
      $idsToNames[$dao->getItemmetricId()] = $dao->getMetricName();
      }

    return $idsToNames;
    }

  /**
   * @TODO better docs
   * @return type
   */
  public function addMetrics($itemId, $metricName)
    {
    // @TODO some checking to ensure we don't add one that already has that
    // name or bmsScript name
    // @TODO some checking to see if config is correct?
    //$metricName = 'MyMetric';
    //$bmsName = 'MyMetric.bms';

    /*
CREATE TABLE batchmake_itemmetric (
    itemmetric_id bigint(20) NOT NULL AUTO_INCREMENT,
    metric_name character varying(64) NOT NULL,
    bms_name character varying(256) NOT NULL,
    PRIMARY KEY (itemmetric_id)
);*/
    // HACK for now set bmsName as itemId
   // $bmsName = $itemId . '.bms';

    // want exe name rather than bms name 
      
    $modelLoad = new MIDAS_ModelLoader();
    $itemmetricModel = $modelLoad->loadModel('Itemmetric', 'batchmake');
    $itemmetricDao = $itemmetricModel->createItemmetric($metricName, $bmsName);
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
    

  public function createMidasCallBackScripts($workDir, $email, $baseURL, $apiKey, $dagName, $taskId)
    {
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
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
  /**
   * 
   * @TODO better docs
   * @return type
   */
  public function compareItems($userDao, $itemId1, $itemId2, $metricId)
    {
//    $userSession = Zend_Registry::get('userSession');
//var_dump($userSession);    
//    $userDao = $userSession->Dao;
echo "compareItems ($itemId1, $itemId2, $metricId)";    
//var_dump($userDao);
    $modelLoad = new MIDAS_ModelLoader();
    $itemmetricModel = $modelLoad->loadModel('Itemmetric', 'batchmake');
    $itemmetricDao = $itemmetricModel->load($metricId);
    // TODO check if no dao
    $metricName = $itemmetricDao->getMetricName();

    
    
    
    
    
    
    
    // TODO get the userDao
    // create the task and workDir
    $taskDao = $this->batchmakeComponent->createTask($userDao);
    $workDir = $taskDao->getWorkDir();
echo "workDir[$workDir] metricName[$metricName]";    
    // TODO get the dir where the itemmetric apps live
    // TODO this will probably change
    //$appDir = '';




    // TODO export items to some data dir
    // TODO get config properties
    // TODO write config file
    $shouldSymLink = true;
    require_once BASE_PATH.'/core/controllers/components/ExportComponent.php';
    $exportComponent = new ExportComponent();
    $exportComponent->exportBitstreams($userDao, $workDir, array($itemId1, $itemId2), $shouldSymLink);
    
    
    // for now assume compare same filename per each item
    
    // want to write out item names as config 
    $configVars = "# Imported parameters from Midas\n";
    $configVars .= "Set(cfg_item1 '".$workDir.$itemId1."')\n";
    $configVars .= "Set(cfg_item2 '".$workDir.$itemId2."')\n";
    // choice of policies
    //$configVars .= "Set(cfg_compare_mode 'FILENAME_MATCH')\n";
    $configVars .= "Set(cfg_compare_mode 'CARTESIAN_PRODUCT')\n";
    
    
    // get an api key for this user, for Default application
    $modelLoad = new MIDAS_ModelLoader();
    $userApiModel = $modelLoad->loadModel('Userapi', 'api');
    $userApiDao = $userApiModel->getByAppAndUser('Default',$userDao);

    // what to do if there ain't a key?


    $configVars .= "Set(cfg_apikey '".$userApiDao->getApikey()."')\n";
    $configVars .= "Set(cfg_appname 'Default')\n";
    $configVars .= "Set(cfg_email '".$userDao->getEmail()."')\n";
    // TODO Where TF to get this?
    //http://localhost/midas3
    $baseURL = 'http://localhost/midas3';
    $configVars .= "Set(cfg_midas_baseURL '".$baseURL."')\n";
    $configVars .= "Set(cfg_condorpostscript '".$workDir."midascondoruploader.php')\n";
    $configVars .= "Set(cfg_work '".$workDir."')\n";
    $configVars .= "Set(cfg_midasbasepath '".BASE_PATH."')\n";
    $configVars .= "Set(cfg_taskId '".$taskDao->getKey()."')\n";
    
    $bmScript = $this->createItemmetricBms($workDir, $metricName, $configVars);
//echo "wrote bmScript[$bmScript]";
    $this->createItemmetricBmm($workDir, $metricName);
   
    // symlink in the PHP bmm

    
    // TODO error checking for these following methods
    $this->batchmakeComponent->compileBatchMakeScript($workDir, $bmScript);
    $dagScript = $this->batchmakeComponent->generateCondorDag($workDir, $bmScript);
    
    
    
    
    $this->createMidasCallBackScripts($workDir, $userDao->getEmail(), $baseURL, $userApiDao->getApikey(), $dagScript, $taskDao->getKey());
    
    
    
    
    $this->batchmakeComponent->condorSubmitDag($workDir, $dagScript);



//    $this->assertTrue(false);
//    - compareItems(itemId_1, itemId_2, metricId) : will export item1 and item2, and run a batchmake exe reference by metricId on those two items, storing the value(s) in some database, returning the comparisonId
    return $taskDao;
    }






} // end class
?>