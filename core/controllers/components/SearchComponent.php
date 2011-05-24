<?php
/** Saerch */
class SearchComponent extends AppComponent
{  
  /** search all the results */
  public function searchAll($userDao, $search, $order)
    {
    $modelLoad = new MIDAS_ModelLoader();
    $itemModel = $modelLoad->loadModel('ItemKeyword');
    $folderModel = $modelLoad->loadModel('Folder');
    $communityModel = $modelLoad->loadModel('Community');
    $userModel = $modelLoad->loadModel('User');
    
    $ItemsDao = $itemModel->getItemsFromSearch($search, $userDao, 200, false, $order);
    
    // Search for the folders
    $FoldersDao = $folderModel->getFoldersFromSearch($search, $userDao, 15, false, $order); 
     
    // Search for the communities
    $CommunitiesDao = $communityModel->getCommunitiesFromSearch($search, $userDao, 15, false, $order); 
    
    // Search for the users
    $UsersDao = $userModel->getUsersFromSearch($search, $userDao, 15, false, $order); 
    
    $return = array();
    
    $return['nitems'] = count($ItemsDao);
    $return['nfolders'] = count($FoldersDao);
    $return['ncommunities'] = count($CommunitiesDao);
    $return['nusers'] = count($UsersDao);
    $return['results'] = $this->_formatResults($order, $ItemsDao, $FoldersDao, $CommunitiesDao, $UsersDao);
    
    return $return;
    }
      
  /** 
   * Format search results
   * @param string $order
   * @param Array $items
   * @param Array $folders
   * @param Array $communities
   * @param Array $users
   * @return Array 
   */
  private function _formatResults($order, $items, $folders, $communities, $users)
    {
    foreach($users as $key => $user)
      {
      $users[$key]->name = $user->getLastname();
      $users[$key]->date = $user->getCreation();
      }
    foreach($communities as $key => $community)
      {
      $communities[$key]->date = $community->getCreation();
      }
    $results = array_merge($folders, $items, $communities, $users);
    
    Zend_Loader::loadClass('SortdaoComponent', BASE_PATH . '/core/controllers/components');
    Zend_Loader::loadClass('DateComponent', BASE_PATH . '/core/controllers/components');
    
    $sortdaoComponent = new SortdaoComponent();
    $dateComponent = new DateComponent();
      
    switch($order)
      {
      case 'name':
        $sortdaoComponent->field = 'name';
        $sortdaoComponent->order = 'asc';
        usort($results, array($sortdaoComponent, 'sortByName'));
        break;
      case 'date':
        $sortdaoComponent->field = 'date';
        $sortdaoComponent->order = 'asc';
        usort($results, array($sortdaoComponent, 'sortByDate'));
        break;
      case 'view':
        $sortdaoComponent->field = 'view';
        $sortdaoComponent->order = 'desc';
        usort($results, array($sortdaoComponent, 'sortByNumber'));
        break;
      default:
        throw new Zend_Exception('Error order parameter');
        break;
      }
    $resultsArray = array();
    foreach($results as $result)
      {
      $tmp = $result->toArray();
      if($result instanceof UserDao)
        {
        $tmp['resultType'] = 'user';
        $tmp['formattedDate'] = $dateComponent->formatDate($result->getCreation());
        }
      if($result instanceof ItemDao)
        {
        $tmp['resultType'] = 'item';
        $tmp['formattedDate'] = $dateComponent->formatDate($result->getDate());
        }
      if($result instanceof CommunityDao)
        {
        $tmp['resultType'] = 'community';
        $tmp['formattedDate'] = $dateComponent->formatDate($result->getCreation());
        }
      if($result instanceof FolderDao)
        {
        $tmp['resultType'] = 'folder';
        $tmp['formattedDate'] = $dateComponent->formatDate($result->getDate());
        }
      unset($tmp['password']);
      unset($tmp['email']);
      $resultsArray[] = $tmp;
      }
    return $resultsArray;
    }//formatResults
} // end class UploadComponent
?>