<?php
namespace ITE;
/**
 * ***** BEGIN LICENSE BLOCK *****
 *  
 *  The MIT License (MIT)
 *
 *  Copyright (c) 2014 Fran Díaz
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
 * @copyright   Copyright © 2014 Fran Díaz
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
 * @copyright   Copyright © 2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @version     4.5b
 * @package     ITE
 * @access      public
 * 
 */

use Monolog\Logger;
use Monolog\Handler\FirePHPHandler;

class ite{
    /**
     * @property $instance ite
     */
    private static $instance;
    /**
     * @property $funcs functions 
     */
    public $funcs;
    /**
     * @property $files files 
     */
    public $files;
    /**
     * @property $cache cache 
     */
    public $cache;
    /**
     * @property $lang lang
     */
    public $lang;
    /**
     * @property $bdd mysql
     */
    public $bdd;
    /**
     * @property $css css
     */
    public $css;
    /**
     * @property $debug FirePHP
     */
    public $debug;
    /**
     * @property $auth auth
     */
    public $auth;
    
    /**
     * Protected constructor to prevent creating a new instance of the *Singleton* via the `new` operator from outside of this class.
     * Registers an custom autoloader function to load the rest of ITE classes (asuming that each class name space matches his respective path situation)
     */
    protected function __construct() {       
        spl_autoload_register(function($class){
            $pos = strrpos($class, '\\');
            $class_file = ($pos !== false)?substr($class,$pos+1):$class;
            $class_namespace = substr($class,0,$pos);
            $temp = explode('\\',$class_namespace);
            array_walk($temp, function(&$element,$index){$element = md5($element);});
            $temp = implode('\\',$temp);
            $filename = str_replace('\\','/',CACHE_PATH.$class_namespace.DIRECTORY_SEPARATOR.$class_file.'.php');
            if(file_exists($filename)){
                require_once($filename);
            }elseif(file_exists(str_replace('\\','/',CACHE_PATH. $temp.DIRECTORY_SEPARATOR.md5($class_file.'.php')))){
                $filename = str_replace('\\','/',CACHE_PATH. $temp.DIRECTORY_SEPARATOR.md5($class_file.'.php'));
                require_once($filename);
            }else{
                return false;
            }
        });

        //$this->debug=($this->__debug())?new FirePHP():false;
        $this->debug = new Logger('my_logger');
        $logger->pushHandler(new FirePHPHandler());
    }
    
    /**
     * Private clone method to prevent cloning of the instance of the *Singleton* instance.
     *
     * @return void
     */
    private function __clone()
    {
        $this->__error("Clonado del objeto no permitido.");
    }
    
    /**
     * Private unserialize method to prevent unserializing of the *Singleton* instance.
     *
     * @return void
     */
    private function __wakeup()
    {
    }
    
    /**
     * Checks cache files and retrieves updated files from library 
     * 
     * @return boolean Returns none if success or false if fails.
     */
    public function __cache(){
        if(UPDATE_CACHE === true){
            if(!defined("LOCATION") || !defined("CODE") || !defined("LIBRARY") || !defined("CACHE_PATH")){
                $this->__error("Imposible recuperar el archivo remoto: falta alguna constante por definir");
                return false;
            }
            
            if(!is_writable(CACHE_PATH)){
                $this->__error("Imposible recuperar el archivo remoto: la carpeta de cache no tiene permisos de escritura.");
                return false;
            }

            $cnn = ftp_connect(LOCATION);
            if($cnn === false){$this->__error("Imposible conectar al servidor FTP remoto.");return false;}
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
     * Checks if DEBUG constant is defined is active
     * 
     * @staticvar boolean DEBUG Assumed that is previusly defined in the bootstrap file (settings)
     * @return boolean
     */
    public static function __debug(){
        if(defined("DEBUG")){
            return DEBUG;
        }
    }
    
    /**
     * Trigger error message and shows it over FirePHP if debug is true or Error Log if false
     * 
     * @param string $msg Error message sent to the user
     */
    public function __error($msg){
        //if($this->__debug()){$this->debug->error($msg);}
        if($this->__debug()){$this->debug->addInfo($msg);}
        else{\trigger_error($msg,E_USER_ERROR);}
    }
    
    /**
     * Trigger warning message and shows it over FirePHP if debug is true or Error Log if false
     * 
     * @param string $msg
     */
    public function __warn($msg){
        //if($this->__debug()){$this->debug->warn($msg);}
        if($this->__debug()){$this->debug->addWarning($msg);}
        else{\trigger_error($msg,E_USER_WARNING);}
    }
    
    /**
     * Trigger information message and shows it over FirePHP if debug is true or Error Log if false
     * 
     * @param string $msg
     */
    public function __info($msg){
        //if($this->__debug()){$this->debug->info($msg);}
        if($this->__debug()){$this->debug->addError($msg);}
        else{\trigger_error($msg,E_USER_NOTICE);}
    }
    
    /**
     * Alias method of $this->lang->gt
     * 
     * @param type $ptr Code of string to be localized
     * @return string|null
     */
    public function __($ptr){
        if(method_exists($this->lang, 'gt')){
            return $this->lang->gt($ptr);
        }else{
            $this->__warn('El acceso directo a la función getText de la librería lang falló, la librería parece no estár instanciada.');
        }
    }
    
    /**
     * Constructor class (based on Singleton pattern 'http://www.phptherightway.com/pages/Design-Patterns.html#singleton'). Returns the *Singleton* instance of this class.
     * @staticvar ite $instance The *Singleton* instances of this class.
     * @var string $db_controller Name of the desired data base controller.
     * @return ite The *Singleton* instance.
     */
    public static function singleton($db_controller = 'mysql') {
        
        if (session_id() == "") {
            session_start();
        } 
        
        if (self::__debug()) {
            ini_set("display_errors", "1");
        } 
        
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
            self::$instance->cache = new cache(self::$instance);
            self::$instance->lang = new lang(self::$instance);
            self::$instance->files = new files(self::$instance);
            self::$instance->funcs = new functions(self::$instance);
            self::$instance->css = new css(self::$instance);
            
            self::$instance->__cache();
            self::set_db_controller($db_controller);
            if(defined('AUTH') && AUTH === true){
                self::$instance->auth = new auth(self::$instance);
            }
        } return self::$instance;
    }
    
    /**
     * Function that sets intantiates database library core
     * 
     * @var string $type Final name of the desired data base class to load. (namespace + class name)
     * @param string $type Name of the data base controller class
     * @return boolean
     */
    public static function set_db_controller($type = "mysql") 
    {
        $type = __NAMESPACE__.'\\'.$type;
        if(class_exists($type)){
            self::$instance->bdd = new $type(self::$instance);
            return true;
        }else{
            $this->__error("Controllador de base de datos no disponible, la clase no existe.");return false;
            return false;
        }
    }
    
    /**
     * Evaluates router request over pattern and executes callback function if success
     * 
     * @param string $uri_ptr Pattern to search over it
     * @param callable $callback Function to execute in callback
     * @var string $_GET['url'] Asummed that is previusly defined by .htaccess or by the user and contains a valid internal url (ex. blog/2010-01-01/example title)
     * @return boolean Executes callback on success or false if fails
     */
    public function request($uri_ptr, $callback, $url = null) 
    {
        
        if(is_null($url) || $url === 'GET'){$target_url = $_GET['url'];}
        else{$target_url = $url;}
        $matches = array();
        $uri_ptr = (defined('WEB_PATH')) ? WEB_PATH . $uri_ptr : $uri_ptr;
        $pattern = '%^' . preg_replace('/\{([\p{L}0-9_\-ñÑ \.\(\)]+)\}/s', '([\p{L}0-9\-\_ñÑ \.\(\)]+)', $uri_ptr) . '$%su';
        if (preg_match($pattern, $target_url, $matches)) {
            array_shift($matches);
            if(is_null($url) || $url === 'GET'){
                $this->__info('ACTIVE ROUTE: ' . $uri_ptr);
                if (!isset($_SESSION['ROUTED'])) {
                    $_SESSION['ROUTED'] = array($uri_ptr => $matches);
                } else {
                    $_SESSION['ROUTED'][$uri_ptr] = $matches;
                } 
            }
            return call_user_func_array($callback, $matches);
        } else {
            return false;
        }
    }
    
    /**
     * Function that controls the language system initialization and laguage in use
     */
    public function languageControl()
    {
        if(isset($_SESSION['display_language'])){
            define("LANG_IN_USE",$_SESSION['display_language']);
        }elseif(DEFAULT_LANG !== false){
            define("LANG_IN_USE",$this->lang->getBrowserLanguage());
        }else{
            define("LANG_IN_USE",false);
        }
        
        if(LANG_IN_USE !== false){
            $this->lang->load_lang((LANG_IN_USE !== false)?LANG_IN_USE:DEFAULT_LANG,LANG_FILE_TYPE);
        }
    }
    
    /**
     * Function that retrieves the required meta values title, description and keywords from /metas.xml or 
     * instantiates in default values.
     * 
     * @return array Title, description and keywords variables for extracting in global scope
     */
    public function metaControl()
    {       
        $metas = (is_readable('metas.xml'))?simplexml_load_file('metas.xml'):false;
        if($metas){
            $curr_uri = ($_SERVER['REQUEST_URI'] == "/" || $_SERVER['REQUEST_URI'] == "/index.html")?"/index.html":$_SERVER['REQUEST_URI'];
            foreach($metas->uri as $num => $uri){
                if($uri['id'] == $curr_uri){
                    $title = (string)$uri->title;
                    $description = (string)$uri->description;
                    $keywords = (string)$uri->keywords;
                }
            }
            if(!isset($title)){
                if(count($metas->xpath('/domain/uri[@id="default"]')) >= 1){
                    $title = (string)array_pop(@$metas->xpath('/domain/uri[@id="default"]'))->title;
                    $description = (string)array_pop(@$metas->xpath('/domain/uri[@id="default"]'))->description;
                    $keywords = '';
                }elseif(count($metas->xpath('/domain/uri[@id="/index.html"]')) >= 1){
                    $title = (string)array_pop(@$metas->xpath('/domain/uri[@id="/index.html"]'))->title;
                    $description = (string)array_pop(@$metas->xpath('/domain/uri[@id="/index.html"]'))->description;
                    $keywords = '';
                }else{
                    $title = DOMAIN;
                    $description = DOMAIN;
                    $keywords = DOMAIN;
                }    
            }
        }else{$title = DOMAIN;$description = DOMAIN;$keywords = DOMAIN;}
        return array('title' =>$title, 'description' =>$description,'keywords' =>$keywords);
    }
    
    /**
     * Function that handles de requested url to locate the final file in case of defined web_path is a specific directory different than root path.
     */
    public function requestControl()
    {
        if($this->files->get_extension($_GET['url']) === false){
            if(defined('WEB_PATH')){
                $_GET['url'] = WEB_PATH.$_GET['url'];
            }
            
            if(is_file(ROOT_PATH.$_GET['url'].'.html')){
                $_GET['url'] .= '.html';
            }elseif(is_dir(ROOT_PATH.$_GET['url'].'/')){
                $_GET['url'] .= '/';
            }
        }else{
            if(defined('WEB_PATH') && is_readable(WEB_PATH.$_GET['url'])){
                $_GET['url'] = WEB_PATH.$_GET['url'];
            }elseif(defined('WEB_PATH') && !is_readable(WEB_PATH.$_GET['url']) && !is_readable($_GET['url'])){
                $_GET['url'] = WEB_PATH.$_GET['url'];
            }
        }
        if($_GET['url'] == '' || substr($_GET['url'],-1) == '/'){
            $_GET['url'] .= "index.html";
        }
    }
    
    /**
     * Function that controls de routing process. Checks the final file looking for a router that manages calls for specific directory
     * 
     * @global string $title Meta title value
     * @global string $description Meta description value
     * @global string $keywords Meta keywords value
     * @global ITE\ite $_ITE Main framework
     * @return array Variables buffer and header_content to instantiate in global scope.
     */
    public function routerControl()
    {
        global $title,$description,$keywords,$_ITE,$_ITEC;
        
        ob_start();
        $header_content = null;
        if(is_readable(ROOT_PATH.$_GET['url'])){
            if((   $this->files->get_extension($_GET['url']) == "html" 
                || $this->files->get_extension($_GET['url']) == "php") 
                && strpos($_GET['url'], "skip-process") === false){
                
                include(ROOT_PATH.$_GET['url']);
                ob_start();
                if(is_readable(ROOT_PATH.WEB_PATH."inc/header.php")){
                    require(ROOT_PATH.WEB_PATH."inc/header.php");
                }else{
                require(ROOT_PATH."inc/header.php");
                }
                $header_content = ob_get_contents();
                ob_end_clean();
                if(is_readable(ROOT_PATH.WEB_PATH."inc/footer.php")){
                    require(ROOT_PATH.WEB_PATH."inc/footer.php");
                }else{
                require(ROOT_PATH."inc/footer.php");
                }
                $_SESSION['ROUTED'] = $_GET['url'];
                $_SESSION['CURRENT_URL'] = $_GET['url'];
            }else{include(ROOT_PATH.$_GET['url']);}  
        }else{
            unset($_SESSION['ROUTED']);
            $aux = explode(DIRECTORY_SEPARATOR,$_GET['url']);
            array_pop($aux);
            $tmp_path = "";
            
            foreach($aux as $folder){
                $tmp_path .= $folder.DIRECTORY_SEPARATOR;
                if(is_readable(ROOT_PATH.$tmp_path.'router.php')){
                    if(DEBUG === true){
                        $this->__info('ROUTING FROM -> '.ROOT_PATH.$tmp_path.'router.php');
                        $this->__info('ROUTING TO -> '.$_GET['url']);
                    }
                    include(ROOT_PATH.$tmp_path.'router.php');
                    ob_start();
                    if(is_readable(ROOT_PATH.WEB_PATH."inc/header.php")){
                        require(ROOT_PATH.WEB_PATH."inc/header.php");
                    }else{
                    require(ROOT_PATH."inc/header.php");
                    }
                    $header_content = ob_get_contents();
                    ob_end_clean();
                    if(is_readable(ROOT_PATH.WEB_PATH."inc/footer.php")){
                        require(ROOT_PATH.WEB_PATH."inc/footer.php");
                    }else{
                    require(ROOT_PATH."inc/footer.php");
                }
            }
            }
            if(!isset($_SESSION['ROUTED'])){
                $this->__warn("Archivo no encontrado: ".$_GET['url']);
                if(defined('WEB_PATH') && is_readable(ROOT_PATH.WEB_PATH."404.html")){header("Location: /404.html");}
                elseif(is_readable(ROOT_PATH."404.html")){header("Location: /404.html");}
                else{die("Imposible mostrar la página, archivo no encontrado.");}
            }else{
                $_SESSION['CURRENT_URL'] = $_GET['url'];
            }
            if(DEBUG === true){$this->__info($_SESSION['ROUTED']);}
        }
        $buffer = ob_get_contents();
        ob_end_clean();
        
        return array('buffer' => $buffer,'header_content' => $header_content);
    }
    
    /**
     * Function that controls the output buffer contents for compress in case of needed.
     * 
     * @global string $header_content Contents of header file
     * @global string $buffer Raw contents of buffer before compression
     * @return string Definitive buffer contents to be printed
     */
    public function bufferControl()
    {
        global $header_content,$buffer;
        
        if(isset($header_content) && !is_null($header_content)){
            $buffer = $header_content.$buffer;
        }
        
        if(
                ($this->files->get_extension($_GET['url']) === "html" || 
                $this->files->get_extension($_GET['url']) === "php" || 
                $this->files->get_extension($_GET['url']) === false) &&  
                DEBUG === false
        ){
            if(strpos($_GET['url'], "skip-process") === false){
                $buffer = $this->files->source_warn().$this->files->compress_html($buffer);
            }else{
                $headers = headers_list();
                
                if(array_search("Content-type: application/vnd.ms-excel; name='excel';", $headers) === false){
                $buffer = $this->files->compress_html($buffer);
            }
                
        }
        }

        return $buffer;
    }
}