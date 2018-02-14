<?php
namespace ITE\lang;

/**
 * Class that manages language layers
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */

use \ITE\serviceProviderInterface;

class serviceProvider implements serviceProviderInterface{
    
    public static function init(\ITE\ite $instance) {
        return new class($instance)
        { 
            private $container;
            
            public function __construct($container) {
                $this->container = $container;
            }
            
            public function __call($name, $params) {
                global $_ITE;
                $_ITE->lang = \ITE\lang\serviceProvider::loadLanguage(LANG_ENGINE,$this->container);
                //return $_ITE->lang->$name($params);
                return call_user_func_array(array($_ITE->lang,$name),$params);
            }
        };
    }
    
    public static function loadLanguage(string $file_type,$container){
        $lang_adapter = new langAdapter();
        return $lang_adapter->getLoader($file_type,$container);
    }
}