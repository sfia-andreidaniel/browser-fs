<?php

    class OneDB_Iterator extends Object {
        
        private $_values = [];
        private $_server = NULL;
        
        public static $_muxer = NULL;
        
        public function init( $data = NULL, OneDB_Client $server ) {
            
            $this->_server = $server;
            
            if ( is_array( $data ) )
                $this->_values = $data;
            else {
                if ( !empty( $data ) )
                    $this->_values = [ $data ];
            }
        }
        
        public function each( $callback ) {
            
            if ( !is_callable( $callback ) )
                throw Object( 'Exception.OneDB.Iterator', "The callback should be callable!" );
            
            for ( $i = 0, $len = count( $this->_values ); $i<$len; $i++ ) {
                
                if ( $callback( $this->_values[$i], $i, $this ) === FALSE )
                    break;
                
            }
            
            return $this;
            
        }
        
        public function here( $callback ) {
            
            if ( is_callable( $callback ) ) {
            
                $callback( $this );
            
            }
            
            return $this;
            
        }
        
        public function filter( $callback ) {
            
            $out = [];
            
            if ( is_callable( $callback ) ) {
                
                for( $i=0, $len = count( $this->_values ); $i<$len; $i++ ) {
                    
                    if ( $callback( $this->_values[$i] ) )
                        $out[] = $this->_values[$i];
                    
                }
                
            }
            
            return Object( 'OneDB.Iterator', $out, $this->_server );
            
        }
        
        public function sort( $callback ) {
            
            if ( !is_callable( $callback ) )
                return $this;
            else {
            
                $out = [];
                
                for ( $i = 0, $len = count( $this->_values ); $i<$len; $i++ ) {
                    $out[] = $this->_values[$i];
                }
                
                usort( $out, $callback );
                
                return Object( 'OneDB.Iterator', $out, $this->_server );
            }
        }
        
        public function reverse() {
            if ( count( $this->_values ) )
                return Object( 'OneDB.Iterator', array_reverse( $this->_values ), $this->_server );
            else
                return $this;
        }
        
        public function skip( $howMany ) {
            
            if ( !count( $this->_values ) )
                return $this;
            else
                return Object( 'OneDB.Iterator', array_slice( $this->_values, $howMany ), $this->_server );
        }
        
        public function limit( $howMany ) {
            
            if ( !count( $this->_values ) )
                return $this;
            else
                return Object( 'OneDB.Iterator', array_slice( $this->values, 0, $howMany ), $this->_server );
            
        }
        
        public function get( $index ) {
            
            if ( $index >= 0 ) {
                
                if ( $index < count( $this->_values ) )
                    return $this->_values[ $index ];
                else
                    throw Object( 'Exception.OneDB.Iterator', "Index range error $index [ 0.." . ( count( $this->_values ) - 1 ) . "]" );

            } else {
                
                $index = abs( $index ) - 1;
                
                if ( $index < ( $len = count( $this->_values ) ) )
                    return $this->_values[ $len - $index - 1 ];
                else
                    throw Object( 'Exception.OneDB.Iterator', "Index ( negative ) range error $index [ 0.." . ( $len - 1 ) . "]" );
                
            }
            
        }
        
        public function join( OneDB_Iterator $resultSet ) {
            
            $myLen  = count( $this->_values );
            $hisLen = $resultSet->length;
            
            switch ( TRUE ) {
                
                case $myLen > 0 && $hisLen > 0:
                    $out = [];
            
                    for ( $i=0, $len = $myLen; $i<$len; $i++ )
                        $out[] = $this->_values;
            
                    for ( $i=0, $len = $hisLen; $i<$len; $i++ )
                        $out[] = $resultSet->get( $i );
            
                    return Object( 'OneDB.Iterator', $out, $this->_server );
                    
                    break;
                
                case $myLen == 0:
                    return $resultSet;
                    break;
                
                default:
                    return $this;
                    break;
            
            }
        
        }
        
        public function add( $item ) {
            $this->_values[] = $item;
            return $this;
        }
        
        public function continueIf( $boolOrCallback ) {
            
            switch ( TRUE ) {
                
                case is_callable( $boolOrCallback ):
                    if ( $boolOrCallback() )
                        return $this;
                    else
                        return Object( 'OneDB.Iterator.Void', null, $this->_server );
                    break;
                
                default:
                    
                    if ( empty( $boolOrCallback ) )
                        return Object( 'OneDB.Iterator.Void', null, $this->_server );
                    else
                        return $this;
                    
                    break;
            }
            
        }
        
        // Performs a fast mongo search
        public function find( $query ) {
            
            $query = is_array( $query ) ? $query : [];
            
            if ( !count( $query ) )
                return $this;
            
            $seenIDs = [];
            $idList  = [];
            
            foreach ( $this->_values as $item ) {
                if ( !isset( $seenIDs[ "$item->id" ] ) ) {
                    $idList[] = $item->id;
                    $seenIDs[ "$item->id" ] = 1;
                }
            }
            
            $query[ '_id' ] = [
                '$in' => $idList
            ];
            
            //print_r( $query );
            
            return $this->_server->find( $query );
        }
        
        public function __mux() {
            
            $out = [];
            
            foreach ( $this->_values as $value ) {
                $out[] = self::$_muxer->mux( $value );
            }
            
            return [ $out, self::$_muxer->mux( $this->_server ) ];
        }
        
    }
    
    OneDB_Iterator::$_muxer = Object( 'RPC.Muxer' );
    
    OneDB_Iterator::prototype()->defineProperty( 'length', [
        "get" => function() {
            return count( $this->_values );
        }
    ] );
    
    OneDB_Iterator::prototype()->defineProperty( 'items', [
        "get" => function() {
            return $this->_values;
        }
    ] );

?>