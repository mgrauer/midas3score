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



/**
 * ItemMetricComponent tests
 */
class ItemMetricComponentTest extends ControllerTestCase
  {

  protected $itemMetricComponent;
  protected $applicationConfig;

  /** constructor */
  public function __construct()
    {
    // need to include the module constant for this test
    require_once BASE_PATH.'/modules/batchmake/constant/module.php';
    require_once BASE_PATH.'/modules/batchmake/controllers/components/ItemMetricComponent.php';
    $this->itemMetricComponent = new Batchmake_ItemMetricComponent(BASE_PATH.'/modules/batchmake/tests/configs/module.local.ini');
    }

  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default'));
    $this->enabledModules = array('batchmake');
    $this->_models = array('User', 'Item', 'Bitstream');
    $this->_components = array('Export', 'Upload');
    parent::setUp();
    }

    /** set up tests* /
  public function setUp()
    {
    $this->_models = array('User');
    parent::setUp();
    }*/

  /**
   * @TODO remove this test
   */
  public function testTrivial()
    {
    $this->assertTrue($this->itemMetricComponent != null);
    }


  /**
   * tests the getMetrics function
   */
  public function testGetMetrics()
    {
    $this->setupDatabase(array('default'), 'batchmake'); // module dataset
    echo "TESTGETMETRICS:";
    $idsToNames = $this->itemMetricComponent->getMetrics();
    var_dump($idsToNames);
    /*    foreach($itemmetricDaos as $dao)
      {
      $idsToNames[$itemmetricDaos->getItemmetricId()] = $itemmetricDaos->getMetricName();
      }



//@TODO is returning a dao the right thing to do?
    return $idsToNames;

    $this->assertTrue($this->itemMetricComponent->getMetrics());
//- getMetrics() : will return a list of metricIds and corresponding names
*/

    $this->assertFalse(true);
    }

  /**
   * tests the addMetrics function
   */
  public function xtestAddMetrics()
    {
    $itemId = 0;
    $name = 'NewMetric';
    $metricId = $this->itemMetricComponent->addMetrics($itemId, $name);

//echo "new metric id: ".$metricId;

    $this->assertFalse(true);
    }
//- addMetric(itemId, name) : will take in a reference to an item, which should have 3 bitstreams in it, an exe, a bmm, and a bms. it will then take the 3 files and set them up so that they can be used as a metric. returns a metricId.


  /**
   * tests the compareItems function
   */
  public function testCompareItems()
    {

      
      
      
      
      
      
      
      
    //HACK login a user
    $usersFile = $this->loadData('User', 'default', '', 'batchmake');
    $modelLoad = new MIDAS_ModelLoader();
    $userDao = $usersFile[0];

     
    
    list($item1, $item2) = $this->uploadItems($userDao);

    
    
    
   /* Zend_Session::start();
    $user = new Zend_Session_Namespace('Auth_User');
    $user->setExpirationSeconds(60 * Zend_Registry::get('configGlobal')->session->lifetime);
    $user->Dao = $userDao;
    $user->lock();  
    Zend_Registry::set('userSession', $user);      
     */ 
      
      
//    $itemId1 = 0;
//    $itemId2 = 1;
    $metricId = 4;
    $this->assertTrue($this->itemMetricComponent->compareItems($userDao, $item1->getItemId(), $item2->getItemId(), $metricId));
//    
//    
//    
//    
//    - compareItems(itemId_1, itemId_2, metricId) : will export item1 and item2, and run a batchmake exe reference by metricId on those two items, storing the value(s) in some database, returning the comparisonId
    }







    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
      /**
   * Helper function to recursively delete a directory
   *
   * @param type $directorypath Directory to be deleted
   * @return bool Success or not
   */
  private function _recursiveRemoveDirectory($directorypath)
    {
    // if the path has a slash at the end, remove it here
    $directorypath = rtrim($directorypath, '/');

    $handle = opendir($directorypath);
    if(!is_readable($directorypath))
      {
      return false;
      }
    while(false !== ($item = readdir($handle)))
      {
      if($item != '.' && $item != '..')
        {
        $path = $directorypath.'/'.$item;
        if(is_dir($path))
          {
          $this->_recursiveRemoveDirectory($path);
          }
        else
          {
          unlink($path);
          }
        }
      }
    closedir($handle);
    // try to delete the now empty directory
    if(!rmdir($directorypath))
      {
      return false;
      }
    return true;
    }


  /**
   * Helper function to upload items
   * @TODO delete tmp dir at the end
   * @param type $userDao User who will upload items
   */
  public function uploadItems($userDao)
    {
    // use UploadComponent
    require_once BASE_PATH.'/core/controllers/components/UploadComponent.php';
    $uploadComponent = new UploadComponent();
    // notifier is required in ItemRevisionModelBase::addBitstream, create a fake one
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    // create a directory for testing the export component
    $midas_exporttest_dir = BASE_PATH.'/tmp/exportTest';
    if(file_exists($midas_exporttest_dir))
      {
      if(!$this->_recursiveRemoveDirectory($midas_exporttest_dir))
        {
        throw new Zend_Exception($midas_exporttest_dir." has already existed and we cannot delete it.");
        }
      }
    if(!mkdir($midas_exporttest_dir))
      {
      throw new Zend_Exception("Cannot create directory: ".$midas_exporttest_dir);
      }
    chmod($midas_exporttest_dir, 0777);

    // create an image
    $im = imagecreatetruecolor(100, 100);
    $white = imagecolorallocate($im, 255, 255, 255);
    //$pink = imagecolorallocate($im, 255, 105, 180);
    imagefilledrectangle($im, 10, 10, 60, 60, $white);
    //imagerectangle($im, 20, 20, 70, 70, $pink);
    $filename1 = 'img1.png';
    $path1 = $midas_exporttest_dir.'/'.$filename1;
    imagepng($im, $path1);
    imagedestroy($im);

    $img1Size = filesize($path1);
    $user1PublicParent = $userDao->getPublicFolder()->getKey();
    $license = 0;
    $item1 = $uploadComponent->createUploadedItem($userDao, $filename1,
                                          $path1, $user1PublicParent, $license);

    
    
    $im = imagecreatetruecolor(100, 100);
    $white = imagecolorallocate($im, 255, 255, 255);
    //$pink = imagecolorallocate($im, 255, 105, 180);
    imagefilledrectangle($im, 10, 10, 90, 90, $white);
    //imagerectangle($im, 20, 20, 70, 70, $pink);
    $filename2 = 'img1.png';
    $path2 = $midas_exporttest_dir.'/'.$filename2;
    imagepng($im, $path2);
    imagedestroy($im);

    
    $img2Size = filesize($path2);
    $license = 0;
    $item2 = $uploadComponent->createUploadedItem($userDao, $filename2,
                                          $path2, $user1PublicParent, $license);
    
    
    return array($item1, $item2);
    
    
/*    // upload an item to user1's public folder
    $user1_public_path = $midas_exporttest_dir.'/user1_public.png';
    copy(BASE_PATH.'/tests/testfiles/search.png', $user1_public_path);
    $user1_public_fh = fopen($user1_public_path, "a+");
    fwrite($user1_public_fh, "content:user1_public");
    fclose($user1_public_fh);
    $user1_pulic_file_size = filesize($user1_public_path);
    $user1_public_filename = 'user1_public.png';
    $user1_public_parent = $userDao->getPublicFolder()->getKey();
    $license = 0;
    $uploadCompoenent->createUploadedItem($userDao, $user1_public_filename,
                                          $user1_public_path, $user1_public_parent, $license);

    // upload an item to user1's private folder
    $user1_private_path = $midas_exporttest_dir.'/user1_private.png';
    copy(BASE_PATH.'/tests/testfiles/search.png', $user1_private_path);
    $user1_private_fh = fopen($user1_private_path, "a+");
    fwrite($user1_private_fh, "content:user1_private");
    fclose($user1_private_fh);
    $user1_pulic_file_size = filesize($user1_private_path);
    $user1_private_filename = 'user1_private.png';
    $user1_private_parent = $userDao->getPrivateFolder()->getKey();
    $license = 0;
    $uploadCompoenent->createUploadedItem($userDao, $user1_private_filename,
                                          $user1_private_path, $user1_private_parent, $license);
 * 
 */
    }








  } // end class

