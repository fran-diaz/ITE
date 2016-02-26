<?php
namespace ITE;

/**
 * Group of commonly used functions
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */
class functions { 
    public $container;
    
    public function __construct($container) {
        $this->container = $container;
    }
    
    /**
     * Function to generates random password
     * 
     * @param integer $length Number of characters that must have final password
     * @param integer $strength Level of complexity that must have final password
     * @return string Generated password
     */
    public static function gen_pass($length=8, $strength=4) {
        $v = 'aeuy';
        $c = 'bdghjmnpqrstvz';
        if ($strength >= 1) {$c .= 'bgghjlmnpqrstvwxy';}
        if ($strength >= 2) {$c .= '23456789';}
        if ($strength >= 4) {$c .= 'BDGHJLMNPQRSTVWXZ';}
        if ($strength >= 8) {$v .= "aeuyAEUY";}
        if ($strength >= 16 ) {$v .= '@#$%';}

        $pass = "";
        $alt = time() % 2;
        for ($i = 0; $i < $length; $i++) {
            if ($alt == 1) {$pass.= $c[(rand() % strlen($c))];$alt = 0;} 
            else {$pass .= $v[(rand() % strlen($v))];$alt = 1;}
        }
        return $pass;
    }
    
    /**
     * Function that process variables to prevent security breaches
     * 
     * @param mixed $input Variable to process
     * @param boolean $strip_tags execute php function 'strip_tags' to remove reserved names from wariable content
     * @return mixed Processed input variable
     */
    public function sanitize($input, $strip_tags=true) {
	if(is_array($input)) {foreach($input as $key => $value) {$input[$key] = self::sanitize($value);}}
        else {
            if(get_magic_quotes_gpc()) {
                if(ini_get('magic_quotes_sybase')){$input = str_replace("''", "'", $input);}
                else {$input = stripslashes($input);}
            }
            if($strip_tags) {$input = strip_tags($input);}
            $input = $this->container->bdd->escape_string($input);
            $input = trim($input);
	}
	return $input;
    }
    
    /**
     * Function that converts integer from range 1-12 to equivalent month spanish name
     * 
     * @param integer $month
     * @return string Spanish month name
     */
    public static function int2month($month){
	if(is_string($month)&& substr($month,0,1) == "0"){$month = str_replace("0", "", $month);}
        switch((int)$month){
            case 1: return "Enero";break;
            case 2: return "Febrero";break;
            case 3: return "Marzo";break;
            case 4: return "Abril";break;
            case 5: return "Mayo";break;
            case 6: return "Junio";break;
            case 7: return "Julio";break;
            case 8: return "Agosto";break;
            case 9: return "Septiembre";break;
            case 10: return "Octubre";break;
            case 11: return "Noviembre";break;
            case 12: return "Diciembre";break;
	}
    }
    
    /**
     * Function that converts integer from range 1-7 to equivalent week day spanish name
     * 
     * @param type $day Week day to convert
     * @return string Spanish week day name
     */
    public static function int2day($day){
        switch((int)$day){
            case 1: return "Lunes";break;
            case 2: return "Martes";break;
            case 3: return "Miercoles";break;
            case 4: return "Jueves";break;
            case 5: return "Viernes";break;
            case 6: return "Sabado";break;
            case 7: return "Domingo";break;
            default: return "Número incorrecto";
	}
    }
    
    /**
     * Function that formats input date
     * 
     * @param string $date Date to be formated
     * @param integer $modo Number in range to 0-6 that represents formatting method
     * @param character $separator Character to be put between date's pieces
     * @return string Formated date
     */
    public static function date_format($date,$modo = 1,$separator = "-"){
        $aux = explode(" ",$date);
        $aux2 = explode("-",$aux[0]);
        switch($modo){
            case 0: return $aux[0];
            case 1: return $aux2[2].$separator.$aux2[1].$separator.$aux2[0];
            case 2: return $aux2[2].$separator.self::int2month($aux2[1]).$separator.$aux2[0];
            case 3: return $aux2[2]." de ".self::int2month($aux2[1])." de ".$aux2[0];
            case 4: return $aux2[2].$separator.$aux2[1].$separator.$aux2[0]." ".$aux[1];
            case 5: return $aux2[2].$separator.$aux2[1].$separator.$aux2[0]." ".substr($aux[1],0,-3);
            case 6: return substr($aux[1],0,-3);
        }
    }
    
    /**
     * Function that splits given date into pieces
     * 
     * @param string $date Date to be splitted
     * @return array Date pieces
     */
    public static function date_split($date)
    {
	$aux = explode(" ",$date);
	$aux2 = explode("-",$aux[0]);
	$aux22 = explode(":",$aux[1]);
	return $aux2;
    }
    
    /**
     * Function that reverses given date, commonly retrieved from database
     * 
     * @param string $date Date to be reversed
     * @param character $separator Character to be put between date's pieces
     * @param character $db_separator Separator from database's date
     * @return string Reversed date
     */
    public static function date_reverse($date,$separator = "/",$db_separator = "-"){
        $aux = explode($separator, $date);
        $final_date = $aux[2].$db_separator.$aux[1].$db_separator.$aux[0];
        return $final_date;
    }
    
    /**
     * Function that returns last nth months from current date (Ex. 3 = march,february,january)
     * 
     * @param integer $num Number of months to retrieve
     * @param boolean $compare Include current month or not
     * @param character $sort_labels_separator
     * @return array Last nth months from current date
     */
    public function last_n_months($num,$compare = false,$sort_labels_separator = "/"){
        $result = array();
        $date = getdate();
        $c_month = $date['mon'];
        $f_month = ($compare)?12-$c_month+(12-$num):(12-$c_month)+1+(12-$num);
        $year = $date['year'];

        for($i=$f_month;$i<=12;$i++){
            $tmp = self::int2month($i);
            $aux = array("label" => $tmp."/".($year-1),"short_label" => substr($tmp,0,3).$sort_labels_separator.substr($year-1,2,2),"month" => $i,"month_name" => $tmp,"year" => ($year-1));
            $result[] = $aux;
        }

        for($i=1;$i<=$c_month;$i++){
            $tmp = self::int2month($i);
            $aux = array("label" => $tmp."/".$year,"short_label" => substr($tmp,0,3).$sort_labels_separator.substr($year,2,2),"month" => $i,"month_name" => $tmp,"year" => $year);
            $result[] = $aux;
        }
        
        return $result;
    }
    
    
    /**
     * Function similar to urlencode oriented to encode phrase to url, lowering letters and changing whitespaces to underscores
     * 
     * @param string $str String to be converter
     * @return string Converted string
     */
    public static function url_encode($str){
        $str=trim($str);
        $str=strtolower($str);
        $str=str_replace(" ", "_", $str);
        return $str;
    }
    
    /**
     * Function similar to urlencode oriented to decode phrase from url, lowering letters and changing underscores to whitespaces
     * 
     * @param string $str String to be converter
     * @return string Converted string
     */
    public static function url_decode($str){
        $str=trim($str);
        $str=strtolower($str);
        $str=str_replace("_", " ", $str);
        return $str;
    }
    
    /**
     * Function that capitalizes naturally a given string
     * 
     * @param string $string Phrase to be capitalized
     * @return string Capitalized string
     */
    public static function capitalize($string){
        return ucfirst(strtolower($string));
    }
    
    
    /**
     * Function that geolocates a given IP
     * 
     * @param string $ip IP direction to be geolocated 
     * @return string IP location o default message if not located
     */
    public function locate_ip($ip = "8.8.8.8"){
        $def_location = 'Ubicación desconocida';
        $def_ip = "8.8.8.8";
        if ($ip == '127.0.0.1' || $ip == 'localhost'){$this->container->__warn("La dirección IP es local: usando dirección IP por defecto ($def_ip) [".__METHOD__.",".__LINE__."]");$ip = $def_ip;}
        //$url = 'http://ipinfodb.com/ip_locator.php?ip=' . urlencode($ip);
        $url = "http://api.hostip.info/get_html.php?ip=".urlencode($ip);
        $curlopt_useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6 (.NET CLR 3.5.30729)';

        $ch = curl_init();
        $curl_opt = array(
            CURLOPT_HEADER      => 0,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_USERAGENT   => $curlopt_useragent,
            CURLOPT_URL       => $url,
            CURLOPT_TIMEOUT         => 1,
            CURLOPT_REFERER         => 'http://' . $_SERVER['HTTP_HOST'],
        );
        curl_setopt_array($ch, $curl_opt);
        $content = curl_exec($ch);
        if($content === false){$this->container->__error("No ha sido posible recuperar la localización de la dirección IP: CURL return FALSE on url ($url) [".__METHOD__.",".__LINE__."]");return false;}
        $curl_info = curl_getinfo($ch);
        curl_close($ch);

        //if ( preg_match('{<li>City : ([^<]*)</li>}i', $content, $regs) )  {$city = $regs[1];}
        //if ( preg_match('{<li>State/Province : ([^<]*)</li>}i', $content, $regs) )  {$state = $regs[1];}
        $lines = explode("\n",$content);
        $state = str_replace("Country: ","",$lines[0]);
        $city = str_replace("City: ","",$lines[1]);

        if( $city!='' && $state!='' ){return self::capitalize($city) . ', ' . self::capitalize($state);}
        else{return $def_location;}
    }
    
    /**
     * Function for PHP CLI. Re-draws a progress bar into unix console each iteration
     * 
     * @param integer $done Number of elements done from total array
     * @param integer $total Total number of elements represented by progress bar
     * @param integer $max_elements Max displayed segments in progress bar over unix console (Ex. 50)
     * @param string $lang Language abbr to display progress bar (Ops. EN|ES)
     * @return boolean True if progress bar at 100% or displays progress bar on iteration
     */
    public function progress_bar($done, $total, $max_elements = 50,$lang = "EN") {
        if($done > $total) return true;
        if(is_null($this->progress_bar_start)){$this->progress_bar_start = microtime(true);$this->progress_bar = "\n";}
        if(is_null($this->progress_bar_prev)){$this->progress_bar_prev = microtime(true);}
        
        $percentage = (float)(($done*100)/$total);
        $num_elements = floor(($max_elements*$percentage)/100);

        $this->progress_bar = "\033[1000D$done/$total [\033[42;1;32m";
        $this->progress_bar .= str_repeat("=", $num_elements);
        if($num_elements < $max_elements){$this->progress_bar .= "\033[0m>";$this->progress_bar .= str_repeat("_", ($max_elements-$num_elements)-1);}
        else{$this->progress_bar .= "=\033[0m";}

        $this->progress_bar .= "] ".round($percentage)."% ";

        $elapsed_time = microtime(true)-$this->progress_bar_start;
        $estimated_total_time = ($total*$elapsed_time)/$done;        
        $remaining_time = $estimated_total_time-$elapsed_time;
        $step_time = number_format(microtime(true)-$this->progress_bar_prev,4);
        $this->progress_bar_prev = microtime(true);
        $average_time = $elapsed_time/$done;

        switch($lang){
            case "ES":
                $this->progress_bar .= "Restante: ".gmdate('H:i:s',$remaining_time).", t/el: ".$step_time."s, transcurrido: ".gmdate('H:i:s',$elapsed_time)."/".gmdate('H:i:s',$estimated_total_time).")";
                break;
            default:
                // Default Lang: EN
                $this->progress_bar .= "Remaining time: ".gmdate('H:i:s',$remaining_time).", t/el: ".$step_time."s, elapsed: ".gmdate('H:i:s',$elapsed_time)."/".gmdate('H:i:s',$estimated_total_time).")";
        }
        echo $this->progress_bar;

        flush();
    }
    
    /**
     * Function that generates breadcrumbs microformat menu
     * 
     * @param array $links Set of links to show (crumbs)
     * @param boolean $display Echo the results of function or return it
     * @return none|string Result menu if $display is false 
     */
    public function showBreadcrumbs($links, $display = true){
        $html = '<ul id="breadcrumbs" class="breadcrumbs clearfix">'."\n".'<li itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><a href="/" itemprop="url"><span itemprop="title">Home</span></a></li>'."\n";
        foreach($links as $crumb){
            $html .= '<li class="separator"></li>'."\n";
            if(is_array($crumb)){$html .= '<li itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><a href="'.$crumb['url'].'" itemprop="url"><span itemprop="title">'.$crumb['title'].'</span></a></li>'."\n";}
            else{$html .= '<li itemscope itemtype="http://data-vocabulary.org/Breadcrumb"><span itemprop="breadcrumb">'.$crumb.'</span></li>'."\n";}
        }
        $html .= '</ul>'."\n";
        if($display){
            echo $html;
        }else{
            return $html;
        }
    }
}