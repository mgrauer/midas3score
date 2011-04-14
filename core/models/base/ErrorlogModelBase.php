<?php
abstract class ErrorlogModelBase extends AppModel
{
  public function __construct()
    {
    parent::__construct();  
    $this->_name = 'errorlog';
    $this->_key = 'errorlog_id'; 
    $this->_mainData= array(
      'errorlog_id'=> array('type'=>MIDAS_DATA),
      'module'=> array('type'=>MIDAS_DATA),
      'message'=> array('type'=>MIDAS_DATA),
      'datetime'=> array('type'=>MIDAS_DATA),
      );
    $this->initialize(); // required
    } // end __construct()
    
} // end class FeedModelBase
?>