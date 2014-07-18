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
class lang {
    public $container;
    private $translation;
    private $active_lang;
    private $lang_file_type;
    private $pending = array();
    
    public function __construct($container) {
        $this->container = $container;
        
        $this->getPending();
    }
    
    /**
     * Function that loads language layer file
     * 
     * @param string $lang Language file to load
     * @param string $type Type of language file to load (Ex. PHP Array, IOS STRINGS)
     */
    public function load_lang($lang,$type = 'PHP array'){
        $lang = strtolower($lang);
        $this->active_lang = $lang;
        $this->lang_file_type = $type;
        $this->container->__info("Activado sistema de idiomas: cargando plantilla ($lang)");
        
        switch($type){
            case 'PHP array':
                if(is_readable(LANG_PATH.$lang.".php")){
                    require(LANG_PATH.$lang.".php");
                }else{
                    $this->container->__error("Imposible cargar la plantilla de idioma: archivo de idioma no legible usando formato 'PHP array' (".LANG_PATH.$lang.".php).");
                }
                break;
            case 'IOS STRINGS':
                if(is_readable(LANG_PATH.$lang.".strings")){
                    $this->translation = $this->processStringsFile(LANG_PATH.$lang.".strings");
                }else{
                    $this->container->__error("Imposible cargar la plantilla de idioma: archivo de idioma no legible usando formato 'IOS STRINGS' (".LANG_PATH.$lang.".strings).");
                }
                break;
            default:
                $this->container->__warn('Tipo de archivo de idioma no reconocido. Importando el archivo ['.LANG_PATH.$lang.".php".'].');
                if(is_readable(LANG_PATH.$lang.".php")){
                    require(LANG_PATH.$lang.".php");
                }else{
                    $this->container->__error("Imposible cargar la plantilla de idioma: archivo de idioma no legible (".LANG_PATH.$lang.".php).");
                }
        }
    }
    
    /**
     * Functio that receives a file path, reads its content (in base of IOS STRINGS format) and process it to retrieve an array.
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
    
    /**
     * Function that search a text in the translation array and returns his translation.
     * Sort version, same as $this->getText($search);
     * 
     * @param string $search Text string to be translated
     * @return string If located, the text translation; In not, the original string.
     */
    public function gt($search){
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
        if(is_readable(LANG_PATH.DEFAULT_LANG.'.pending') !== false){
            $aux = file_get_contents(LANG_PATH.DEFAULT_LANG.'.pending');
            $decoded = json_decode($aux);
            if($decoded !== null){$this->pending = $decoded;}
        }
    }
    
    public function savePending(){
        $pending = json_encode($this->pending);
        return file_put_contents(LANG_PATH.DEFAULT_LANG.'.pending',$pending);
    }
    
    public function __destruct() {
        $pre_pending = $this->pending;
        $this->getPending();
        $this->pending = array_values(array_unique(array_merge($pre_pending,$this->pending)));
        $this->savePending();
    }
}