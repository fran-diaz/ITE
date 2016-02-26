<?php
namespace ITE;

/**
 * Assistance class to some CSS3 methods with variations over some browsers
 * 
 * @copyright   Copyright © 2007-2014 Fran Díaz
 * @author      Fran Díaz <fran.diaz.gonzalez@gmail.com>
 * @license     http://opensource.org/licenses/MIT
 * @package     ITE
 * @access      public
 * 
 */
class css {
    public $container;
    
    public function __construct($container) {
        $this->container = $container;
    }
    
    /**
     * Function that generates CSS code to linear gradient
     * 
     * @param string $c1 First color of gradient (top), must be in 6 letters format (#FFFFFF).
     * @param string $c2 Second color of gradient (bottom), must be 6 letters format (#FFFFFF).
     * @param string $img File name of image to represent as background additional to linear gradient
     * @return string CSS3 code to output linear gradient
     */
    public static function linear_gradient($c1,$c2,$img = null){
        $img_code = ($img != null)?"url($img),":"";
        return "background: $img_code -moz-linear-gradient($c1, $c2) ;background: $img_code -ms-linear-gradient($c1, $c2);background: $img_code -webkit-gradient(linear, left top, left bottom, color-stop(0%, $c1), color-stop(100%, $c2));background: $img_code -webkit-linear-gradient($c1, $c2);background: -o-linear-gradient($c1, $c2);filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='$c1', endColorstr='$c2',GradientType=0 );background: $img_code linear-gradient($c1, $c2);";
    }
    
    /**
     * Function that generates CSS code to border radius Accepts 1 param or 4 param
     * 
     * @param integer $r_si Top left border radius. If only this param is submited, the other params will be the same as this.
     * @param integer $r_sd Top right border radius
     * @param integer $r_id Bottom right border radius
     * @param integer $r_ii Bottom left border radius
     * @return string CSS3 code to output linear gradient
     */
    public static function border_radius($r_si = 0,$r_sd = 0,$r_id = 0,$r_ii = 0){
        $num = func_num_args();
        if($num == 4){
            return "-webkit-border-top-left-radius: ".$r_si."px;-webkit-border-top-right-radius: ".$r_sd."px;-webkit-border-bottom-right-radius: ".$r_id."px;-webkit-border-bottom-left-radius: ".$r_ii."px;-moz-border-radius-topleft: ".$r_si."px;-moz-border-radius-topright: ".$r_sd."px;-moz-border-radius-bottomright: ".$r_id."px;-moz-border-radius-bottomleft: ".$r_ii."px;border-top-left-radius: ".$r_si."px;border-top-right-radius: ".$r_sd."px;border-bottom-right-radius: ".$r_id."px;border-bottom-left-radius: ".$r_ii."px;";
        }else{
            return "-webkit-border-radius: ".$r_si."px;-moz-border-radius: ".$r_si."px;border-radius: ".$r_si."px;";        
        }
    }
    
    /**
     * Function that generates alternatives to calc method for achieve other browsers specs.
     * 
     * @param string $property CSS3 property
     * @param string $exp CSS3 Relative calculation expression (Ex. 100% - 32px)
     * @return string CSS3 code to output calculation
     */
    public static function calc($property = "width",$exp = "100"){
        return "$property:calc($exp);$property:-moz-calc($exp);$property:-webkit-calc($exp);";
    }
    
    /**
     * Function that generates alternatives to box shadow method for achieve other browsers specs.
     * 
     * @param string $shadow CSS3 shadow expression (Ex. 1px 1px 1px rgba(0,0,0,.3))
     * @return string CSS3 code to output box shadow
     */
    public static function box_shadow($shadow = ""){
        return "-moz-box-shadow: $shadow;-webkit-box-shadow: $shadow;box-shadow: $shadow;";
    }
    
    /**
     * Function that generates alternatives to transition duration method for achieve other browsers specs.
     * 
     * @param string $dur Duration of transition (Ex. 0.2s)
     * @return string CSS3 code to output transition duration
     */
    public function transition_duration($dur = "0.2s"){
        return "-webkit-transition-duration: $dur;-moz-transition-duration: $dur;transition-duration: $dur;";
    }
    
    /**
     * Function that generates alternatives to transform method for achieve other browsers specs.
     * 
     * @param string $type Type of transform to generate (Ex. rotate)
     * @param string $angle Angle of generated transform (Ex. 0deg)
     * @return string CSS3 code to output transform
     */
    public static function transform($type = "rotate",$angle = "0deg"){
        return "-webkit-transform: $type($angle);-moz-transform: $type($angle);-ms-transform: $type($angle);-o-transform: $type($angle);transform: $type($angle);";
    }
    
    /**
     * Function that generates alternatives to transform origin method for achieve other browsers specs.
     * 
     * @param string $value Space reference to transform origin (Ex. 0 50%)
     * @return string CSS3 code to output transform origin
     */
    public static function transform_origin($value = "0 0"){
        return "-webkit-transform-origin: $value;-moz-transform-origin: $value;-ms-transform-origin: $value;-o-transform-origin: $value;transform-origin: $value;";
    }
    
    /**
     * Function that generates transition method for achieve other browsers specs.
     * 
     * @param string $value Transition expression (Ex. all 200ms ease)
     * @return string CSS3 code to output transition
     */
    public function transition($value = "all 200ms ease"){
        return "-webkit-transition: $value;-moz-transition: $value;-o-transition: $value;-ms-transition: $value;transition-duration: $value;";
    }
    
    /**
     * Function that generates filter method for achieve other browsers specs.
     * 
     * @param string $filter Type of filter to apply (Ex. greyscale)
     * @param string $value Filter expression (Ex. 100%)
     * @return string CSS3 code to output filter
     */
    public function filter($filter = "grayscale",$value = "100%"){
        return "-webkit-filter: $filter($value);-moz-filter: $filter($value);-o-filter: $filter($value);-ms-filter: $filter($value);filter: $filter($value);";
    }
}