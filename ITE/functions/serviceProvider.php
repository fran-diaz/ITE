<?php
namespace ITE\functions;

/**
 * Description of cacheServiceProvider
 *
 * @author Fran Díaz <fran.diaz.gonzalez@gmail.com>
 */

use \ITE\serviceProviderInterface;

class serviceProvider implements serviceProviderInterface{

    public static function init(\ITE\ite $instance) {
        return new class($instance)
        { 
            private $container;
            public $override_classes;
            
            public function __construct($container) {
                $this->container = $container;
            }
            
            public function __call($name, $params) {
                global $_ITE;
                $_ITE->funcs = new \ITE\functions\functions($this->container);
                if(is_array($this->override_classes)){
                    foreach ($this->override_classes as $o){
                        $_ITE->funcs = new $o($this->container);
                    }
                }
                return call_user_func_array(array($_ITE->funcs,$name),$params);
            }
            
            public function setOverrideClass($class_name){
                $this->override_classes[] = $class_name;
                return true;
            }
        };
    }
}
