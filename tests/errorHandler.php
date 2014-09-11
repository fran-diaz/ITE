<?php
namespace ITE;

trait errorHandler
{
    private $errors;
    private $verbose;
    
    public function initErrorHandler($verbose = false){
        $this->errors = array();
        $this->verbose = $verbose;
        set_error_handler(array($this, "errorHandler"));
    }
   
    public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
        $this->errors[] = compact("errno", "errstr", "errfile",
            "errline", "errcontext");
    }
    
    public function assertError($errstr, $errno) {
        foreach ($this->errors as $error) {
            if ($error["errstr"] === $errstr
                && $error["errno"] === $errno) {
                return;
            }
        }
        
        if($this->verbose){
            $this->fail("Error with level $errno and message '$errstr' not found in errors list: ".var_export($this->errors, TRUE));
        }else{
            $this->fail("Error with level $errno and message '$errstr' not found in errors list (active verbose mode to show list).");
        }
    }
}