<?php
    
    class Exception_IO extends Exception {
        
        public function init( $message, $code = 0, $previous = NULL ) {
            
            parent::__construct( $message, $code = 0, $previous = NULL );
            
        }
        
    }
    
?>