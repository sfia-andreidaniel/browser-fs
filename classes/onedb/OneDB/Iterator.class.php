<?php

    require_once __DIR__ . '/Iterator/Void.class.php';

    class OneDB_Iterator extends Object {
        
        private $_values = [];
        
        public function init( $data = NULL ) {
            
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
            
            return Object( 'OneDB.Iterator', $out );
            
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
                
                return Object( 'OneDB.Iterator', $out );
            }
        }
        
        public function reverse() {
            if ( count( $this->_values ) )
                return Object( 'OneDB.Iterator', array_reverse( $this->_values ) );
            else
                return $this;
        }
        
        public function skip( $howMany ) {
            
            if ( !count( $this->_values ) )
                return $this;
            else
                return Object( 'OneDB.Iterator', array_slice( $this->_values, $howMany ) );
        }
        
        public function limit( $howMany ) {
            
            if ( !count( $this->_values ) )
                return $this;
            else
                return Object( 'OneDB.Iterator', array_slice( $this->values, 0, $howMany ) );
            
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
            
                    return Object( 'OneDB.Iterator', $out );
                    
                    break;
                
                case $myLen == 0:
                    return $resultSet;
                    break;
                
                case $hisLen == 0:
                    return $this;
                    break;
            
            }
        
        }
        
        public function continueIf( $boolOrCallback ) {
            
            switch ( TRUE ) {
                
                case is_callable( $boolOrCallback ):
                    if ( $boolOrCallback() )
                        return $this;
                    else
                        return new OneDB_Iterator_Void();
                    break;
                
                default:
                    
                    if ( empty( $boolOrCallback ) )
                        return new OneDB_Iterator_Void();
                    else
                        return $this;
                    
                    break;
            }
            
        }
        
    }
    
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