<?php
    
    require_once __DIR__ . '/../Object.class.php';
    
    class OneDB_Type_Json extends OneDB_Type {
        
        static $_isContainer = FALSE;
        static $_isReadOnly  = FALSE;
        
        private $_fields = [];
        
        public function exportOwnProperties( array &$properties ) {
            
            foreach ( array_keys( $this->_fields ) as $key )
                if ( !in_array( $key, OneDB_Object::$_nativeProperties ) )
                    $properties[ $key ] = $this->_fields[ $key ];
            
        }
        
        public function importOwnProperties( $properties ) {
            
            foreach ( array_keys( $properties ) as $key )
                if ( !in_array( $key, OneDB_Object::$_nativeProperties ) )
                    $this->_fields[ $key ] = $properties[ $key ];
            
        }
        
        public function __get( $propertyName ) {
            
            if ( $propertyName == 'name' )
                return 'Json';
            else {
                
                if ( array_key_exists( $propertyName, $this->_fields ) )
                    return $this->_fields[ $propertyName ];
                else
                    return NULL;
                
            }
            
        }
        
        public function __set( $propertyName, $propertyValue ) {
            
            $this->_fields[ $propertyName ] = $propertyValue;
            
            $this->_root->_change( $propertyName, $propertyValue );
            
        }
        
        public function __mux() {
            return $this->_fields;
        }
        
    }
    
?>