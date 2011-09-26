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

/** Batchmake_ItemmetricController */
class Batchmake_ItemmetricController extends Batchmake_AppController
{




//  public $_moduleForms = array('Config');
  public $_components = array('Utility', 'Internationalization');
  public $_moduleComponents = array('KWBatchmake','ItemMetric');




  /**
   * @method indexAction()
   */
  public function indexAction()
    {
    $userDao = $this->userSession->Dao;
    $taskDao = $this->ModuleComponent->ItemMetric->compareItems($userDao, 58, 59, 1);
    $this->view->taskId = $taskDao->getKey();
    
    }



}//end class
