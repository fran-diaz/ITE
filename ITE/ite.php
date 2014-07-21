<?php
namespace ITE;
/**
 * ***** BEGIN LICENSE BLOCK *****
 *  
 *  The MIT License (MIT)
 *
 *  Copyright (c) [year] [fullname]
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE.
 * 
 * ***** END LICENSE BLOCK *****
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @version     4.5b
 * @package     ITE
 * @access      public
 * 
 */

/**
 * Main class to provide MVC Controller functionality. Based on Singleton pattern.
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @version     4.5b
 * @package     ITE
 * @access      public
 * 
 */
class ite{
    /**
     * @var \ITE\ite
     */
    private static $instance;
    /**
     * @var \ITE\functions 
     */
    public $funcs;
    /**
     * @var \ITE\files 
     */
    public $files;
    /**
     * @var \ITE\cache 
     */
    public $cache;
    /**
     * @var \ITE\lang
     */
    public $lang;
    /**
     * @var \ITE\mysql
     */
    public $bdd;
    /**
     * @var \ITE\css
     */
    public $css;
    /**
     * @var \ITE\js
     */
    public $js;
    /**
     * @var \ITE\FirePHP
     */
    public $debug;
    
    private function __construct() {       
        spl_autoload_register(function($class){
            //global $_ITE;
            //$_ITE->files->autoload(CACHE_PATH,$class);
            
            //$filename = str_replace('\\','/',CACHE_PATH .$class.'.php');
            $pos = strrpos($class, '\\');
            $class_file = substr($class,$pos+1);
            $class_namespace = substr($class,0,$pos);
            $temp = explode('\\',$class_namespace);
            array_walk($temp, function(&$element,$index){$element = md5($element);});
            $temp = implode('\\',$temp);
            $filename = str_replace('\\','/',CACHE_PATH. $class_namespace.DIRECTORY_SEPARATOR.$class_file.'.php');
            if(file_exists($filename)){
                require_once($filename);
            }elseif(file_exists(str_replace('\\','/',CACHE_PATH. $temp.DIRECTORY_SEPARATOR.md5($class_file.'.php')))){
                $filename = str_replace('\\','/',CACHE_PATH. $temp.DIRECTORY_SEPARATOR.md5($class_file.'.php'));
                require_once($filename);
            }else{
                var_dump(str_replace('\\','/',CACHE_PATH. $temp.DIRECTORY_SEPARATOR.md5($class_file.'.php')));
                $this->__error("Imposible incluir la librería, archivo no encontrado: ".$filename);
            }
        });

        $this->debug=($this->__debug())?new FirePHP():false;
    }
    
    /**
     * Constructor class
     * 
     * @return ite
     */
    public static function singleton() {
        
        if (session_id() == "") {
            session_start();
        } if (self::__debug()) {
            ini_set("display_errors", "1");
        } if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
            self::$instance->cache = new cache(self::$instance);
            self::$instance->lang = new lang(self::$instance);
            self::$instance->files = new files(self::$instance);
            self::$instance->funcs = new functions(self::$instance);
            self::$instance->css = new css(self::$instance);
            self::$instance->js = new js(self::$instance);
            
            self::$instance->__cache();
            self::set_db_controller();
        } return self::$instance;
    }
    
    /**
     * Function that sets intantiates database library core
     * 
     * @param string $type Default DB library core
     */
    public static function set_db_controller($type = "mysql") {
        //$aux_lib = (file_exists(__DIR__.DIRECTORY_SEPARATOR.md5($type.".class.php"))) ? md5($type.".class.php") : $type.'.class.php';
        //require($aux_lib);
        $type = '\ITE\\'.$type;
        self::$instance->bdd = new $type(self::$instance);
    }

    /**
     * Checks cache files and retrieves updated files from library 
     * 
     * @return boolean Returns none if success or false if fails.
     */
    public function __cache(){
        if(UPDATE_CACHE == true){
            if(!defined("LOCATION") || !defined("CODE") || !defined("LIBRARY") || !defined("CACHE_PATH")){
                $this->__error("Imposible recuperar el archivo remoto: falta alguna constante por definir");
                return false;
            }
            
            if(!is_writable(CACHE_PATH)){
                $this->__error("Imposible recuperar el archivo remoto: la carpeta de cache no tiene permisos de escritura.");
                return false;
            }

            $cnn = ftp_connect(LOCATION);
            $rs = ftp_login($cnn, LIBRARY, CODE);
            ftp_chdir($cnn, LIBRARY);
            $files = ftp_nlist($cnn, ".");

            foreach($files as $file){
                if(UPDATE_CACHE == true){
                    $filem = md5($file);
                    @rename(CACHE_PATH.$filem,CACHE_PATH.$filem."_old");
                    @rename(CACHE_PATH.$file,CACHE_PATH.$file."_old");
                    if($this->cache->get_file($file)){
                        $this->files->rrmdir(CACHE_PATH.$filem."_old");
                        $this->files->rrmdir(CACHE_PATH.$file."_old");
                    }
                }else{
                    $file = ($this->__debug())?$file:md5($file);
                    if(is_readable(CACHE_PATH.$file) && $this->files->is_old(CACHE_PATH.$file)){
                        rename(CACHE_PATH.$file,CACHE_PATH.$file."_old");
                        $this->cache->get_file($file);
                        $this->files->rrmdir(CACHE_PATH.$file."_old");
                    }
                }
            }
            $this->__info("¡Archivos de cache actualizados!");
        }
    }
    
    /**
     * Evaluates router request over pattern and executes callback function if success
     * 
     * @param string $uri_ptr Pattern to search over it
     * @param callable $callback Function to execute in callback
     * @return boolean Executes callback on success or false if fails
     */
    public function request($uri_ptr, $callback) {
        $matches = array();
        $uri_ptr = (defined('WEB_PATH')) ? WEB_PATH . $uri_ptr : $uri_ptr;
        $pattern = '%^' . preg_replace('/\{([\p{L}0-9_\-ñÑ ]+)\}/s', '([\p{L}0-9\-\_ñÑ ]+)', $uri_ptr) . '$%su';
        if (preg_match($pattern, $_GET['url'], $matches)) {
            array_shift($matches);
            $this->__info('ACTIVE ROUTE: ' . $uri_ptr);
            if (!isset($_SESSION['ROUTED'])) {
                $_SESSION['ROUTED'] = array($uri_ptr => $matches);
            } else {
                $_SESSION['ROUTED'][$uri_ptr] = $matches;
            } return call_user_func_array($callback, $matches);
        } else {
            return false;
        }
    }
    
    /**
     * Checks if debug is active
     * 
     * @return boolean
     */
    public static function __debug(){return(defined("DEBUG")&&DEBUG==true)?true:false;}
    
    /**
     * Alerts about object cloning
     */
    public function __clone(){
        $this->__error("Error al clonar el objeto: no está permitido.");
    }
    
    /**
     * Trigger error message and shows it over FirePHP if debug is true or Error Log if false
     * 
     * @param string $msg
     */
    public function __error($msg){
        if($this->__debug()){$this->debug->error($msg);}
        else{trigger_error($msg,E_USER_ERROR);}
    }
    
    /**
     * Trigger warning message and shows it over FirePHP if debug is true or Error Log if false
     * 
     * @param string $msg
     */
    public function __warn($msg){
        if($this->__debug()){$this->debug->warn($msg);}
        else{trigger_error($msg,E_USER_WARNING);}
    }
    
    /**
     * Trigger information message and shows it over FirePHP if debug is true or Error Log if false
     * 
     * @param string $msg
     */
    public function __info($msg){
        if($this->__debug()){$this->debug->info($msg);}
        else{trigger_error($msg,E_USER_NOTICE);}
    }
}