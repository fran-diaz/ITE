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
define("DOMAIN",  "testcase");
define("DEBUG", false);
define("UPDATE_CACHE",false);
define("DBSERVER","localhost");
define("DBUSER","default_user");
define("DBPASS","default_password");
define("DB","default_db");
define("DEFAULT_LANG","es");
define("LANG_IN_USE",false);

autoload("ITE");