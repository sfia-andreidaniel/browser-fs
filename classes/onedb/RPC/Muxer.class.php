<?php
    
    class RPC_Muxer {
        
        public function is_primitive_type( $data ) {
            
            return $data === NULL || is_bool( $data ) ||
                   is_int( $data ) || is_float( $data ) || is_string( $data );
            
        }
        
        public function is_composed_type( $data ) {
            
            return is_array( $data ) || ( is_object( $data ) && get_class( $data ) == 'stdClass' );
            
        }
        
        public function is_instantiated_type( $data ) {
            $className = get_class( $data );
            
            if ( $className && $className != 'stdClass' )
                return $className;
            
            return FALSE;
        }
        
        public function mux( $mixed ) {
            
            switch ( TRUE ) {
                
                case $this->is_primitive_type( $mixed ):
                    return $mixed;
                    break;
                
                case $this->is_composed_type( $mixed ):
                    
                    $out = [];

                    if ( is_array( $mixed ) ) {

                        $indexed = TRUE;
                    
                        foreach ( array_keys( $mixed ) as $key ) {
                            $out[ $key ] = $this->mux( $mixed[$key] );

                            if ( !is_int( $key ) )
                                $indexed = FALSE;
                        }

                        return [ 
                            'type'  => $indexed ? 'window.Array' : 'window.Object',
                            'v' => $indexed ? array_values( $out ) : $out
                        ];
                        
                    } else {
                        
                        // Is object

                        $out = [];

                        $keys = get_object_vars( $mixed );
                        
                        foreach ( $keys as $key )
                            $out[ $key ] = $this->mux( $mixed->{$key} );
                        
                        return [
                            'type' => 'window.Object',
                            'v' => $out
                        ];
                        
                    }
                    
                    
                    break;
                
                case ( $className = $this->is_instantiated_type( $mixed ) ) ? TRUE : FALSE:
                    
                    switch ( TRUE ) {
                        // If the class implements a __mux method, we return that method
                        case method_exists( $mixed, '__mux' ):
                            return [
                                'type' => $className,
                                'v'    => $mixed->__mux()
                            ];
                            break;
                        
                        case method_exists( $mixed, '__toString' ):
                            return [
                                'type' => $className,
                                'v'    => $mixed->__toString()
                            ];
                            break;
                        
                        default:
                            return [
                                'type' => $className,
                                'v'    => NULL
                            ];
                            break;
                    }
                    
                    break;
                
                default:
                    
                    //echo "Warning: Don't known how to mux!";
                    //var_dump( $mixed );
                    
                    return NULL;
                    break;
            }
            
        }
        
    }
    
    
    
?>