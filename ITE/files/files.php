<?php
namespace ITE\files;

/**
 * Class that group some usefull file functions
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */
class files {
    public $container;
    public $tokens;
    
    public function __construct($container) {
        $this->container = $container;
        
        /*for ($i = 100; $i < 500; $i++){
            if(($name = @token_name($i)) == 'UNKNOWN') continue;
            $this->tokens[$i] = $name;
        }*/
    }
    
    /**
     * Function that checks file modified date and determines if the field is old in days
     * 
     * @param string $file Path to the given file
     * @param integer $days_to_old Days to consider a file is outdated
     * @return boolean
     */
    public function is_old($file,$days_to_old = 7){
        $day_seconds = 24*60*60;
        $diff = abs(time() - filemtime($file));
        if(($diff / $day_seconds) > $days_to_old){return true;}
        else{return false;}
    }
    
    /**
     * Function that checks file modified date and determines if the field is old in seconds
     * 
     * @param string $file Path to the given file
     * @param integer $days_to_old Seconds to consider a file is outdated
     * @return boolean
     */
    public function is_old_seconds($file,$seconds_to_old = 3600){
        $diff = abs(time() - filemtime($file));
        if($diff > $seconds_to_old){return $diff - $seconds_to_old;}
        else{return false;}
    }
    
    /**
     * Function that compress file content
     * 
     * @param string $file Path to the given file
     * @param string $type Type of compression to apply
     * @return string Content compressed from the given file
     */
    public function compress_file($file, $type = "php"){
        if(!is_readable($file)){
            if($this->container->__debug()){$this->container->debug->error("No ha sido posible actualizar el archivo en cache: file not readable;");}
            else{trigger_error("No ha sido posible actualizar el archivo en cache: file not readable;",E_USER_ERROR);}
        }
        switch($type){
            case "php":
                $content = file_get_contents($file);
                
                if(!$this->container->__debug()){ // Clear PHP Code removing whitespaces and comments 
                    //$content = $this->compress_php($content);
                    $content = php_strip_whitespace($file);
                    $content = preg_replace("/\n\r|\r\n|\n|\r/", " ", $content); //Clear EOL
                    $content = preg_replace("/\t/", " ", $content);//Clear tabs
                }//else{$content = preg_replace('/\s\s+/', ' ', $content);} //Remove excess whitespaces only
                $content = trim($content);//Clear white spaces at begin and end
                //$content = $this->encode_php($content,1);
                
                break;
            case "css":
                $content = file_get_contents($file); //Get css content
                $replace = array("#/\*.*?\*/#s"=>"","#\s\s+#"=>" ");
                $search = array_keys($replace);
                $content = preg_replace($search, $replace, $content);
                $replace = array(
                    ": "  => ":",
                    "; "  => ";",
                    " {"  => "{",
                    " }"  => "}",
                    ", "  => ",",
                    "{ "  => "{",
                    ";}"  => "}", // Strip optional semicolons.
                    ",\n" => ",", // Don't wrap multiple selectors.
                    "\n}" => "}", // Don't wrap closing braces.
                    "} "  => "}\n", // Put each rule on it's own line.
                );
                $search = array_keys($replace);
                $content = str_replace($search, $replace, $content);
                break;
            case "html":
                $content = file_get_contents($file); //Get html content
                $content = $this->compress_html($content);
                break;
            case "js":
                $content = file_get_contents($file);
                //$content = $this->compress_js($content);
                
                if($this->container->__debug()){$this->container->debug->warn("No es posible realizar compresión de archivos JS en estos momentos, devuelto sin comprimir");}
                else{trigger_error("No es posible realizar compresión de archivos JS en estos momentos, devuelto sin comprimir",E_USER_WARNING);}
                break;
        }
        return $content;
    }
    
    /**
     * Function that executes a recursive 'rmdir'. Alters from 'rmdir' and 'unlink' PHP functions in case given directory was not empty
     * 
     * @param string $dir Path to the folder that will be deleted
     */
    function rrmdir($dir) {
        if (is_dir($dir)) {
         $objects = scandir($dir);
         foreach ($objects as $object) {
           if ($object != "." && $object != "..") {
             if(is_dir($dir.DIRECTORY_SEPARATOR.$object)){
                $this->rrmdir($dir.DIRECTORY_SEPARATOR.$object);
             }else{
                unlink($dir.DIRECTORY_SEPARATOR.$object);
             }
           }
         }
         //reset($objects);
         rmdir($dir);
        }
    }

    function rcopy($src, $dst) {
      if (file_exists($dst)) rrmdir($dst);
      if (is_dir($src)) {
        mkdir($dst);
        $files = scandir($src);
        foreach ($files as $file)
        if ($file != "." && $file != "..") rcopy("$src/$file", "$dst/$file");
      }
      else if (file_exists($src)) copy($src, $dst);
    }
    
    function folder_append_files($src_folder,$dst_folder){
        $files = scandir($src_folder);
        foreach ($files as $file){
            if ($file != "." && $file != ".."){
                if(file_exists("$dst_folder/$file")){rename("$dst_folder/$file", "$dst_folder/$file"."_old");}
                rcopy("$src_folder/$file", "$dst_folder/$file");
            }
        }
    }
    
    function folder_to_zip($dir, $zipArchive, $zipdir = ''){
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                if(!empty($zipdir)) $zipArchive->addEmptyDir($zipdir);
                while (($file = readdir($dh)) !== false) {
                    if(!is_file($dir . "/" . $file)){
                        if( ($file !== ".") && ($file !== "..")){
                            folder_to_zip($dir . "/" . $file, $zipArchive, $zipdir . $file . "/");
                        }
                    }else{
                        $zipArchive->addFile($dir . "/" . $file, $zipdir . $file);
                   }
                }
            }
        }
    } 
    
    /**
     * Function to autoload classes and interfaces. Commonly used with spl_autoload_register
     * 
     * @param string $dir Base path for the loaded file
     * @param string $class Name of the file that will be loaded
     */
    public function autoload($dir,$class){
            $filename = str_replace('\\','/',$dir. __NAMESPACE__ .$class.'.php');
            require_once($filename);
    }
    
    /**
     * Function that includes a file, regularly or inline
     * 
     * @param string $file Path to the field that will be included
     * @param boolean $inline If the file will be included inline or regularly
     */
    public function include_file($file,$inline = false){
        if($inline == "inline")$inline=true;
        $type = $this->get_extension($file);
        switch($type){
            case "js":
                if($inline){$this->include_js_inline($file);}
                else{include_once($file);}
                break;
            case "css":
                if($inline){$this->include_css_inline($file);}
                else{include_once($file);}
                break;
            default:
                include($file);
        }
    }
    
    /**
     * Function that includes a javascript file inline
     * 
     * @param string $file Path to the file that will be included
     */
    public function include_js_inline($file){
        echo "<script language=\"text/javascript\">";
        include_once($file);
        echo "</script>";
    }
    
    /**
     * Function that includes a CSS file inline
     * 
     * @param string $file Path to the file that will be included
     */
    public function include_css_inline($file){
        echo "<style type=\"text/css\">";
        include_once($file);
        echo "</style>";
    }
    
    public function fileGetContents(string $file){
        if(defined('WEB_PATH') && is_readable(WEB_PATH.$file)){
            return file_get_contents(WEB_PATH.$file);
        }elseif(defined('DEFAULT_WEB_PATH') && is_readable(DEFAULT_WEB_PATH.$file)){
            return file_get_contents(DEFAULT_WEB_PATH.$file);
        }elseif(is_readable(ROOT_PATH.$file)){
            return file_get_contents(ROOT_PATH.$file);
        }else{
            $this->container->__warn("Archivo '$file' no encontrado.");
            return '';
        }
    }
    
    /**
     * Function that retrieves the extension of a given file
     * 
     * @param string $file Path to the target file
     */
    public static function get_extension($file){
        if(strrpos($file,".") === false){return false;}
        $pos = strrpos($file,".");
        $ext = substr($file, $pos+1);
        $pos2 = strrpos($ext,"#");
        if($pos2 > 0)$ext = substr($ext, 0,$pos2);
        if(strlen($ext) > 5 || strlen($ext) < 1){return false;}else{return strtolower($ext);}
    }
    
    
    public function get_file_size($file,$decimals = 2){
        $bytes = filesize($file);
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
    
    /**
     * Function that compress js code
     * 
     * @param type $content
     * @return type
     */
    public function compress_js($content){
        $replace = array(
            '#\'([^\n\']*?)/\*([^\n\']*)\'#' => "'\1/'+\'\'+'*\2'", // remove comments from ' strings
            '#\"([^\n\"]*?)/\*([^\n\"]*)\"#' => '"\1/"+\'\'+"*\2"', // remove comments from " strings
            '#/\*.*?\*/#s'            => "",      // strip C style comments
            '#[\r\n]+#'               => "\n",    // remove blank lines and \r's
            '#\n([ \t]*//.*?\n)*#s'   => "\n",    // strip line comments (whole line only)
            '#([^\\])//([^\'"\n]*)\n#s' => "\\1\n",
                                                  // strip line comments
                                                  // (that aren't possibly in strings or regex's)
            '#\n\s+#'                 => "\n",    // strip excess whitespace
            '#\s+\n#'                 => "\n",    // strip excess whitespace
            '#(//[^\n]*\n)#s'         => "\\1\n", // extra line feed after any comments left
                                                  // (important given later replacements)
            '#/([\'"])\+\'\'\+([\'"])\*#' => "/*" // restore comments in strings
          );

          $search = array_keys( $replace );
          $content = preg_replace( $search, $replace, $content );

          $replace = array(
            "&&\n" => "&&",
            "||\n" => "||",
            "(\n"  => "(",
            ")\n"  => ")",
            "[\n"  => "[",
            "]\n"  => "]",
            "+\n"  => "+",
            ",\n"  => ",",
            "?\n"  => "?",
            ":\n"  => ":",
            ";\n"  => ";",
            "{\n"  => "{",
        //  "}\n"  => "}", (because I forget to put semicolons after function assignments)
            "\n]"  => "]",
            "\n)"  => ")",
            "\n}"  => "}",
            "\n\n" => "\n"
          );

          $search = array_keys( $replace );
          $content = str_replace( $search, $replace, $content );
          
          return $content;
    }
    
    
    /**
     * Function that compress HTML code
     * 
     * @param string $data HTML code that will be compressed
     * @return string Final HTML code
     */
    public function compress_html($data){
        $data=preg_replace_callback("/>[^<]*<\\/textarea/i", array(&$this, "harden_characters"), $data);
        $data=preg_replace_callback('/\\"[^\\"<>]+\\"/', array(&$this, "harden_characters"), $data);

        //$data=preg_replace("/(\\/\\/.*\\n)/","",$data); // remove single line comments, like this, from // to \\n
        $data=preg_replace("/(\\t|\\r|\\n)/","",$data);  // remove new lines \\n, tabs and \\r
        //$data=preg_replace("/(\\/\\*.*\\*\\/)/","",$data);  // remove multi-line comments /* */
        //$data=preg_replace("/(<![^>]*>)/","",$data);  // remove multi-line comments <!-- -->
        $data=preg_replace('/(\\s+)/', ' ',$data); // replace multi spaces with singles
        $data=preg_replace('/>\\s</', '><',$data); 

        $data=preg_replace_callback('/\\"[^\\"<>]+\\"/', array(&$this, "unharden_characters"), $data);
        $data=preg_replace_callback("/>[^<]*<\\/textarea/", array(&$this, "unharden_characters"), $data);

        return $data;
    }
    
    /**
     * Function that compress PHP code
     * 
     * @param string $data PHP source code that will be compressed
     * @return string Final PHP source code
     * @todo En pruebas, falla en los href, quiza php_strip_whitespace??
     */
    public function compress_php($source){
        $source = $this->obfuscate_php($source);
        return $source;
    }
    
    /**
     * Function that obfuscates PHP code
     * 
     * @param string $code
     * @return string PHP obfuscated code
     */
    public function obfuscate_php($code){
        // This regex requires functions are indented with tabs
        $regex = '/\n\s*(\w+ ){0,2}function \w+.+?\n\t?}/is';

        // We need to obfuscate and compress each function/method
        return preg_replace_callback($regex, array($this, 'variable_replace'), $code);
    }
    
    /**
     * Function that replaces variable names with letter combinations to obfuscate code 
     * 
     * @param string $source PHP source code to convert variable names
     * @return string PHP processed code
     */
    public function variable_replace($source){
        $output = '';
        $letters = range('a', 'z');

        // Tokenize the method code so we can compress it correctly (then remove php tag)
        $tokens = array_slice(token_get_all("<?php ". $source[0]), 1);
        $variables = array();

        foreach($tokens as $c)
        {
                if(is_array($c))
                {
                        // Do not replace $this with a short name!
                        if($c[0] === T_VARIABLE AND $c[1] !== '$this')
                        {
                                if( ! isset($variables[$c[1]]))
                                {
                                        // The first item of the difference is the value we use
                                        $result = array_diff($letters, $variables);
                                        $variables[$c[1]] = array_shift($result);
                                }
                                $c[1] = '$' . $variables[$c[1]];

                        }
                        $output .= $c[1];
                }
                else
                {
                        $output .= $c;
                }
        }
        //$this->container->__info($source);
        return $output;
    }
    
    /**
     * Function that encodes PHP code
     * 
     * @param string $code PHP code to be encoded
     * @param integer $levels Level of encoding. Number of iterations in code encoding
     * @param string $password Default password for encoding
     * @return string Final PHP code
     */
    private function encode_php($code, $levels=1,$password="BFMõÕ.í"){
        $levels=(int) $levels;
        
        
        $code=str_replace("<?php","",$code);
        $code=str_replace("<?","",$code);
        $code=str_replace("?>","",$code);
        $code=trim($code);
        //$code = addslashes($code);
        for ($i=0; $i<$levels;$i++){
            //$code=gzdeflate($code,9);
            //$code='eval(gzinflate("'.$code.'"));';
            /*if($i == 1){
                $code=base64_encode($code);
                $subcode=substr($code,0,9).$password.substr($code,9);
                
                //$code='eval(base64_decode(substr("'.$subcode.'",0,9).substr("'.$subcode.'",(9+strlen($password)))));';
                $code='eval(base64_decode(str_replace("'.$password.'","","'.$subcode.'")));';
                //$aux = gzinflate(substr($pass,9));
                //eval(base64_decode(substr($subcode,0,9).substr($subcode,(9+strlen($password)))));
            }else{
                $code=base64_encode($code);
                $code='eval(base64_decode("'.$code.'"));';
            }*/
            $code=base64_encode($code);
                $code=''.$code.'';
        }
        
        
        
        $code = '<?php eval(base64_decode("'.$code;
        $code .= '")); ?>';
        return $code;
    }

    /**
     * Function that replaces some non-printable characters from code with safe versions
     * 
     * @param type $array with parameters from 'preg_replace_callback' function
     * @return type Final code
     */
    private function harden_characters($array)
    {
        $safe=$array[0];
        $safe=preg_replace('/\\n/', "%0A", $safe);
        $safe=preg_replace('/\\t/', "%09", $safe);
        $safe=preg_replace('/\\s/', "&nbsp;", $safe);
        return $safe;
    }
    
    /**
     * Function that replaces some safe codes with his non-printable characters equivalent
     * 
     * @param type $array with parameters from 'preg_replace_callback' function
     * @return type Final code
     */
    private function unharden_characters($array)
    {
        $safe=$array[0];
        $safe=preg_replace('/%0A/', "\\n", $safe);
        $safe=preg_replace('/%09/', "\\t", $safe);
        $safe=preg_replace('/&nbsp;/', " ", $safe);
        return $safe;
    }
    
    /**
     * Function that shows warning message into html code view
     * 
     * @param string $source HTML code from frontend
     * @return string The given HTML code with the warning message appended at begining
     */
    public function source_warn(){
        global $empresa;
        $source_warn = "<!-- \n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n
        ".ucfirst($_SERVER['SERVER_NAME'])."\n
        Copyright &copy; ".date("Y")." ";
        
        if(isset($empresa)){$source_warn .= $empresa;}
        else{$source_warn .= ucfirst($_SERVER['SERVER_NAME']);}
        
        $source_warn .= ", Todos los derechos reservados.\n
        \n
        El código fuente de esta página es propietario y pretenece al titular del sitio web \n
        y al desarrollador del proyecto. Queda prohibida la copia, difusión, ingeniería inversa, \n
        remapeado y cualquier otra acción que resulte en una reutilización del código sin \n
        la autorización expresa de un propietario.\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n
        -->\n\n";
        return $source_warn;
    }
    
    /**
     * Function that compress HTML output of web page and includes a source warning
     * 
     * @param string $buffer HTLM code to be compressed
     * @return string Final HTML code
     */
    public function compress_html_output($buffer){
        $output = preg_replace("/>/", ">".$this->source_warn(), $buffer, 1);
        return $output;
    }
}