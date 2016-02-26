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
    
    public function allowed($permission = false){
        if($this->checkSuperAdmin()) return true;
        
        if(is_string($permission)){
            if(isset($this->perms_cache[$permission])){
                $permission = $this->perms_cache[$permission];
            }else{
                $this->container->__warn('Imposible localizar el permiso solicitado en la cache de permisos ('.$permission.').');
                
            }
        }
        
        if(count($_SESSION['allowed']) >= 1 && in_array($permission,$_SESSION['allowed'])){return true;}
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
        
        return $user_perms;
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
    
    /**
     * Network ranges can be specified as:
     * 1. Wildcard format:     1.2.3.*
     * 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
     * 3. Start-End IP format: 1.2.3.0-1.2.3.255
     * @param type $ip
     * @param type $range
     * @return boolean
     */
    public function checkIP($ip,$range){
        $this->container->__info($range);
        if (strpos($range, '/') !== false) {
            // $range is in IP/NETMASK format
            list($range, $netmask) = explode('/', $range, 2);
            if (strpos($netmask, '.') !== false) {
              // $netmask is a 255.255.0.0 format
              $netmask = str_replace('*', '0', $netmask);
              $netmask_dec = ip2long($netmask);
              return ( (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec) );
            } else {
              // $netmask is a CIDR size block
              // fix the range argument
              $x = explode('.', $range);
              while(count($x)<4) $x[] = '0';
              list($a,$b,$c,$d) = $x;
              $range = sprintf("%u.%u.%u.%u", empty($a)?'0':$a, empty($b)?'0':$b,empty($c)?'0':$c,empty($d)?'0':$d);
              $range_dec = ip2long($range);
              $ip_dec = ip2long($ip);

              # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
              #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

              # Strategy 2 - Use math to create it
              $wildcard_dec = pow(2, (32-$netmask)) - 1;
              $netmask_dec = ~ $wildcard_dec;

              return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
}
        } else {
            // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
            if (strpos($range, '*') !==false) { // a.b.*.* format
              // Just convert to A-B format by setting * to 0 for A and 255 for B
              $lower = str_replace('*', '0', $range);
              $upper = str_replace('*', '255', $range);
              $range = "$lower-$upper";
            }

            if (strpos($range, '-')!==false) { // A-B format
              list($lower, $upper) = explode('-', $range, 2);
              $lower_dec = (float)sprintf("%u",ip2long($lower));
              $upper_dec = (float)sprintf("%u",ip2long($upper));
              $ip_dec = (float)sprintf("%u",ip2long($ip));
              return ( ($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec) );
            }else{
                if(filter_var($range, FILTER_VALIDATE_IP,FILTER_FLAG_IPV4) !== false){
                    $ip_dec = (float)sprintf("%u",ip2long($ip));
                    $range_dec = (float)sprintf("%u",ip2long($range));
                    return ($ip_dec===$range_dec);
                }
            }

            $this->container->__error('Formato de IP almacenada no reconocida al intentar chequearla.');
            return false;
        }
    }
}