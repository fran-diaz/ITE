<?php
namespace ITE\auth;

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
                $_ITE->auth = new \ITE\auth\auth($this->container);
                if(is_array($this->override_classes)){
                    foreach ($this->override_classes as $o){
                        $_ITE->auth = new $o($this->container);
                    }
                }
                return call_user_func_array(array($_ITE->auth, $name), $params);
            }
            
            public function setOverrideClass($class_name){
                $this->override_classes[] = $class_name;
                return true;
            }
        };
    }
}