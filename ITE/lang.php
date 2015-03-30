<?php
namespace ITE;

/**
 * Class that manages language layers
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */
use \locale_accept_from_http;

class lang {
    public $container;
    private $translation;
    private $active_lang;
    private $lang_file_type;
    private $active_lang_file;
    private $pending = array();
    
    public function __construct($container) {
        $this->container = $container;
    }
    
    /**
     * Extract user browser language
     * @return string|null Language code to use
     */
    public function getBrowserLanguage() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) >= 2) {
            $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $aux = $this->container->bdd->select('SELECT abbr FROM languages','','',false);
            if($aux){foreach($aux as $lang){
                $languages[] = $lang['abbr']; 
            }}else{
                $table_info = $this->container->bdd->free_query('SHOW TABLES LIKE \'languages\'');
                if($table_info === false){
                    $aux = $this->container->bdd->free_query('CREATE TABLE `languages` (`languages_id` int(11) NOT NULL AUTO_INCREMENT,`language` varchar(255) COLLATE utf8_spanish2_ci DEFAULT NULL,`abbr` varchar(6) COLLATE utf8_spanish2_ci DEFAULT NULL,PRIMARY KEY (`languages_id`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;');
                    if($aux === false){
                        $this->container->__warn("No ha sido posible crear la base de datos para la información de los idiomas (languages), el usuario de base de datos puede no tener permisos de creación de tablas.");
                    }else{
                        $this->container->bdd->insert("languages",array('language','abbr'),array($locale,$locale));
                    }
                }
                $languages = array($locale);
            }
            return locale_lookup($languages, $locale, false, DEFAULT_LANG);
        }else{
            return NULL;
        }
    }
    
    /**
     * Function that loads language layer file
     * 
     * @param string $lang Language file to load
     * @param string $type Type of language file to load (Ex. PHP Array, IOS STRINGS, PO EDIT)
     */
    public function loadLanguage($lang,$type = 'PHP array'){
        $this->active_lang = $lang;
        $this->lang_file_type = $type;
        $this->container->__info("Activado sistema de idiomas: cargando plantilla ($lang)");
        
        header('Vary: Accept-Language');
        
        switch($type){
            case 'PHP array':
                $this->active_lang_file = LANG_PATH.$lang.".php";
                if(is_readable(LANG_PATH.$lang.".php")){
                    require(LANG_PATH.$lang.".php");
                }else{
                    $this->container->__error("Imposible cargar la plantilla de idioma: archivo de idioma no legible usando formato 'PHP array' (".LANG_PATH.$lang.".php).");
                }
                break;
            case 'IOS STRINGS':
                $this->active_lang_file = LANG_PATH.$lang.".strings";
                if(is_readable(LANG_PATH.$lang.".strings")){
                    $this->translation = $this->processStringsFile(LANG_PATH.$lang.".strings");
                }else{
                    $this->container->__error("Imposible cargar la plantilla de idioma: archivo de idioma no legible usando formato 'IOS STRINGS' (".LANG_PATH.$lang.".strings).");
                }
                break;
            case 'PO EDIT':
                $this->active_lang_file = LANG_PATH.$lang.".po";
                if(is_readable(LANG_PATH.$lang.".po")){
                    $this->translation = $this->processPoFile(LANG_PATH.$lang.".po");
                }else{
                    $this->container->__error("Imposible cargar la plantilla de idioma: archivo de idioma no legible usando formato 'PO EDIT' (".LANG_PATH.$lang.".po).");
                }
                break;
            default:
                $this->container->__warn('Tipo de archivo de idioma no reconocido. Importando el archivo ['.LANG_PATH.$lang.".php".'].');
                $this->active_lang_file = LANG_PATH.$lang.".php";
                if(is_readable(LANG_PATH.$lang.".php")){
                    require(LANG_PATH.$lang.".php");
                }else{
                    $this->container->__error("Imposible cargar la plantilla de idioma: archivo de idioma no legible (".LANG_PATH.$lang.".php).");
                }
        }
        
        if($this->container->__debug() === true){
            $this->getPending();
        }
    }
    
    /**
     * Same function as 'loadLanguage', maintained only for backward compatibility purposes.
     * 
     * @param type $lang
     * @param type $type
     */
    public function load_lang($lang,$type = 'PHP array'){
        $this->loadLanguage($lang, $type);
    }
    
    /**
     * Function that receives a file path, reads its content (in base of IOS STRINGS format) and process it to retrieve an array.
     * 
     * @param text $file Content of language file to process.
     * @return array Translation result.
     */
    public function processStringsFile($file){
        $content = file_get_contents($file);
        
        preg_match_all('#"([^"]+)"\s*=\s*"([^"]+)";#', $content, $match);
        $translation = array_combine($match[1], $match[2]);
        
        return $translation;
    }
    
    public function poFileHeaders(){
        $headers = 'msgid ""'."\n".'msgstr ""'."\n\n".'"Plural-Forms: nplurals=2; plural=(n != 1);\n"'."\n".'"Project-Id-Version: '.$this->active_lang.'\n"'."\n".'"POT-Creation-Date: \n"'."\n".'"PO-Revision-Date: \n"'."\n".'"Last-Translator: \n"'."\n".'"Language-Team:  <desarrollo@brainhardware.es>\n"'."\n".'"MIME-Version: 1.0\n"'."\n".'"Content-Type: text/plain; charset=UTF-8\n"'."\n".'"Content-Transfer-Encoding: 8bit\n"'."\n".'"Language: \n"'."\n".'"X-Generator: ITE\n"'."\n".'"X-Poedit-SourceCharset: UTF-8\n"'."\n";
        
        return $headers;
    }
    
    /**
     * Function that receives a file path, reads its content (in base of PO EDIT format) and process it to retrieve an array.
     * 
     * @param text $file Content of language file to process.
     * @return array Translation result.
     */
    public function processPoFile($file){
        $translation = array();
        if(!is_readable($file)){return false;}
        $content = file($file);
        $provisional = false;
        $id_splitted = false;
        $msg_splitted = false;
        
        foreach ($content as $line) {
            if(substr(trim($line),-3) === '\n"'){
                continue;
            }
            
            if (substr($line,0,8) == '#, fuzzy') {
                if($msg_splitted){$msg_splitted = false;$translation[$current] = $msg;}
                $provisional = true;
            }
            
            if (substr($line,0,1) == '"' && $id_splitted === true) {
                $current .= substr(trim($line),1,-1);
                continue;
            }
            
            if (substr($line,0,1) == '"' && $msg_splitted === true) {
                $msg .= substr(trim($line),1,-1);
                continue;
            }
            
            if (substr($line,0,5) == 'msgid') {
                if($msg_splitted){$msg_splitted = false;$translation[$current] = $msg;}
                $current = trim(substr(trim(substr($line,5)),1,-1));
                if(empty($current)){$id_splitted = true;continue;}
            }
            if (substr($line,0,6) == 'msgstr') {
                if($id_splitted){$id_splitted = false;}
                $msg = trim(substr(trim(substr($line,6)),1,-1));
                if(empty($msg)){$msg_splitted = true;continue;}
                
                $translation[$current] = $msg;
                if($provisional){$provisional = false;}
            }
        }
        if($msg_splitted){$msg_splitted = false;$translation[$current] = $msg;}
        
        return $translation;
    }
    
    public function writePoFile($final_file){
        $content = $this->poFileHeaders();
        
        foreach($this->pending as $key => $value){
            $content .= "\n#, fuzzy";
            $content .= "\n".'msgid "'.$value.'"';
            $content .= "\n".'msgstr ""'."\n";
        }
        return file_put_contents($final_file,$content);
    }
    
    /**
     * Function that search a text in the translation array and returns his translation.
     * Sort version, same as $this->getText($search);
     * 
     * @param string $search Text string to be translated
     * @return string If located, the text translation; In not, the original string.
     */
    public function gt($search){
        if(LANG_IN_USE === false){return $search;}
        if(isset($this->translation[$search])){
            return $this->translation[$search];
        }else{
            if(array_search($search, $this->pending) === false){$this->pending[] = $search;}
            
            $this->container->__warn('Traducción no encontrada. No se ha encontrado la traducción del término. [idioma: '.$this->active_lang.', término: '.$search.', url: '.$_GET['url'].']');
            return $search;
        }
    }
    
    /**
     * Same as $this->gt($search); Long version (Only for backward compatibility purposes).
     * 
     * @param string $search Text string to be translated.
     * @return string If located, the text translation; In not, the original string.
     */
    public function getText($search){
        return $this->gt($search);
    }
    
    public function getPending(){
        if(is_readable($this->active_lang_file.'.pending') !== false){
            if($this->lang_file_type === "PO EDIT"){
                $aux = $this->processPoFile($this->active_lang_file.'.pending');
                $this->pending = array_keys($aux);
            }else{
                $aux = file_get_contents($this->active_lang_file.'.pending');
                $decoded = json_decode($aux);
                if($decoded !== null){$this->pending = $decoded;}
            }
        }
    }
    
    public function savePending(){
        if(!is_dir(ROOT_PATH.'lang')){mkdir(ROOT_PATH.'lang',0775);}
        if($this->lang_file_type === "PO EDIT"){
            return $this->writePoFile($this->active_lang_file.'.pending');
        }else{
            $pending = json_encode($this->pending);
            return file_put_contents($this->active_lang_file.'.pending',$pending);
        }
    }
    
    public function __destruct() {
        if($this->container->__debug() === true && LANG_IN_USE !== false){
            $pre_pending = $this->pending;
            $this->getPending();
            $this->pending = array_values(array_unique(array_merge($pre_pending,$this->pending)));
            $this->savePending();
        }
    }
}