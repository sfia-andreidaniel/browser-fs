<?php
    
    define( 'DEMUX_ENSURE_INSTANCE',  'INSTANCE' );
    define( 'DEMUX_ENSURE_ARRAY',     'ARRAY' );
    define( 'DEMUX_ENSURE_OBJECT',    'OBJECT' );
    define( 'DEMUX_ENSURE_PRIMITIVE', 'PRIMITIVE' );
    
    class RPC_Demuxer {
        
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
        
        public function ensure_class_loaded( $className ) {
            
            $classTestName = preg_replace( '/[\.\\_]+/', '_', $className );
            
            if ( !class_exists( $classTestName ) ) {
                
                $guessClassPath = @realpath( __DIR__ . '/../' . preg_replace( '/[\._]+/', '/', $className ) . '.class.php' );
                
                if ( empty( $guessClassPath ) )
                    throw Object( 'Exception.RPC', "The class " . $classTestName . " was not found on disk ( assumed location = " . __DIR__ . '/../' . preg_replace( '/[\._]+/', '/', $className ) . ".class.php )" );
                
                require_once $guessClassPath;
                
                if ( !class_exists( $classTestName ) )
                    throw Object( 'Exception.RPC', "The class " . $classTestName . " was not found defined in it's destination file ( file = " . __DIR__ . '/../' . preg_replace( '/[\._]+/', '/', $className ) . ".class.php )" );
            }
            
            return $classTestName;
        }
        
        // Instantiates a snapshoted data from the browser locally.
        public function demux( $mixed, $flag = NULL ) {
            
            if ( $flag !== NULL && !in_array( $flag, [ DEMUX_ENSURE_PRIMITIVE, DEMUX_ENSURE_ARRAY, DEMUX_ENSURE_OBJECT, DEMUX_ENSURE_INSTANCE ] ) )
                throw Object('Exception.RPC', "Bad demuxer flag value: " . json_encode( $flag ) );
            
            switch ( TRUE ) {
                
                case $this->is_primitive_type( $mixed ):
                
                    if ( $flag != NULL && $flag != DEMUX_ENSURE_PRIMITIVE )
                        throw Object( 'Exception.RPC', 'The demuxed data was NOT supposed to be a primitive, but a ' . $flag );
    
                    return $mixed;
                    
                    break;
                
                case is_array( $mixed ):
                    
                    $type = isset( $mixed[ 'type' ] )
                        ? $mixed[ 'type' ]
                        : "";
                    
                    if ( !is_string( $type ) || empty( $type ) )
                        throw Object( 'Exception.RPC', "The 'type' field of the muxed data was expected to be a non-empty string!" );
                    
                    $v = isset( $mixed[ 'v' ] )
                        ? $mixed[ 'v' ]
                        : Base_Undefined::create();
                    
                    if ( Base_Undefined::is( $v ) )
                        throw Object( 'Exception.RPC', "The 'v' field of the muxed data is unset!" );
                    
                    switch ( TRUE ) {
                        
                        case $type == 'window.Array':
                            // Good, we demux an array
                            
                            if ( $flag != NULL && $flag != DEMUX_ENSURE_ARRAY )
                                throw Object( 'Exception.RPC', 'The demuxed data was NOT supposed to be a native array, but a ' . $flag );
                            
                            if ( !is_array( $v ) )
                                throw Object( 'Exception.RPC', "The 'v' field was expected to be an array ( demux window.Array )" );
                            
                            for ( $i=0, $len = count( $v ); $i<$len; $i++ )
                                $v[$i] = $this->demux( $v[$i] );
                            
                            return $v;
                            
                            break;
                        
                        case $type == 'window.Object':
                            // Good, we demux an object
                            
                            if ( $flag != NULL && $flag != DEMUX_ENSURE_OBJECT )
                                throw Object( 'Exception.RPC', 'The demuxed data was NOT supposed to be an object, but a ' . $flag );
                            
                            if ( !is_array( $v ) )
                                throw Object( 'Exception.RPC', "The 'v' field was expected to be an object ( demux window.Object )" );
                            
                            foreach ( array_keys( $v ) as $key )
                                $v[ $key ] = $this->demux( $v[ $key ] );
                            
                            return $v;
                            
                            break;
                        
                        default:
                            
                            if ( $flag != NULL && $flag != DEMUX_ENSURE_INSTANCE )
                                throw Object( 'Exception.RPC', 'The demuxed data was NOT supposed to be an instance, but an ' . $flag );
                            
                            // We try to demux a class instance snapshot
                            
                            // the class name of the instance is found in "type"
                            
                            try {
                                
                                $className = $this->ensure_class_loaded( $type );
                                
                                $reflection = new ReflectionClass( $className );
                                
                                if ( !$reflection->implementsInterface( 'IDemuxable' ) )
                                    throw Object( 'Exception.RPC', "The class '$className' is not a demuxable one!" );
                                
                                return $className::__demux( $this->demux( $v ) );
                                
                            } catch ( Exception $e ) {
                                throw Object( 'Exception.RPC', "Failed to demux class instance '$className'", 0, $e );
                            }
                            
                            break;
                        
                    }
                    
                    break;
                    
                default:
                    throw Object('Exception.RPC', "Don't know how to demux structure: " . json_encode( $mixed ) );
                    break;
            }
            
        }
        
    }
    
    
    
?>