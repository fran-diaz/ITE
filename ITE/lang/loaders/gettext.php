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
class gettext implements loadersInterface {
    public $container;
    private $pending = [];
    
    public function __construct(\ITE\ite $container) {
        $this->container = $container;
        
        //register_shutdown_function(array($this, 'destructHandler'));
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
     * Function that search a text in the translation array and returns his translation.
     * Sort version, same as $this->getText($search);
     * 
     * @param string $search Text string to be translated
     * @return string If located, the text translation; In not, the original string.
     */
    public function gt($search,...$params){
        if(LANG_IN_USE === false){return $search;}
        if(count($params) >= 1){
            return vsprintf(gettext($search),$params);
        }else{
            $tmp = gettext($search);
            if($tmp === $search){
                //$this->container->__warn('No encontrada traducción para: '.$search);
                $this->pending[] = "('".DEFAULT_LANG."','$search')";
            }
            return $tmp;
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
    
    public function destructHandler(){
        if($this->container->__debug() === true && count($this->pending) >= 1){
            $table_info = $this->container->bdd->free_query('SHOW TABLES LIKE \'system_i18n\'');
            if($table_info === false){
                $aux = $this->container->bdd->free_query('CREATE TABLE `system_i18n` (  `system_i18n_id` int(11) unsigned NOT NULL AUTO_INCREMENT,  `locale` varchar(255) COLLATE utf8_spanish2_ci DEFAULT \'es_ES\' COMMENT \'label:conjunto de idioma\',  `message` varchar(255) COLLATE utf8_spanish2_ci NOT NULL COMMENT \'label:mensaje\',  `translation` text COLLATE utf8_spanish2_ci COMMENT \'label:traducción\',  `date` datetime DEFAULT CURRENT_TIMESTAMP COMMENT \'label:fecha\',  PRIMARY KEY (`system_i18n_id`),  UNIQUE KEY `INDEX_MESSAGE` (`message`,`locale`)) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;');
                if($aux === false){
                    die("No ha sido posible crear la base de datos (system_i18n), el usuario de base de datos puede no tener permisos de creación de tablas.");
                }
            }
            
            //if(count($this->pending) >= 1){$this->container->__warn('No encontrada traducción para varias cadenas.');}
            
            $this->container->bdd->free_query("INSERT INTO system_i18n (`locale`,`message`) VALUES ".implode(',',$this->pending)." ON DUPLICATE KEY UPDATE `date` = NOW();");
        }
    }
    
    public function __destruct() {
        //$this->destructHandler();
    }
}
