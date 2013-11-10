<?php

    require "Method.class.php";

    class Property {
        public $get        = NULL;
        public $set        = NULL;
        public $enumerable = FALSE;
        
        private $_thisArg = NULL;
        
        public function __construct( $propertyConfig ) {
        
            $this->_thisArg = $this;
        
            if (!is_array( $propertyConfig ) )
                throw new Exception("Cannot create property, as it's config is not an array!");
            
            if ( isset( $propertyConfig['get'] ) ) {
                
                if (!is_callable( $propertyConfig['get'] ) )
                    throw new Exception("Getter is not callable!");
                
                $this->get = new Method( $propertyConfig[ 'get' ] );
            }
            
            if ( isset( $propertyConfig['set'] ) ) {
                
                if (!is_callable( $propertyConfig['set'] ) )
                    throw new Exception("Setter is not callable!");
                
                $this->set = new Method( $propertyConfig['set'] );
            }
            
            if ( isset( $propertyConfig['enumerable'] ) )
                $this->enumerable = $propertyConfig['enumerable'] ? TRUE : FALSE;
        }
    }

?>