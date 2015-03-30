<?php
namespace ITE;

/**
 * Class to work with cache files
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */
class cache {
    public $container;
    
    public function __construct($container) 
    {
        $this->container = $container;
    }
    
    /**
     * Function that retrieves a file from FTP library and compress it if necessary
     * 
     * @param string $file File name to be retrieved from FTP library
     * @return boolean
     */
    public function get_file($file,$dir='')
    {
        if(!defined("LOCATION") || !defined("CODE") || !defined("LIBRARY") || !defined("CACHE_PATH")){
            $this->container->__error("Imposible recuperar el archivo remoto: falta alguna constante por definir");
            return false;
        }
        $cnn = ftp_connect(LOCATION);
        $rs = ftp_login($cnn, LIBRARY, CODE);
        if($rs === false){$this->container->__error("Imposible conectar a la libreria de funciones!");}
        $dir = ($dir=='')?'':$dir.DIRECTORY_SEPARATOR;
        ftp_chdir($cnn, LIBRARY.DIRECTORY_SEPARATOR.$dir);
        
        if (@ftp_chdir($cnn, $file) !== false) { 
            if($this->container->__debug()){
                @mkdir(CACHE_PATH.$dir.$file); 
                chmod(CACHE_PATH.$dir.$file, 0777);
                $dir = ($dir=='')?$file:$dir.$file;
            }else{
                @mkdir(CACHE_PATH.$dir.md5($file)); 
                chmod(CACHE_PATH.$dir.md5($file), 0777);
                $dir = ($dir=='')?$file:$dir.$file;
            }
            
            $files = ftp_nlist($cnn, "."); 
            foreach ($files as $filea) { 
                $this->get_file($filea, $dir); 
            }
            return true;
        }else{ 
            if ($file == '.' || $file == '..'){return;}
            if($this->container->__debug()){
                $aux = ftp_get($cnn, CACHE_PATH.$dir.$file, $file, FTP_BINARY);
            }else{
                $temp = explode(DIRECTORY_SEPARATOR,$dir);
                array_walk($temp, function(&$element,$index){$element =  md5($element);});
                array_pop($temp);
                $temp = implode(DIRECTORY_SEPARATOR,$temp).DIRECTORY_SEPARATOR;
                $aux = ftp_get($cnn, CACHE_PATH.$temp.md5($file), $file, FTP_BINARY);
                
            }
            if (!$aux) {ftp_close($cnn);$this->container->__error("Imposible obtener el archivo para cache: ".$file);return false;}
            else{
                ftp_close($cnn);
                if($this->container->__debug()){
                    chmod(CACHE_PATH.$dir.$file, 0777);
                    $this->compress_cache_file($dir.$file);
                }else{
                    chmod(CACHE_PATH.$temp.md5($file), 0777);
                    $this->compress_cache_file($temp.md5($file));
                }
                return true;
            }
        }
    }
    
    /**
     * Function to compress a cache file
     * 
     * @param string $file Cache file name to be compressed
     * @return boolean
     */
    public function compress_cache_file($file)
    {
        $content = $this->container->files->compress_file(CACHE_PATH.$file);
        if(file_put_contents(CACHE_PATH.$file, $content) !== false){return true;}
        else{
            $this->container->__error("No ha sido posible actualizar el archivo en cache ($file): file_put_contents failed [".__METHOD__."];");
            return false;
        }
    }
    
    
    /**
     * 
     * @param string $file Cache file name to be checked
     * @param integer $seconds_to_old Seconds that represent cache file is outdated.
     * @return boolean
     */
    public function cache_status($file,$seconds_to_old = 3600)
    {
        if(strpos($file, CACHE_PATH) === false && strpos($file, 'http') === false){
            $file = CACHE_PATH.$file;
        }
        if(is_readable($file)){
            if($this->container->files->is_old_seconds($file,$seconds_to_old)){ // Is old
                return false;
            }else{return true;}
        }else{$this->container->__warn('File is not readable. [file: '.$file.']');return false;}
    }
    
    /**
     * Deprecated function, later divided into functions get_file, compress_cache_file and cache_status. Maintained only for backward compatibility purposes.
     * 
     * @global object $_ITE Main MVC object
     * @global object $ITE Mirror of $_ITE
     * @param string $filepath Cache file name to be processed
     * @param integer $time_limit Seconds that represent cache file is outdated.
     * @return string HTML code generated from imported cache file
     */
    public function cache($filepath = null,$time_limit = 3600){
        global $_ITE,$ITE;
        if($filepath == null){$this->container->__error("Fallo en inicialización de cache, ruta al archivo no proporcionada ($filepath) [".__METHOD__.",".__LINE__."]");}
        
        $cache_name = md5($filepath."Yt3");
        if($this->cache_status($cache_name,$time_limit) && DEBUG === false){ // El archivo de cache existe y es válido
            if($this->container->files->get_extension($_GET['url']) == "css"){header("Content-type: text/css; charset: UTF-8");}
            elseif($this->container->files->get_extension($_GET['url']) == "js"){header("Content-Type: application/javascript; charset: UTF-8");}
            return substr(base64_decode(file_get_contents(CACHE_PATH.$cache_name)),0,-3);
        }else{ // El archivo de cache no existe o ya no vale
            ob_start();
                include($filepath);
                $buffer = ob_get_contents();
            ob_end_clean();
            
            file_put_contents(CACHE_PATH.$cache_name, base64_encode($buffer."Yt3"));
            return $buffer;
        }
    }
    
    /**
     * 
     * @param array $files Array of external files to be checked. Pattern array('internal filepath' => 'source filepath');
     * @param integer $seconds_to_old Seconds to consider that a file is outdated and renew it
     * @param mixed $returned The method to return contents of cached files.
     */
    public function checkFiles($files,$seconds_to_old = 3600,$returned = 'echo')
    {
        foreach($files as $name => $file){
            // Check cache folders exists
            $aux = explode(DIRECTORY_SEPARATOR,$name,-1);
            if(count($aux) >= 1){
                $tmp_folder = '';
                foreach($aux as $folder){
                    if(!is_readable(CACHE_PATH.$tmp_folder.$folder)){
                        mkdir(CACHE_PATH.$tmp_folder.$folder);
                    }
                    $tmp_folder .= $folder.DIRECTORY_SEPARATOR;
                }
            }
            
            // Get files if needed
            if(!is_array($file)){
                if($seconds_to_old !== false AND !$this->cache_status(CACHE_PATH.$name,$seconds_to_old)){
                    file_put_contents(CACHE_PATH.$name,file_get_contents($file));
                }
                
                // return file
                switch($returned){
                    case "echo":
                        $ext = $this->container->files->get_extension(CACHE_PATH.$name);
                        echo $this->container->files->compress_file(CACHE_PATH.$name,$ext);
                        unset($ext);
                        break;
                    case "include":
                        $this->container->files->include_file(CACHE_PATH.$name);
                        break;
                }
            }else{
                if(isset($file['seconds']) && $file['seconds'] >= 1){
                    $tmp_seconds = $file['seconds'];
                }elseif(isset($file['seconds']) && ($file['seconds'] === 0 || $file['seconds'] === false)){
                    $tmp_seconds = false;
                }else{
                    $tmp_seconds = $seconds_to_old;
                }
                
                if($tmp_seconds !== false AND !$this->cache_status(CACHE_PATH.$name,$tmp_seconds)){
                    file_put_contents(CACHE_PATH.$name,file_get_contents($file['source']));
                }
                
                // return file
                if(isset($file['returned'])){
                    $tmp_method = $file['returned'];
                }else{
                    $tmp_method = $returned;
                }
                
                switch($tmp_method){
                    case "echo":
                        $ext = $this->container->files->get_extension(CACHE_PATH.$name);
                        echo $this->container->files->compress_file(CACHE_PATH.$name,$ext);
                        unset($ext);
                        break;
                    case "include":
                        $this->container->files->include_file(CACHE_PATH.$name);
                        break;
                }
                
                unset($tmp_method,$tmp_seconds);
            }
        }
    }
}