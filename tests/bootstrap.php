<?php
namespace ITE;

/**
 * @author Fran Díaz <fran.diaz.gonzalez@gmail.com>
 */

function autoload($dir){
    $files = scandir($dir);
    foreach($files as $file){
        if($file == "." || $file == "..")continue;
        $tmp_dir = $dir.DIRECTORY_SEPARATOR.$file;
        if(is_dir($tmp_dir)){
            autoload($tmp_dir);
        }elseif(is_readable($tmp_dir)){
            require_once($tmp_dir);
        }
    }
}

session_start();

define("ROOT_PATH",getcwd().DIRECTORY_SEPARATOR);
define("CACHE_PATH","ITE");
define("LANG_PATH","ITE");
define("WEB_PATH","ITE");
define("PDC_PATH",ROOT_PATH);
define("PDC_FOLDER","");
define("LIBRARY","library");
define("CODE", urlencode("test"));
define("LOCATION", "test");
define("BASEFILE","ite");
define("DOMAIN",  str_replace("beta.", "", $_SERVER['HTTP_HOST']));
define("DEBUG", true);
(isset($_REQUEST['update_cache']))?define("UPDATE_CACHE",true):define("UPDATE_CACHE",false);
define("DBSERVER","server");
define("DBUSER","user");
define("DBPASS","pass");
define("DB","db");
define("DEFAULT_LANG","es");
define("LANG_IN_USE",false);

autoload("ITE");