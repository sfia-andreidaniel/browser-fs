<?php

    require_once __DIR__ . '/../Category.class.php';

    class OneDB_Type_Category_Aggregator extends OneDB_Type_Category {
        
        static public $_isReadOnly  = FALSE;
        static public $_isContainer = TRUE;

        protected $_paths    = [];
        protected $_maxItems = -1;

        public function exportOwnProperties( array &$properties ) {
            
            $properties[ 'paths' ]    = $this->_paths;
            $properties[ 'maxItems' ] = $this->_maxItems;
        }
        
        public function importOwnProperties( array $properties ) {
            
            $this->_paths = isset( $properties['paths'] )
                ? $properties['paths']
                : [];
            
            $this->_maxItems = isset( $properties['maxItems'] )
                ? $properties['maxItems']
                : -1;
        }
        
        public function getChildNodes() {
            
            $out = Object( 'OneDB.Iterator', [], $this->_root->server );
            
            $seenIDs = [];
            
            foreach ( $this->_paths as $path ) {
                
                $category = $this->_root->server->getElementByPath( $path );
                
                if ( $category ) {
                    
                    $category->childNodes->each( function( $item ) use ( &$seenIDs, &$out ) {
                        
                        if ( !isset( $seenIDs[ "$item->id" ] ) ) {
                            $out->add( $item );
                            $seenIDs[ "$item->id" ] = 1;
                        }
                        
                    } );
                    
                } else throw Object( 'Exception.OneDB', "Path '$path' of OneDB.Type.Category.Aggregator '" . $this->_root->url . "' was not found!" );
                
            }
            
            if ( $this->_maxItems >= 0 )
                $out = $out->limit( $this->_maxItems );
            
            return $out;
        }
        
    }
    
    OneDB_Type_Category_Aggregator::prototype()->defineProperty( 'paths', [
        "get" => function() {
            return $this->_paths;
        },
        "set" => function( $paths ) {
            
            if ( !is_array( $paths ) )
                throw Object( 'Exception.OneDB', "The paths property of a OneDB.Type.Category.Aggregator should be an array of strings!" );
            
            // Test to see if actually all paths are categories and if
            // all the paths exists
            
            $out = [];
            
            foreach ( $paths as $path ) {
                
                if ( !is_string( $path ) )
                    throw Object( 'Exception.OneDB', "All components of a path for OneDB.Type.Category.Aggregator should be of type string" );
                
                $path = trim( $path );
                
                if ( !strlen( $path ) )
                    throw Object( 'Exception.OneDB', "Empty paths are not allowed!" );
                
                if ( $path == '/' )
                    throw Object( 'Exception.OneDB', "The root cannot be aggregated" );
                
                $item = $this->_root->server->getElementByPath( $path );
                
                if ( !$item )
                    throw Object( 'Exception.OneDB', "The path $path was not found!" );
                
                if ( !$item->isContainer() )
                    throw Object( 'Exception.OneDB', "The path $path is not a container (a category)" );
                
                $out[] = $path;
                
            }
            
            $this->_paths = $out;
            
            $this->_root->_change( 'paths', $this->_paths );
        }
    ]);
    
    OneDB_Type_Category_Aggregator::prototype()->defineProperty( 'maxItems', [
        
        "get" => function() {
            return $this->_maxItems;
        },
        "set" => function( $howMany ) {
            
            $howMany = (int)$howMany;
            $howMany = $howMany < -1 ? -1 : $howMany;
            
            $this->_maxItems = $howMany;
            
            $this->_root->_change( 'maxItems', $this->_maxItems );
        }
        
    ] );

?>