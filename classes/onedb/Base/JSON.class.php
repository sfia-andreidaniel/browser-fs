<?php

    require_once dirname(__FILE__) . "/Object.class.php";

    require_once "Number.class.php";
    require_once "String.class.php";
    require_once "Date.class.php";

    class Base_JSON extends Object {
        
        static private function get_primitive( $value ) {
            
            switch (TRUE) {
                
                case is_string( $value ):
                case is_bool( $value ):
                case is_int( $value ):
                case is_float( $value ):
                    return $value;
                    break;
                
                case is_array( $value ): {
                    
                    $out = array();
                    
                    foreach ( array_keys( $value ) as $key )
                        $out[ $key ] = self::get_primitive( $value[ $key ] );
                    
                    return $out;
                }
                
                case is_object( $value ):
                    
                    $out = array();
                    
                    if ( $value instanceof Object ) {
                        
                        if ( $value instanceof Base_String ||
                             $value instanceof Base_Number ||
                             $value instanceof Base_Date
                        ) return $value->valueOf( $value );
                        
                        $keys = $value->_keys();
                        
                        foreach ($keys as $key) {
                            if ( $value->hasOwnProperty( $key ) )
                                $out[ $key ] = self::get_primitive( $value[ $key ] );
                        }
                        
                    } else {
                        
                        $out = get_object_vars( $value );
                        
                        if ( empty( $out ) )
                            $out = [];
                        else
                            foreach ( array_keys( $out ) as $key )
                                $out[ $key ] = self::get_primitive( $value[ $key ] );
                    }
                    
                    return $out;
                
                default:
                    return $value;
                    break;
            }
            
        }
        
        static public function stringify( $value ) {
            return json_encode( self::get_primitive( $value ) );
        }
        
        static public function parse( $value ) {

            $data = @json_decode( $value, TRUE );
            
            switch ( TRUE ) {
                
                case is_array( $data ):
                    return ( new Base_Object() )->init( $data );
                    break;

                default:
                    return $data;
            }
        }
    }
    
    class JSON extends Base_JSON {}

?>