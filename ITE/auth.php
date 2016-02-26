<?php
namespace ITE;

/**
 * Class that manages permissions and restrictions
 * 
 * @copyright   Copyright © 2007-2015 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */
class auth {
    public $container;
    private $storage;
    private $perms_cache = array();
    
    public function __construct($container) {
        $this->container = $container;
        $this->setStorage($this->container->bdd);
        $this->createPermsCache();
    }
    
    private function createPermsCache(){
        $perms = $this->container->bdd->select('permissions','','',false,false);
        if($perms){
            $this->perms_cache = array();
            foreach($perms as $perm){
                $this->perms_cache[$perm['permissions_id']] = $perm['abbr'];
                $this->perms_cache[$perm['abbr']] = $perm['permissions_id'];
            }
            return true;
        }else{
            $this->container->__warn('No es posible crear la cache de permisos.');
            return false;
        }
    }
    
    public function setStorage($new_storage){
        if($new_storage instanceof \ITE\mysql){
            $this->storage = $new_storage;
            return true;
        }else{
            $this->container->__error('El nuevo almacen de permisos no es un objeto de base de datos válido.');
            return false;
        }
    }
    
    public function allowed($permission){
        if($this->checkSuperAdmin()) return true;
        
        if(is_string($permission)){
            if(isset($this->perms_cache[$permission])){
                $permission = $this->perms_cache[$permission];
            }else{
                $this->container->__warn('Imposible localizar el permiso solicitado en la cache de permisos.');
            }
        }
        
        if(in_array($permission,$_SESSION['allowed'])){return true;}
        return false;
    }
    
    public function can($level,$permission){
        if($this->checkSuperAdmin()) return true;
        
        if(isset($_SESSION['allowed'][$permission]) && $_SESSION['allowed'][$permission] >= $level){return true;}
        return false;
    }
    
    public function getUserPermissions($user_id){
        $user_perms = array();
        
        $user_info = $this->storage->select('users',"users_id = '$user_id'",'',false,false);
        if($user_info){
            $rol_info = $this->storage->select('role_permissions',"roles_id = '".$user_info[0]['roles_id']."'",'',false,false);
            if($rol_info){
                foreach($rol_info as $perm){
                    $user_perms[] = $perm['permissions_id'];
                }
            }
        }
        
        if(!empty($user_perms)){
            return $user_perms;
        }else{
            return false;
        }
    }
    
    public function checkSuperAdmin($user_id = 'SESSION'){
        switch($user_id){
            case 'SESSION':
                if(isset($_SESSION['user_sa']) && $_SESSION['user_sa'] == true){return true;}
                break;
            default:
                $user_info = $this->storage->select('users',"users_id = '$user_id'",'',false,false);
                if($user_info && $user_info[0]['sa'] === 1){return true;}
        }
        return false;
    }
}