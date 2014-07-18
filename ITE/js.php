<?php
namespace ITE;

/**
 * Assistance class with some javascript functions needed by other plugins
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */
class js {
    public $container;
    
    public function __construct($container) {
        $this->container = $container;
    }
    
    /**
     * Function that generates HTML code to instantiate clock plugin
     * 
     * @return string Clock HTML code
     */
    public static function generate_clock(){
        $now = getdate();
        return '<span class="clock"><span class="h">'.$now['hours'].'</span>:<span class="m">'.$now['minutes'].'</span>:<span class="s">'.$now['seconds'].'</span></span>';
    }
    
    /**
     * Function to instantiate clock defining the var in javascript layer
     * 
     * @return string Javascript code
     */
    public static function instantiate_clock(){
        return 'var t'.rand(1000, 2000).'=setInterval("clock()",1000);';
    }
}
?>