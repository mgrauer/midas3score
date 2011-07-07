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

/** job model */
class Scheduler_JobModel extends Scheduler_JobModelBase
{

  /** get jobs*/
  public function getJobsToRun()
    {
    $load = $this->getServerLoad();
    $minPriority = MIDAS_EVENT_PRIORITY_LOW;
    if(!empty($load))
      {
      if($load[0] > 80 || $load[1] > 80) //don't run anything
        {
        return array();
        }
      $minPriority = MIDAS_EVENT_PRIORITY_HIGH;
      if($load[0] < 40 || $load[1] < 40)
        {
        $minPriority = MIDAS_EVENT_PRIORITY_NORMAL;
        }
      if($load[0] < 20 && $load[1] < 20)
        {
        $minPriority = MIDAS_EVENT_PRIORITY_LOW;
        }
      }
      
   $sql = $this->database->select()
          ->setIntegrityCheck(false)
          ->where('priority >= ?', $minPriority)
          ->where('status = ?', SCHEDULER_JOB_STATUS_TORUN)
          ->where('fire_time <= ?', date('c'))
          ->order(array('priority DESC',
                           'fire_time ASC'));
    $rowset = $this->database->fetchAll($sql);
    $return = array();
    foreach($rowset as $row)
      {
      $tmpDao = $this->initDao('Job', $row, 'scheduler');
      $return[] = $tmpDao;
      unset($tmpDao);
      }
    return $return;
    }
    
}  // end class
?>