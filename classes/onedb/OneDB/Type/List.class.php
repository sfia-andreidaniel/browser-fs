<?php

    require_once __DIR__ . '/../Type.class.php';
    require_once __DIR__ . '/../Iterator.class.php';

    class OneDB_Type_List extends OneDB_Type {
        
        protected $_items    = [];
        protected $_accept   = '';
        protected $_maxItems = 1;
        
        static protected $_isContainer = FALSE;
        static protected $_isLive      = FALSE;
        static protected $_isReadOnly  = FALSE;
        
        public function exportOwnProperties( array &$properties ) {
            
            $properties[ 'items' ]    = $this->_items;
            $properties[ 'accept']    = $this->_accept;
            $properties[ 'maxItems' ] = $this->_maxItems;
            
        }
        
        public function importOwnProperties( array $properties ) {
        
            $this->_items    = isset( $properties['items'] ) ? $properties['items'] : [];
            $this->_accept   = isset( $properties['accept']) ? $properties['accept']: '';
            $this->_maxItems = isset ( $properties['maxItems'] ) ? $properties['maxItems'] : 1;
            
        }
        
        public function __mux() {
            return [
                'accept' => $this->_accept,
                'maxItems' => $this->_maxItems
            ];
        }
        
        // Test if something is representing a OneDB_Object.
        // @param $mixed can be either <string>, either <OneDB_Object> with
        //        non-null ID.
        // @returns: <OneDB_Object> or NULL if item is not compatible.
        
        public function _compatible( $mixed ) {
            
            $result = NULL;
            
            switch ( TRUE ) {
                case !is_string( $mixed ) && !( $mixed instanceof MongoId ) && !( $mixed instanceof OneDB_Object ):
                    break;
                
                case is_string( $mixed ):
                    
                    if ( preg_match( '/^[a-f\d]{24}$/', $mixed ) )
                        $result = $this->_root->server->getElementById( new MongoId( $mixed ) );

                    break;
                
                case $mixed instanceof OneDB_Object:
                    
                    $id = $mixed->id;
                    
                    if ( $id )
                        $result = $mixed;
                    
                    break;
                
                case $mixed instanceof MongoId:
                    
                    $result = $this->_root->server->getElementById( $mixed );
                    
                    break;
            }
            
            if ( $result && strlen( $this->_accept ) ) {
                
                $result = preg_match( $this->_accept, $result->type )
                    ? $result
                    : NULL;
                
            }
            
            return $result;
            
        }
    }
    
    OneDB_Type_List::prototype()->defineProperty( 'items', [
        
        'get' => function() {
            
            if ( !count( $this->_items ) )
            
                return Object( 'OneDB.Iterator', [], $this->_root->server );
            
            else {
            
                $idList = [];
                
                for ( $i = 0, $len = count( $this->_items ); $i<$len; $i++ ) {
                    $idList[] = new MongoId( $this->_items[$i] );
                }
                
                return $this->_root->server->find( [
                    '_id' => [
                        '$in' => $idList
                    ]
                ], $this->_maxItems, [
                    
                    'created' => -1
                    
                ] );
            
            }
            
        },
        
        'set' => function( $list ) {
            
            $set = [];
            
            switch ( TRUE ) {
            
                case is_array( $list ):
                    
                    foreach ( $list as $item ) {
                        if ( $accept = $this->_compatible( $item ) )
                            $set[] = $accept->id->__toString();
                    }
                    
                    break;
                
                case $list instanceof OneDB_Iterator:
                
                    $me = $this;
                
                    $list->each( function( $item ) use (&$set, $me) {
                        
                        if ( $accept = $me->_compatible( $item ) )
                            $set[] = $accept->id->__toString();
                        
                    } );
                
                    break;
                
                default:
                    break;
            }
            
            $this->_items = $set;
            
            $this->_root->_change( 'items', $this->_items );
            
        }
        
    ] );
    
    OneDB_Type_List::prototype()->defineProperty( 'accept', [
        
        'get' => function() {
            return $this->_accept;
        },
        
        'set' => function( $regex ) {
            
            if ( !is_string( $regex ) )
                throw Object('Exception.OneDB', "The 'accept' property of a OneDB_Type_List should be a string containing a regular expression" );
            
            $this->_accept = $regex;
            
            $this->_root->_change( 'accept', $this->_accept );
        }
        
    ] );
    
    OneDB_Type_List::prototype()->defineProperty( 'maxItems', [
        
        'get' => function() {
            return $this->_maxItems;
        },
        
        'set' => function( $int ) {
        
            if ( !is_int( $int ) )
                throw Object( 'Exception.OneDB', "The property maxItems of a OneDB_Type_List should be of integer type" );
            
            if ( $int < 0 )
                throw Object( 'Exception.OneDB', "Negative values are not allowed for the properties OneDB_Type_List" );
            
            $this->_maxItems = $int;
            
            $this->_root->_change( 'maxItems', $this->_maxItems );
        }
        
    ] );

?>