<?php
namespace ITE\db;

/**
 * Description of cacheServiceProvider
 *
 * @author Fran Díaz <fran.diaz.gonzalez@gmail.com>
 */

use \ITE\serviceProviderInterface;

class serviceProvider implements serviceProviderInterface{

    public static function init(\ITE\ite $container,string $controller = null) {
        return new class($container,$controller)
        { 
            private $container;
            private $controller;
            
            public function __construct($container,$controller) {
                $this->container = $container;
                $this->controller = $controller;
            }
            
            public function __call($name, $params) {
                global $_ITE;
                $_ITE->bdd = new $this->controller($this->container);
                return call_user_func_array(array($_ITE->bdd, $name), $params);
            }
        };
    }
}
