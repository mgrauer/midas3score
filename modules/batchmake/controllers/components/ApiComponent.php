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

/** Component for api methods */
class Batchmake_ApiComponent extends AppComponent
{
    
    
  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', -1);
        }
      }
    }
    
  /**
   * Get the list of existing ItemMetricIds and their names
   * @return an array of itemmetric_id's and corresponding names.
   */
  public function getItemMetrics($value)
    {
    $componentLoader = new MIDAS_ComponentLoader();
    $itemmetricComponent = $componentLoader->loadComponent('ItemMetric', 'batchmake');
    return $itemmetricComponent->getMetrics();
    }
    
  /**
   * @param item_id an id of an existing item, which should have an executable
   * of the same name as the metric_name param
   * @param metric_name the name of the newly created metric, should not conflict
   * with any existing metrics 
   * @return the itemmetric_id of the newly created ItemMetric.
   */
  public function addItemMetric($value)
    {
    $this->_checkKeys(array('item_id', 'metric_name'), $value);

    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao)
      {
      throw new Zend_Exception('You need to have a valid user session to create an ItemMetric');
      }
 
    $componentLoader = new MIDAS_ComponentLoader();
    $itemmetricComponent = $componentLoader->loadComponent('ItemMetric', 'batchmake');
 
    return array($itemmetricComponent->addMetric($userDao, $value['item_id'], $value['metric_name']));
    }
    
    
  /**
   * will compare two items, calculating a scalar value, then sending this back
   * very much a work in progress
   * @param dashboard_id id of dashboard to send back to
   * @param folder_id id of result folder to send back to
   * @param truth_item_id id of truth value item
   * @param result_item_id id of result value item, to send back to
   * @param metric_id id of itemmetric used to calculate value
   * @return doesn't return anything, but will call midas.validation.setscalarresult
   * with the result of calling the passed in itemmetric on the two passed in items. 
   */  
  public function compareItems($value)
    {
    $this->_checkKeys(array('truth_item_id', 'result_item_id', 'dashboard_id', 'folder_id', 'metric_id'), $value);

    $componentLoader = new MIDAS_ComponentLoader();
    $authComponent = $componentLoader->loadComponent('Authentication', 'api');
    $userDao = $authComponent->getUser($value,
                                       Zend_Registry::get('userSession')->Dao);
    if(!$userDao)
      {
      throw new Zend_Exception('You need to have a valid user session to compare items with an ItemMetric');
      }
 
    $componentLoader = new MIDAS_ComponentLoader();
    $itemmetricComponent = $componentLoader->loadComponent('ItemMetric', 'batchmake');
 
    $scalarValueName = 'value';
    $webApiParams = array('dashboard_id'=>$value['dashboard_id'],'folder_id'=>$value['folder_id'],'item_id'=>$value['result_item_id']);
    $webApiMethod = 'midas.validation.setscalarresult';
    $callBackParams = array('SCALAR_VALUE_NAME'=>$scalarValueName, 'WEB_API_METHOD'=>$webApiMethod,
        'WEB_API_PARAMS'=>$webApiParams);

    return array($itemmetricComponent->compareItems($userDao, $value['truth_item_id'], $value['result_item_id'], $value['metric_id'], $callBackParams));
    }    
    
    
    
    

    
} // end class