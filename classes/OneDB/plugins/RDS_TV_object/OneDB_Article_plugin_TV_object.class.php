<?php

    class OneDB_Article_plugin_TV_object {
    
        protected $_ = NULL;

        public function __construct( &$that ) {
            $this->_ = $that;
        }
        
        public function __get( $propertyName ) {
            return $this->_->{$propertyName};
        }
        
        public function __set( $propertyName, $propertyValue ) {
            $this->_->{$propertyName} = $propertyValue;
        }
        
        public function __call( $methodName, $args ) {
            if (!method_exists( $this, $methodName ))
                return call_user_func_array( array( $this->_, $methodName ), $args );
            else 
                return call_user_func_array( array( $this, $methodName ), $args );
        }
        
    }

?>