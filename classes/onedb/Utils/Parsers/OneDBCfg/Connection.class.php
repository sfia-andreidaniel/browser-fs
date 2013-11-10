<?php

    class Utils_Parsers_OneDBCfg_Connection extends Object {
        
        private $_data = NULL;
        
        public function init( $data ) {
            
            $this->_data = $data;
            
        }
        
        public function __get( $propertyName ) {
            
            if ( !isset( $this->_data[ $propertyName ] ) )
                return NULL;
            else
                return $this->_data[ $propertyName ];
            
        }
        
    }

?>