<?php
    
    class OneDB_Query_Parser extends Object {
        
        private $_server = NULL;
        private $_query  = NULL;
        
        public function init( OneDB_Client $server, array $query ) {
            $this->_server = $server;
            $this->_query  = $query;
        }
        
        private function transform( $object, $level = 0 ) {
            
            if ( $level > 5 )
                throw Object( 'Exception.OneDB', "Query too complicated or recursion detected!" );
            
            if ( !is_array( $object ) )
                return $object;
            
            // Treat magic $id query field
            if ( array_key_exists( '$id', $object ) ) {

                $object[ '_id' ] = $object[ '$id' ] !== NULL
                    ? new MongoId( $object['$id'] )
                    : NULL;

                unset( $object[ '$id' ] );
            }

            // Treat magic $parent query field
            if ( array_key_exists( '$parent', $object ) ) {

                $object[ '_parent' ] = $object[ '$parent' ] !== NULL
                    ? new MongoId( $object[ '$parent' ] )
                    : NULL;

                unset( $object[ '$parent' ] );

            }
            
            // Treat magic $childOf query field
            if ( $level == 0 && array_key_exists( '$childOf', $object ) ) {
            
                switch ( TRUE ) {
                    
                    case is_string( $object['$childOf'] ):
                    
                        $item = $this->_server->getElementByPath( $object['$childOf'] );

                        if ( $item === NULL )
                            throw Object('Exception.OneDB', 'Object in magic query field $childOf was not found!' );

                        //echo "url: " . $item->url . "\n";
                        $expr = new MongoRegex( '/^' .
                            addcslashes( $item->url, '*/\\.#{}+?%()^:' ) . // ' > mc bug
                            '([^*]+)/'
                        );

                        $object[ 'url' ] = $expr;

                        unset( $object['$childOf'] );

                        break;
                    
                    case is_array( $object['$childOf'] ):
                        
                        if ( !count( $object['$childOf'] ) )
                            throw Object( 'Exception.OneDB', 'No paths defined in magic query field $childOf' );
                        
                        $where = [];
                        
                        foreach ( $object['$childOf'] as $path ) {
                            
                            if ( !is_string( $path ) )
                                throw Object( 'Exception.OneDB', 'The paths defined in magic query field $childOf should be of type string' );
                            
                            $item = $this->_server->getElementByPath( $path );
                            
                            if ( $item === NULL )
                                throw Object( 'Exception.OneDB', 'Path ' . $path . ' provided in magic query field $childOf was not found!' );
                            
                            $where[] = [ 'url' => new MongoRegex( '/^' .
                                addcslashes( $item->url, '*/\\.#{}+?%()^:' ) . 
                                '([^*]+)/' 
                            ) ];
                            
                        }
                        
                        $object[ '$or' ] = $where;
                        
                        unset( $object['$childOf'] );
                        
                        break;
                    
                    default:
                        
                        throw Object('Exception.OneDB', 'Bad value provided for magic operator $childOf: ' . json_encode( $object['$childOf'] ) );
                        break;
                }
            }

            // Treat '...' => '$func' magic query fields
            foreach ( array_keys( $object ) as $queryField ) {

                if ( is_string( $object[ $queryField ] ) && preg_match( '/^\$func\:([a-zA-Z_\d]+)(\:([^*]+))?$/', $object[ $queryField ], $matches ) ) {

                    $functionName = $matches[1];
                    $functionArgs = isset( $matches[3] ) ? @json_decode( $matches[3] ) : [];

                    if ( !is_array( $functionArgs ) )
                        throw Object('Exception.OneDB', 'Invalid magic $func field usage!' );

                    if ( !function_exists( $functionName ) )
                        throw Object('Exception.OneDB', 'Function "' . $functionName . '" does not exist!' );

                    $object[ $queryField ] = call_user_func_array( $functionName, $functionArgs );
                } else
                
                // process subb
                if ( is_array( $object[ $queryField ] ) )
                    $object[ $queryField ] = $this->transform( $object[ $queryField ], $level + 1 );
            }

            return $object;
            
        }
    }
    
    OneDB_Query_Parser::prototype()->defineProperty( "compile", [
        "get" => function() {
            return $this->transform( $this->_query );
        }
    ] );
    
?>