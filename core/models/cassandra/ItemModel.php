<?php
require_once BASE_PATH.'/core/models/base/ItemModelBase.php';

/**
 * \class ItemModel
 * \brief Cassandra Model
 */
class ItemModel extends ItemModelBase
{
  /** get random items
   *
   * @param UserDao $userDao
   * @param type $policy
   * @param type $limit
   * @return array of ItemDao 
   */
  function getRandomItems($userDao=null,$policy=0,$limit=10,$thumbnailFilter=false)
    {
    if($userDao==null)
      {
      $userId= -1;
      }
    else if(!$userDao instanceof UserDao)
      {
      throw new Zend_Exception("Should be an user.");
      }
    else
      {
      $userId = $userDao->getUserId();
      }
      
    /*  
    if(Zend_Registry::get('configDatabase')->database->adapter=='PDO_MYSQL')
      {
      $rand='RAND()';
      }
    else
      {
      $rand='random()';
      }
    $sql=$this->database->select()
        ->setIntegrityCheck(false)
        ->from(array('i' => 'item'))
        ->join(array('tt'=> $this->database->select()
                    ->from(array('i' => 'item'),array('maxid'=>'MAX(item_id)'))
                    ),        
            ' i.item_id >= FLOOR(tt.maxid*'.$rand.')')
        ->joinLeft(array('ip' => 'itempolicyuser'),'
                  i.item_id = ip.item_id AND '.$this->database->getDB()->quoteInto('ip.policy >= ?', $policy).'
                     AND '.$this->database->getDB()->quoteInto('user_id = ? ',$userId).' ',array('userpolicy'=>'ip.policy'))
        ->joinLeft(array('ipg' => 'itempolicygroup'),'
                        i.item_id = ipg.item_id AND '.$this->database->getDB()->quoteInto('ipg.policy >= ?', $policy).'
                           AND ( '.$this->database->getDB()->quoteInto('group_id = ? ',MIDAS_GROUP_ANONYMOUS_KEY).' OR
                                group_id IN (' .new Zend_Db_Expr(
                                $this->database->select()
                                     ->setIntegrityCheck(false)
                                     ->from(array('u2g' => 'user2group'),
                                            array('group_id'))
                                     ->where('u2g.user_id = ?' , $userId)
                                     ) .'))' ,array('grouppolicy'=>'ipg.policy'))
        ->where(
         '(
          ip.item_id is not null or
          ipg.item_id is not null)'
          )
        ->limit($limit)
        ;
    if($thumbnailFilter)
      {
      $sql->where('thumbnail != ?','');
      }

    $rowset = $this->database->fetchAll($sql);
    */
    $rowsetAnalysed=array();
    /*
    foreach ($rowset as $keyRow=>$row)
      {
      if($row['userpolicy']==null)$row['userpolicy']=0;
      if($row['grouppolicy']==null)$row['grouppolicy']=0;
      if(!isset($rowsetAnalysed[$row['item_id']])||($rowsetAnalysed[$row['item_id']]->policy<$row['userpolicy']&&$rowsetAnalysed[$row['item_id']]->policy<$row['grouppolicy']))
        {
        $tmpDao= $this->initDao('Item', $row);
        if($row['userpolicy']>=$row['grouppolicy'])
          {
          $tmpDao->policy=$row['userpolicy'];
          }
        else
          {
          $tmpDao->policy=$row['grouppolicy'];
          }
        $rowsetAnalysed[$row['item_id']] = $tmpDao;
        unset($tmpDao);
        }
      }
    */  
    return $rowsetAnalysed;
    }//end get random
}
?>