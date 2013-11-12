<?php
    
    class Exception_RPC extends Exception {
        
        public function init( $message, $code = 0, $previous = NULL ) {
            
            parent::__construct( $message, $code = 0, $previous = NULL );
            
        }
        
    }
    
?>