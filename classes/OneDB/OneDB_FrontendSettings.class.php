<?php

    class OneDB_FrontendSettings {
        
        private $_item = NULL;
        
        public function __construct( &$MongoObject ) {
            $this->_item = $MongoObject;
        }
        
        public function getProperty( $propertyName, $recursive = TRUE, $defaultValue = NULL ) {
            $found = FALSE;
            
            $cursor = $this->_item;
            
            while ($cursor !== NULL ) {
                
                $frontend = $cursor->frontend;
                
                if (!is_array( $frontend ) ) {
                    
                } else {
                    if ( isset( $frontend[ $propertyName ] ) )
                        return $frontend[ $propertyName ];
                }
                
                $cursor = $cursor->_parent === NULL ? NULL : ( $recursive ? $cursor->getParent() : NULL );
            }
            
            return $defaultValue;
        }
        
        public function __get( $propertyName ) {
            return $this->getProperty( $propertyName, TRUE, NULL );
        }
    }

?>