<?php

    require_once __DIR__ . '/../Category.class.php';

    class OneDB_Type_Category_Search extends OneDB_Type_Category {
        
        static public $_isReadOnly  = FALSE;
        static public $_isContainer = TRUE;
        
        protected $_query = [];
        
        public function exportOwnProperties( array &$properties ) {
            
            $properties[ 'query' ] = $this->_query;
            
        }
        
        public function importOwnProperties( array $properties ) {
            
            if ( isset( $properties[ 'query' ] ) && is_array( $properties['query'] ) )
                $this->_query = $properties['query'];
            
        }
        
        public function getChildNodes() {
            
            return count( $this->_query )
                ? $this->_root->server->find( $this->_query )
                : Object( 'OneDB.Iterator', [], $this->_root->server );
        
        }
        
    }
    
    OneDB_Type_Category_Search::prototype()->defineProperty( 'query', [
        
        "get" => function() {
            
            return $this->_query;
            
        },
        
        "set" => function( $findQuery ) {
            
            $this->_query = is_array( $findQuery )
                ? $findQuery
                : [];
            
            $this->_root->_change( 'query', $this->_query );
        }
        
    ] );

?>