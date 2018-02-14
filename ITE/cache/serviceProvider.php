<?php
namespace ITE\cache;

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
            
            public function __construct($container) {
                $this->container = $container;
            }
            
            public function __call($name, $params) {
                global $_ITE;
                $_ITE->cache = new \ITE\cache\cache($this->container);
                return call_user_func_array(array($_ITE->cache, $name), $params);
            }
        };
    }

}
