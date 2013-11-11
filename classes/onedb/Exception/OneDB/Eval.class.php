<?php

    class Exception_OneDB_Eval extends Exception {
        
        public $value = NULL;
        
        public function init( $value = NULL ) {
            
            $this->value = $value;
            
            parent::__construct();
        }
        
    }

?>