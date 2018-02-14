<?php
namespace ITE\lang;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of adapter
 *
 * @author Fran Díaz <fran.diaz.gonzalez@gmail.com>
 */
class langAdapter {
    public function getLoader(string $type,$container){
        $class_name = '\ITE\lang\loaders\\'.$type;
        if($class_name){
            $loader = new $class_name($container);
            return $loader;
        }else{
            throw new exception("Clase '\ITE\lang\loader\\$type' no entontrada.");
        }
        
    }
}
