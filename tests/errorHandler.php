<?php
namespace ITE;

trait errorHandler
{
    private $errors;
    private $verbose;
    private $test_name;
    
    public function initErrorHandler($verbose = false){
        $this->errors = array();
        $this->verbose = $verbose;
        $this->test_name = $this->getName();
        set_error_handler(array($this, "errorHandler"));
    }
   
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
        $this->errors[] = compact("errno", "errstr", "errfile","errline", "errcontext");
    }
    
    public function assertError($errstr, $errno) {
        if(empty($errstr)){$this->fail('Assert error failed, error string is empty.');}
        if(is_int($errno) === false){$this->fail('Assert error failed, error number is not an integer.');}
        
        foreach ($this->errors as $error) {
            if ($error["errstr"] === $errstr && $error["errno"] === $errno) {
                return;
            }
        }
        
        if($this->verbose){
            $this->fail("Error with level $errno and message '$errstr' not found in errors list.");
        }else{
            $this->fail("Error with level $errno and message '$errstr' not found in errors list (active verbose mode to show list).");
        }
    }
    
    public function getErrors(){
        if(count($this->errors) >= 1 && $this->verbose !== false){
            $output = "\n".'Handled errors for test \''.$this->test_name.'\':'."\n";
            
            foreach($this->errors as $error){
                $output .= "\t+ (".$error['errno'].') '.$error['errstr'].' [file: '.$error['errfile'].', line: '.$error['errline'].']'."\n";
            }
            
            return $output;
        }elseif(count($this->errors) >= 1 && $this->verbose === false){
            return "\n".count($this->errors).' Errors'."\n";
        }else{
            return false;
        }
    }
    
    static function tearDownAfterClass(){
        echo "\n\033[37m\033[44m".' Tests errors handled, active verbose output if are not displayed. '."\033[0m";
    }
}