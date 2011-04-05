<?php
class InternationalizationComponent extends AppComponent
{ 
    static private $_instance = null;
    private function __construct() {
    }
    static public function getInstance() {
        if (!self::$_instance instanceof self) {
           self::$_instance = new self();
        }
        return self::$_instance;
    }
 
  /** translate*/
  static public function translate($text)
     {
    if (Zend_Registry::get('configGlobal')->application->lang=='fr')
      {
      $translate=Zend_Registry::get('translater');
      $new_text=$translate->_($text);
      if ($new_text==$text)
        {
        $translaters=Zend_Registry::get('translatersModules');
        foreach ($translaters as $t)
          {
          $new_text=$t->_($text);
          if($new_text!=$text)
            {
            break;
            }
          }
        }
      return $new_text;
      }
    return $text;
    } //end method t
    
       /**
   * @method public  isDebug()
   * Is Debug mode ON
   * @return boolean
   */
  static public function isDebug()
    {
    $config = Zend_Registry::get('config');
    if ($config->mode->debug == 1)
      {
      return true;
      }
    else
      {
      return false;
      }
    }
} // end class
?>