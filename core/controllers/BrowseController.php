<?php

/**
 *  AJAX request for the admin Controller
 */
class BrowseController extends AppController
{
  public $_models=array('Folder','User','Community','Folder','Item');
  public $_daos=array('Folder','User','Community','Folder','Item');
  public $_components=array('Date','Utility','Sortdao');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = 'browse'; // set the active menu
    session_write_close();
    }  // end init()

  /** Index Action*/
  public function indexAction()
    {
    $communities=array();
    $items=array();
    $header="";

    if($this->logged&&$this->userSession->Dao->isAdmin())
      {
      $communities=$this->Community->getAll();
      }
    else
      {
      $communities=$this->User->getUserCommunities($this->userSession->Dao);
      $communities=array_merge($communities, $this->Community->getPublicCommunities());
      }
    
    $header.="<ul class='pathBrowser'>";
    $header.=" <li class='pathData'><a href='{$this->view->webroot}/browse'>{$this->t('Data')}</a></li>";
    $header.="</ul>";
    
    $this->view->Date=$this->Component->Date;
    
    $this->Component->Sortdao->field='name';
    $this->Component->Sortdao->order='asc';
    usort($communities, array($this->Component->Sortdao,'sortByName'));
    $communities=$this->Component->Sortdao->arrayUniqueDao($communities );
    
    $this->view->communities=$communities;
    $this->view->header=$header;
    
    $this->view->itemThumbnails=$this->Item->getRandomItems($this->userSession->Dao,0,12,true);
    $this->view->nUsers=$this->User->getCountAll();
    $this->view->nCommunities=$this->Community->getCountAll();
    $this->view->nItems=$this->Item->getCountAll();
    $this->view->notifications=array();
    
    $this->view->json['community']['createCommunity']=$this->t('Create a community');
    $this->view->json['community']['titleCreateLogin']=$this->t('Please log in');
    $this->view->json['community']['contentCreateLogin']=$this->t('You need to be logged in to be able to create a community.');
    }

  /** move or copy selected element*/
  public function movecopyAction()
    {
    $copySubmit=$this->_getParam('copyElement');
    $moveSubmit=$this->_getParam('moveElement');
    if(isset($copySubmit)||isset($moveSubmit))
      {
      $elements=explode(';',$this->_getParam('elements'));
      $destination=$this->_getParam('destination');
      $ajax=$this->_getParam('ajax');
      $folderIds=explode('-',$elements[0]);
      $itemIds=explode('-',$elements[1]);
      $folders= $this->Folder->load($folderIds);
      $items= $this->Item->load($itemIds);
      $destination=$this->Folder->load($destination);      
      if(empty($folders)&&empty ($items))
        {
        throw new Zend_Exception("No element selected");
        }
      if($destination==false)
        {
        throw new Zend_Exception("Unable to load destination");
        }
        
      foreach ($folders as $folder)
        {
        //TODO
        if(isset($copySubmit))
          {
          
          }
        else
          {
          
          }
        }
      foreach ($items as $item)
        {
        if(isset($copySubmit))
          {
          $this->Folder->addItem($destination,$item);
          }
        else
          {
          $from=$this->_getParam('from');
          $from=$this->Folder->load($from);  
          if($destination==false)
            {
            throw new Zend_Exception("Unable to load destination");
            }
          $this->Folder->addItem($destination,$item);
          $this->Folder->removeItem($from, $item);
          }
        }
      if(isset($ajax))
        {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        echo JsonComponent::encode(array(true,$this->t('Changes saved')));
        return;
        }
      $this->_redirect ('/folder/'.$destination->getKey());
      }

      
    if(!$this->getRequest()->isXmlHttpRequest())
     {
     throw new Zend_Exception("Why are you here ? Should be ajax.");
     }
    $this->_helper->layout->disableLayout();
    $folderIds=$this->_getParam('folders');
    $itemIds=$this->_getParam('items');
    $move=$this->_getParam('move');
    $this->view->folderIds=$folderIds;
    $this->view->itemIds=$itemIds;
    $this->view->moveEnabled=true;
    if(isset($move))
      {
      $this->view->moveEnabled=false;
      }
    $folderIds=explode('-',$folderIds);
    $itemIds=explode('-',$itemIds);
    $folders= $this->Folder->load($folderIds);
    $items= $this->Item->load($itemIds);
    if(empty($folders)&&empty ($items))
      {
      throw new Zend_Exception("No element selected");
      }
    if(!$this->view->logged)
      {
      throw new Zend_Exception("Should be logged");
      }
    $this->view->folders=$folders;
    $this->view->items=$items;
    
    $communities=$this->User->getUserCommunities($this->userSession->Dao);
    $communities=array_merge($communities, $this->Community->getPublicCommunities());
    $this->view->Date=$this->Component->Date;
    
    $this->Component->Sortdao->field='name';
    $this->Component->Sortdao->order='asc';
    usort($communities, array($this->Component->Sortdao,'sortByName'));
    $communities=$this->Component->Sortdao->arrayUniqueDao($communities );
    
    $this->view->user=$this->userSession->Dao;
    $this->view->communities=$communities;
    }
    
  /** get getfolders content (ajax function for the treetable) */
  public function getfolderscontentAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
     {
     throw new Zend_Exception("Why are you here ? Should be ajax.");
     }

    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folderIds=$this->_getParam('folders');
    if(!isset($folderIds))
     {
     throw new Zend_Exception("Please set the folder Id");
     }
    $folderIds=explode('-',$folderIds);
    $parents= $this->Folder->load($folderIds);
    if(empty($parents))
      {
      throw new Zend_Exception("Folder doesn't exist");
      }
      
    $folders=$this->Folder->getChildrenFoldersFiltered($parents,$this->userSession->Dao,MIDAS_POLICY_READ);
    $items=$this->Folder->getItemsFiltered($parents,$this->userSession->Dao,MIDAS_POLICY_READ);
    $jsonContent=array();
    foreach ($folders as $folder)
      {
      $tmp=array();
      $tmp['folder_id']=$folder->getFolderId();
      $tmp['name']=$folder->getName();
      $tmp['creation']=$this->Component->Date->ago($folder->getDate(),true);
      if($tmp['name']=='Public'||$tmp['name']=='Private')
        {
        $tmp['deletable']='false';
        }
      else
        {
        $tmp['deletable']='true';
        }
      $tmp['policy']=$folder->policy;
      $jsonContent[$folder->getParentId()]['folders'][]=$tmp;
      unset($tmp);
      }
    foreach ($items as $item)
      {
      $tmp=array();
      $tmp['item_id']=$item->getItemId();
      $tmp['name']=$item->getName();
      $tmp['parent_id']=$item->parent_id;
      $tmp['creation']=$this->Component->Date->ago($item->getDate(),true);
      $tmp['size']=$this->Component->Utility->formatSize($item->getSizebytes());
      $tmp['policy']=$item->policy;
      $jsonContent[$item->parent_id]['items'][]=$tmp;
      unset($tmp);
      }
    echo JsonComponent::encode($jsonContent);
    }//end getfolderscontent
    
   /** get getfolders Items' size */
   public function getfolderssizeAction()
    {
  /*  if(!$this->getRequest()->isXmlHttpRequest())
     {
     throw new Zend_Exception("Why are you here ? Should be ajax.");
     }  */  
     
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $folderIds=$this->_getParam('folders');
    if(!isset($folderIds))
     {
     echo "[]";
     return;
     }
    $folderIds=explode('-',$folderIds);
    $folders= $this->Folder->load($folderIds);
    $folders=$this->Folder->getSizeFiltered($folders,$this->userSession->Dao);
    $return=array();
    foreach($folders as $folder)
      {
      $return[]=array('id'=>$folder->getKey(),'count'=>$folder->count,'size'=>$this->Component->Utility->formatSize($folder->size));
      }
    echo JsonComponent::encode($return);
    }//end getfolderscontent

   /** get element info (ajax function for the treetable) */
  public function getelementinfoAction()
    {
    if(!$this->getRequest()->isXmlHttpRequest())
      {
      throw new Zend_Exception("Why are you here ? Should be ajax.");
      }
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();
    $element=$this->_getParam('type');
    $id=$this->_getParam('id');
    if(!isset($id)||!isset($element))
     {
     throw new Zend_Exception("Please double check the parameters");
     }
    $jsonContent=array('type'=>$element);
    switch ($element)
      {
      case 'community':
        $community=$this->Community->load($id);        
        $jsonContent=array_merge($jsonContent,$community->_toArray());
        $jsonContent['creation']=$this->Component->Date->formatDate(strtotime($community->getCreation()));
        $members=$community->getMemberGroup()->getUsers();
        $jsonContent['members']=count($members);
        break;
      case 'folder':
        $folder=$this->Folder->load($id);
        $jsonContent=array_merge($jsonContent,$folder->_toArray());
        $jsonContent['creation']=$this->Component->Date->formatDate(strtotime($jsonContent['date']));
        break;
      case 'item':
        $item=$this->Item->load($id);
        $jsonContent=array_merge($jsonContent,$item->_toArray());
        $itemRevision=$this->Item->getLastRevision($item);
        $jsonContent['creation']=$this->Component->Date->formatDate(strtotime($itemRevision->getDate()));
        $jsonContent['uploaded']=$itemRevision->getUser()->_toArray();
        $jsonContent['revision']=$itemRevision->_toArray();
        $jsonContent['nbitstream']=count($itemRevision->getBitstreams());
        $jsonContent['type']='item';
        break;
      default:
        throw new Zend_Exception("Please select the right type of element.");
        break;
      }
    $jsonContent['translation']['Created']=$this->t('Created');
    $jsonContent['translation']['File']=$this->t('File');
    $jsonContent['translation']['Uploaded']=$this->t('Uploaded by');
    $jsonContent['translation']['Private']=$this->t('This community is private');
    echo JsonComponent::encode($jsonContent);
    }//end getElementInfo


        /** review (browse) uploaded files*/
    public function uploadedAction()
      {
      if(empty($this->userSession->uploaded)||!$this->logged)
        {
        $this->_redirect('/');
        }
      $this->view->items=array();
      foreach($this->userSession->uploaded as $item)
        {
        $this->view->items[]=$this->Item->load($item);
        }
      }
} // end class

