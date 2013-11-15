<?php
    
    class Exception_Security extends Exception {
        
        public function init( $message, $code = 0, $previous = NULL ) {
            
            parent::__construct( $message, $code, $previous );
            
        }
        
    }
    
?>