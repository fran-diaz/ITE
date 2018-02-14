<?php
namespace ITE\lang\loaders;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of i18n
 *
 * @author Fran Díaz <fran.diaz.gonzalez@gmail.com>
 */
class strings implements loadersInterface 
{
    public $container;
    private $translation;
    private $active_lang;
    private $lang_file_type;
    private $active_lang_file;
    private $pending = array();
    
    public function __construct($container) {
        $this->container = $container;
        
        if(LANG_IN_USE !== false){
            $this->loadLanguage(LANG_IN_USE, 'PO EDIT');
        }
    }
    
    /**
     * Extract user browser language
     * @return string|null Language code to use
     */
    public function getBrowserLanguage() {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strlen($_SERVER['HTTP_ACCEPT_LANGUAGE']) >= 2) {
            $locale = \locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
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
            case 'IOS STRINGS':
                $this->active_lang_file = LANG_PATH.$lang.".strings";
                if(is_readable(LANG_PATH.$lang.".strings")){
                    $this->translation = $this->processStringsFile(LANG_PATH.$lang.".strings");
                }else{
                    $this->container->__error("Imposible cargar la plantilla de idioma: archivo de idioma no legible usando formato 'IOS STRINGS' (".LANG_PATH.$lang.".strings).");
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
    
    public function getPending(){
        if(is_readable($this->active_lang_file.'.pending') !== false){
            
            $aux = file_get_contents($this->active_lang_file.'.pending');
            $decoded = json_decode($aux);
            if($decoded !== null){$this->pending = $decoded;}
            
        }
    }
    
    public function savePending(){
        if(!is_dir(LANG_PATH)){
            @mkdir(LANG_PATH,0775);
            if(!is_dir(LANG_PATH)){
                if(!headers_sent()){
                    $this->container->__warn('No ha sido posible crear el directorio de idiomas (lang). Posiblemente falten permisos.');
                }
                return false;
            }
        }
        $pending = json_encode($this->pending);

        return file_put_contents($this->active_lang_file.'.pending',$pending);
        
    }
    
    /**
     * Function that search a text in the translation array and returns his translation.
     * Sort version, same as $this->getText($search);
     * 
     * @param string $search Text string to be translated
     * @return string If located, the text translation; In not, the original string.
     */
    public function gt($search,...$params){
        if(LANG_IN_USE === false){return $search;}
        if(isset($this->translation[$search])){
            if(!empty($this->translation[$search])){
                return $this->translation[$search];
            }else{
                return $search;
            }
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
    public function getText($search,...$params){
        return $this->gt($search,$params);
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
