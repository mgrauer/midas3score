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
/** Batchmake_DagjobModelBase */
class Batchmake_DagjobModelBase extends Batchmake_AppModel {

  /**
   * constructor
   */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'batchmake_dagjob';
    $this->_key = 'batchmake_dagjob_id';
    $this->_mainData = array(
      'batchmake_dagjob_id' => array('type' => MIDAS_DATA),
      'batchmake_task_id' => array('type' => MIDAS_DATA, ),
      'job_processed' => array('type' => MIDAS_DATA, ),
      'output_path' => array('type' => MIDAS_DATA, ),
      'error_path' => array('type' => MIDAS_DATA, ),
      'log_path' => array('type' => MIDAS_DATA, ),
      'executable' => array('type' => MIDAS_DATA, ),
      'arguments' => array('type' => MIDAS_DATA, )
       );
    $this->initialize(); // required
    }

  /** Create a dagjob
   * @return DagjobDao */
  function createDagjob($taskId)
    {
    $this->loadDaoClass('DagjobDao', 'batchmake');
    $dagjob = new Batchmake_DagjobDao();
    $dagjob->setTaskId($taskId);
    $this->save($dagjob);

    return $dagjob;
    } // end createDagjob()


}  // end class Batchmake_DagjobModelBase





