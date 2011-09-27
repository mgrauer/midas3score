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
abstract class Batchmake_ItemmetricModelBase extends Batchmake_AppModel {

  /**
   * constructor
   */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'batchmake_itemmetric';
    $this->_key = 'itemmetric_id';

    $this->_mainData = array(
      'itemmetric_id' => array('type' => MIDAS_DATA),
      'metric_name' => array('type' => MIDAS_DATA),
      'exe_path' => array('type' => MIDAS_DATA),
      );
    $this->initialize(); // required
    }

  /** Create an Itemmetric
   * @return ItemmetricDao, will throw a Zend_Exception if an
   * Itemmetric already exists with this metricName
   */
  public function createItemmetric($userDao, $metricName, $itemId, $itemmetricDir)
    {
    include_once BASE_PATH . '/library/KWUtils.php';
    if(!file_exists($itemmetricDir) && !KWUtils::mkDir($itemmetricDir))
      {
      throw new Zend_Exception('Itemmetric dir '.$itemmetricDir.' does not exist and could not be created');  
      }
      
    $this->loadDaoClass('ItemmetricDao', 'batchmake');
    $itemmetric = new Batchmake_ItemmetricDao();

    // make sure one isn't already there by this name
    $found = $this->findBy('metric_name', $metricName);
    if(isset($found) && sizeof($found) > 0)
      {
      // don't allow the creation, as we have a metric of this name already
      throw new Zend_Exception('An Itemmetric already exists with that name');
      }

    // symlink the exe from the item
    $shouldSymLink = true;
    require_once BASE_PATH.'/core/controllers/components/ExportComponent.php';
    $exportComponent = new ExportComponent();
    $exportComponent->exportBitstreams($userDao, $itemmetricDir, array($itemId), $shouldSymLink);
    
    // assume name of exe is the same as that of the metricName, try to find it
    $exePath = $itemmetricDir . '/'. $itemId . '/' . KWUtils::formatAppName($metricName);
    if(!file_exists($exePath))
      {
      // clean out the exported item
      $exportedItemPath = $itemmetricDir . '/'. $itemId;
      KWUtils::recursiveRemoveDirectory($exportedItemPath);
      // ensure that exe name is the same as the metricname
      throw new Zend_Exception('An Itemmetric must have the same name as its executable');
      }
  
    $itemmetric->setMetricName($metricName);
    $itemmetric->setExePath($exePath);
    $this->save($itemmetric);
    return $itemmetric;
    } // end createItemmetric()

    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    

  /**
   * getAll returns all rows
   */
  public abstract function getAll();





}  // end class Batchmake_ItemmetricModelBase
