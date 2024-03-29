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

/** Item Controller */
class ItemController extends AppController
  {
  public $_models = array('Item', 'ItemRevision', 'Bitstream', 'Folder', 'Metadata');
  public $_daos = array();
  public $_components = array('Date', 'Utility', 'Sortdao');
  public $_forms = array('Item');

  /** Init Controller */
  function init()
    {
    $this->view->activemenu = ''; // set the active menu
    $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
    if(isset($actionName) && (is_numeric($actionName) || strlen($actionName) == 32)) // This is tricky! and for Cassandra for now
      {
      $this->_forward('view', null, null, array('itemId' => $actionName));
      }
    }  // end init()


  /** create/edit metadata*/
  function editmetadataAction()
    {
    $this->disableLayout();
    if(!$this->logged)
      {
      throw new Zend_Exception("You have to be logged in to do that");
      }

    $itemId = $this->_getParam("itemId");
    $metadataId = $this->_getParam("metadataId");
    $itemDao = $this->Item->load($itemId);
    if($itemDao === false)
      {
      throw new Zend_Exception("This item doesn't exist.");
      }
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Problem policies.");
      }
    $itemRevision = $this->Item->getLastRevision($itemDao);
    $metadatavalues = $this->ItemRevision->getMetadata($itemRevision);
    $this->view->metadata = null;

    foreach($metadatavalues as $value)
      {
      if($value->getMetadataId() == $metadataId)
        {
        $this->view->metadata = $value;
        break;
        }
      }

    $this->view->itemDao = $itemDao;
    $this->view->metadataType = $this->Metadata->getAllMetadata();
    $this->view->metadataType = $this->view->metadataType['sorted'];
    $this->view->jsonMetadataType = JsonComponent::encode($this->view->metadataType);
    }

  /** view a community*/
  function viewAction()
    {
    $this->view->header = $this->t("Item");
    $this->view->Date = $this->Component->Date;
    $this->view->Utility = $this->Component->Utility;
    $itemId = $this->_getParam("itemId");
    if(!isset($itemId) || !is_numeric($itemId))
      {
      throw new Zend_Exception("itemId  should be a number");
      }
    $itemDao = $this->Item->load($itemId);
    if($itemDao === false)
      {
      throw new Zend_Exception("This item doesn't exist.");
      }
    if(!$this->Item->policyCheck($itemDao, $this->userSession->Dao))
      {
      throw new Zend_Exception("Problem policies.");
      }

    $this->view->isAdmin = $this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN);
    $this->view->isModerator = $this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_WRITE);
    $itemRevision = $this->Item->getLastRevision($itemDao);
    if($this->_request->isPost())
      {
      $deleteMetadata = $this->_getParam('deleteMetadata');
      $editMetadata = $this->_getParam('editMetadata');
      if(isset($deleteMetadata) && !empty($deleteMetadata) && $this->view->isModerator) //remove users from group
        {
        $this->disableView();
        $this->disableLayout();
        $metadataId = $this->_getParam('element');
        $this->ItemRevision->deleteMetadata($itemRevision, $metadataId);
        echo JsonComponent::encode(array(true, $this->t('Changes saved')));
        return;
        }
      if(isset($editMetadata) && !empty($editMetadata) && $this->view->isModerator) //remove users from group
        {
        $metadataId = $this->_getParam('metadataId');
        $metadatatype = $this->_getParam('metadatatype');
        $element = $this->_getParam('element');
        $qualifier = $this->_getParam('qualifier');
        $value = $this->_getParam('value');
        if(isset($metadataId) && !empty($metadataIds))
          {
          $this->ItemRevision->deleteMetadata($itemRevision, $metadataId);
          }
        $metadataDao = $this->Metadata->getMetadata($metadatatype, $element, $qualifier);
        if($metadataDao == false)
          {
          $this->Metadata->addMetadata($metadatatype, $element, $qualifier, '');
          }
        $this->Metadata->addMetadataValue($itemRevision, $metadatatype, $element, $qualifier, $value);
        }
      }

    if($this->logged)
      {
      $request = $this->getRequest();
      $cookieData = $request->getCookie('recentItems'.$this->userSession->Dao->getKey());
      $recentItems = array();
      if(isset($cookieData))
        {
        $recentItems = unserialize($cookieData);
        }
      $tmp = array_reverse($recentItems);
      $i = 0;
      foreach($tmp as $key => $t)
        {
        if($t == $itemDao->getKey() || !is_numeric($t))
          {
          unset($tmp[$key]);
          continue;
          }
        $i++;
        if($i > 4)
          {
          unset($tmp[$key]);
          }
        }
      $recentItems = array_reverse($tmp);
      $recentItems[] = $itemDao->getKey();

      setcookie('recentItems'.$this->userSession->Dao->getKey(), serialize($recentItems), time() + 60 * 60 * 24 * 30, '/'); //30 days
      }

    $this->Item->incrementViewCount($itemDao);
    $itemDao->lastrevision = $itemRevision;
    $itemDao->revisions = $itemDao->getRevisions();

    // Display the good link if the item is pointing to a website
    $this->view->itemIsLink = false;

    $bitstreams = $itemRevision->getBitstreams();
    if(count($bitstreams) == 1)
      {
      $bitstream = $bitstreams[0];
      if(strpos($bitstream->getPath(), 'http://') !== false)
        {
        $this->view->itemIsLink = true;
        }
      }


    $this->Component->Sortdao->field = 'revision';
    $this->Component->Sortdao->order = 'desc';
    usort($itemDao->revisions, array($this->Component->Sortdao, 'sortByNumber'));

    $itemDao->creation = $this->Component->Date->formatDate(strtotime($itemRevision->getDate()));
    $this->view->itemDao = $itemDao;

    $this->view->itemSize = $this->Component->Utility->formatSize($itemDao->getSizebytes());

    $this->view->title .= ' - '.$itemDao->getName();
    $this->view->metaDescription = substr($itemDao->getDescription(), 0, 160);

    $this->view->metadatavalues = $this->ItemRevision->getMetadata($itemRevision);


    $tmp = Zend_Registry::get('notifier')->callback("CALLBACK_VISUALIZE_CAN_VISUALIZE", array('item' => $itemDao));
    if(isset($tmp['visualize']) && $tmp['visualize'] == true)
      {
      $this->view->preview = true;
      }
    else
      {
      $this->view->preview = false;
      }

    $items = array();
    $this->view->backUploaded = false;
    $this->view->currentFolder = false;
    if(isset($this->userSession->uploaded) && in_array($itemDao->getKey(), $this->userSession->uploaded))
      {
      $this->view->backUploaded = true;
      $items = $this->Item->load($this->userSession->uploaded);
      }
    else
      {
      $parents = $itemDao->getFolders();
      if(isset($this->userSession->recentFolders))
        {
        foreach($parents as $p)
          {
          if(in_array($p->getKey(), $this->userSession->recentFolders))
            {
            $currentFolder = $p;
            break;
            }
          }
        if(isset($currentFolder))
          {
          $items = $this->Folder->getItemsFiltered($currentFolder, $this->userSession->Dao, MIDAS_POLICY_READ);
          $this->view->currentFolder = $currentFolder;
          }
        }
      }

    foreach($items as $key => $item)
      {
      $tmp = Zend_Registry::get('notifier')->callback("CALLBACK_VISUALIZE_CAN_VISUALIZE", array('item' => $item));
      if(isset($tmp['visualize']) && $tmp['visualize'] == true)
        {
        $items[$key]->preview = 'true';
        }
      else
        {
        $items[$key]->preview = 'false';
        }
      }

    $this->view->sameLocation = $items;

    $this->view->json['item'] = $itemDao->toArray();
    $this->view->json['item']['message']['delete'] = $this->t('Delete');
    $this->view->json['item']['message']['deleteMessage'] = $this->t('Do you really want to delete this item? It cannot be undo.');
    $this->view->json['item']['message']['deleteMetadataMessage'] = $this->t('Do you really want to delete this metadata? It cannot be undo.');
    $this->view->json['item']['message']['movecopy'] = $this->t('Copy Item.');
    }//end index

  /** Edit  (ajax) */
  function editAction()
    {
    $this->disableLayout();
    $item_id = $this->_getParam('itemId');
    $item = $this->Item->load($item_id);
    if(!isset($item_id))
      {
      throw new Zend_Exception("Please set the itemId.");
      }
    elseif($item === false)
      {
      throw new Zend_Exception("The item doesn t exist.");
      }
    elseif(!$this->Item->policyCheck($item, $this->userSession->Dao, MIDAS_POLICY_WRITE))
      {
      throw new Zend_Exception("Permissions error.");
      }

    if($this->_request->isPost())
      {
      $name = $this->_getParam('name');
      $description = $this->_getParam('description');

      if(strlen($name) > 0)
        {
        $item->setName($name);
        }
      $item->setDescription($description);

      $this->Item->save($item);
      $this->_redirect('/item/'.$item->getKey());
      }

    $this->view->itemDao = $item;
    $form = $this->Form->Item->createEditForm();
    $formArray = $this->getFormAsArray($form);
    $formArray['name']->setValue($item->getName());
    $formArray['description']->setValue($item->getDescription());
    $this->view->form = $formArray;
    }

  /** Delete an item*/
  function deleteAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $itemId = $this->_getParam("itemId");
    if(!isset($itemId) || (!is_numeric($itemId) && strlen($itemId) != 32)) // This is tricky! and for Cassandra for now
      {
      throw new Zend_Exception("itemId should be a number");
      }
    $itemDao = $this->Item->load($itemId);
    if($itemDao === false || !$this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
      {
      throw new Zend_Exception("This community doesn't exist or you don't have the permissions.");
      }

    $this->Item->delete($itemDao);

    $this->_redirect('/?checkRecentItem = true');
    }//end delete


  /** Merge items*/
  function mergeAction()
    {
    $this->_helper->layout->disableLayout();
    $this->_helper->viewRenderer->setNoRender();

    $itemIds = $this->_getParam("items");
    $name = $this->_getParam("name");
    if(empty($name))
      {
      throw new Zend_Exception('Please set a name');
      }
    $itemIds = explode('-', $itemIds);

    $items = array();
    foreach($itemIds as $item)
      {
      $itemDao = $this->Item->load($item);
      if($itemDao != false && $this->Item->policyCheck($itemDao, $this->userSession->Dao, MIDAS_POLICY_ADMIN))
        {
        $items[] = $itemDao;
        }
      }

    if(empty($items))
      {
      throw new Zend_Exception('Permissions error');
      }


    $mainItem = $items[0];
    $mainItemLastResision = $this->Item->getLastRevision($mainItem);
    foreach($items as $key => $item)
      {
      if($key != 0)
        {
        $revision = $this->Item->getLastRevision($item);
        $bitstreams = $revision->getBitstreams();
        foreach($bitstreams as $b)
          {
          $b->setItemrevisionId($mainItemLastResision->getKey());
          $this->Bitstream->save($b);
          }
        $this->Item->delete($item);
        }
      }

    $mainItem->setSizebytes($this->ItemRevision->getSize($mainItemLastResision));
    $mainItem->setName($name);
    $this->Item->save($mainItem);

    $this->_redirect('/browse/uploaded');
    }//end delete

  }//end class

  /*    pour la r�cup�rer
     */