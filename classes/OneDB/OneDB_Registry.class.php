<?php

    /* Class to be used under backend mode in order to
       modify various database-specific settings */

    class OneDB_Registry {
        
        protected $_onedb = NULL;
        protected $_properties = array(
        
        );
        
        public function __construct( &$OneDB ) {
            $this->_onedb = $OneDB;
            
            $cursor = $this->_onedb->db->config->find();
            
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                $this->_properties[ $row['name'] ] = @$row['value'];
            }
        }
        
        public function __get( $propertyName ) {
            return in_array( $propertyName, array_keys( $this->_properties ) ) ? $this->_properties[ $propertyName ] : NULL;
        }
        
        public function __set( $propertyName, $propertyValue ) {
            
            if (function_exists( 'policy_exists' ) && !policy_exists('OneDB_Gods'))
                throw new Exception("Not enough permissions!");
            
            $this->delete( $propertyName );
            
            $this->_onedb->db->config->insert( array(
                'name' => $propertyName,
                'value' => $propertyValue
            ), array(
                'upsert' => TRUE,
                'safe' => TRUE,
                'multiple' => FALSE
            ) );
            
            $this->_properties[ $propertyName ] = $propertyValue;
        }
        
        public function keys() {
            return array_keys( $this->_properties );
        }
        
        public function delete( $propertyName ) {
            
            if (function_exists( 'policy_exists' ) && !policy_exists('OneDB_Gods'))
                throw new Exception("Not enough permissions!");
            
            $this->_onedb->db->config->remove(
                array(
                    "name" => $propertyName
                ),
                array(
                    "safe" => TRUE,
                    "justOne" => TRUE
                )
            );
            
            if (isset( $this->_properties[ "$propertyName" ] ))
                unset( $this->_properties[ "$propertyName" ] );
        }
        
        public function all() {
            return $this->_properties;
        }
    
    }

?>