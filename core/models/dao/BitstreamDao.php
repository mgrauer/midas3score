<?php
/**
 * \class ItemDao
 * \brief DAO Bitstream (table bitstream)
 */
class BitstreamDao extends AppDao
{
  public $_model='Bitstream';
  public $_components=array('MimeType','Utility');
  
  /** Fill the properties of the bitstream given the path.
   *  The file should be accessible from the web server.
   */
  function fillPropertiesFromPath()
    {
    // Check if the path exists
    if(!isset($this->path) || empty($this->path))
      {
      throw new Zend_Exception("BitstreamDao path is not set in fillPropertiesFromPath()"); 
      } 
       
    // TODO Compute the full path from the assetstore. For now using the path  
    $this->setMimetype($this->Component->MimeType->getType($this->path));
    $this->setSizebytes(filesize($this->path));
    $this->setChecksum(UtilityComponent::md5file($this->path));
    } // end fillPropertiesFromPath()
  
  /** Returns the full path of the bitstream based on the assetstore and the path of the bitstream */  
  function getFullPath()
    {
    $assetstore = $this->get('assetstore');
    return $assetstore->getPath()."/".$this->getPath();
    } // end function getFullPath()
  
  
} // end class  
?>