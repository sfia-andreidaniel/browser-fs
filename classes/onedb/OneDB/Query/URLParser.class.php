<?php
    
    class OneDB_Query_URLParser extends Object {
        
        private $_query  = NULL;
        
        public function init( array $query ) {
            $this->_query  = $query;
        }
        
        private function transform( $object, $level = 0 ) {
            
            if ( $level > 5 )
                throw Object( 'Exception.OneDB', "Query too complicated or recursion detected!" );
            
            if ( !is_array( $object ) )
                return $object;
            
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
    
    OneDB_Query_URLParser::prototype()->defineProperty( "encodeAsGet", [
        "get" => function() {
            
            $result = $this->transform( $this->_query );
            
            $out = [];
            
            foreach ( array_keys( $result ) as $key )
                $out[] = ( urlencode( $key ) .  '=' . urlencode( $result[ $key ] . '' ) );
            
            return count( $out ) ? ( '?' . implode( '&', $out ) ) : '';
        }
    ] );
    
    OneDB_Query_URLParser::prototype()->defineProperty( "encodeAsPost", [
        "get" => function() {
            
            $result = $this->transform( $this->_query );
            
            return $result;
        
        }
    ] );
    
    
?>