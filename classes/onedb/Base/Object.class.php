<?php

    require_once dirname(__FILE__) . "/../@/ListenerInterface.trait.php";

    /* Nitro object */

    class Base_Object extends Object {
        
        use ListenerInterface;
        
        static public function primitive( $value, $parent = NULL ) {
            $v = NULL;
            switch ( TRUE ) {
                case is_string( $value ):
                    $v = ( new Base_String() )->init( $value );
                    break;

                case is_bool( $value ) :
                case is_float( $value ) :
                case is_int( $value ) :

                case $value === NULL;
                    $v = $value;
                    break;

                case is_array( $value ):
                    /* Is the value a standard array? */
                    
                    if ( !self::isAssoc( $value ) )
                        $v = ( new Base_Array() )->init( array_keys( $value ), $parent );
                    else {
                        $tmp = new Base_Array();
                        foreach ( array_keys( $value ) as $key ) {
                            $tmp[ $key ] = $value[ $key ];
                        }
                        $v = $tmp;
                    }
                    break;
                
                case is_object( $value ):
                    
                    $v = ( new Base_Array() )->init( $value );
                    $v->__setParentObject( $parent );
                    
                    break;

                default:
                    return NULL;
            }
            
            return $v;
        }
        
        public function init( $from = NULL, $_parentNode = NULL ) {
            
            $this->__setParentObject( $_parentNode );
            
            switch ( TRUE ) {
                
                case is_array( $from ): 
                    foreach ( array_keys( $from ) as $property )
                        $this->{"$property"} = $from[ $property ];
                    
                    return $this;
                    break;
                
                case is_object( $from ):

                    $vars = [];

                    if ( $from instanceof Object ) {
                        foreach ( $from as $key=>$value ) {
                            if ( $from->hasOwnProperty( $key ) )
                                $vars[ $key ] = $from[ $value ];
                        }
                    } else {
                        $vars = get_object_vars( $from );
                        if ( empty( $vars ) )
                            $vars = [];
                    }
                    
                    foreach ( array_keys( $vars ) as $var ) {
                        $this->{"$var"} = $vars[ $var ];
                    }
                    
                    return $this;
                    break;
                
                default:
                    return $this;
                    break;

            }

        }
        
        public static function isAssoc( $arr ) {
            return count( $arr ) && array_keys($arr) !== range(0, count($arr) - 1);
        }
        
        public function __set( $property, $value ) {
            
            $v = self::primitive( $value, $this );
            
            $this->on('change', $property, array(
                'value' => $value,
                'details' => 'set'
            ) );

            if ( is_object( $v ) ) {
                $v->__setObjectName( $property );
                $v->__setParentObject( $this );
            }
            
            parent::__set( $property, $v );
        }
    }

?>