<?php
    
    /* A void iterator is an iterator that does nothing */
    
    require_once __DIR__ . '/../Iterator.class.php';
    
    class OneDB_Iterator_Void extends OneDB_Iterator {
        
        public function __call( $methodName, $methodArgs, $server = NULL ) {}

        public function __get ( $propertyName ) {
            return NULL;
        }

        public function __set( $propertyName, $propertyValue ) {}
        
    }

?>