<?php

    class Exception_OneDB_Iterator extends Exception {
        
        public function init( $message, $code = 0, $previous = NULL ) {
            parent::__construct( $message, $code, $previous );
        }
        
    }

?>