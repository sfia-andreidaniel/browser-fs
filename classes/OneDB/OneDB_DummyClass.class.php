<?php

    /* Dummy class in order to support chained instructions
       but to do nothing */

    class OneDB_DummyClass {
        
        function __construct() {
        }
        
        function __call( $methodName, $argumentsList ) {
            return $this;
        }
        
        function __get( $propertyName ) {
            return $this;
        }
        
        function __set( $propertyName, $propertyValue ) {
            return $propertyValue;
        }
        
    }

?>