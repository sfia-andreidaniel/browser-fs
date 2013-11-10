<?php

    require_once "String.class.php";

    define('NaN', 'NaN');

    class Base_Number extends Object {
    
        use ListenerInterface;
    
        protected $_value = 0;
        
        /* Initialize the primitive value of a string */
        public function init( $value ) {
            $this->_value = $value * 1;
            return $this;
        }
        
        /* The toFixed() method converts a number into a string, keeping a specified number of decimals */
        public function toFixed( $decimals = 0 ) {
            return ( new Base_String( ) )->init(
                number_format( $this->_value, $decimals, '.', '' )
            );
        }
        
        public function toPrecision( $precision = NULL ) {
            $out = abs( $this->_value ) . '';
            switch (TRUE) {
                case strpos( $out, '.' ) !== FALSE:
                    // number is decimal
                    if ( $precision !== NULL ) {

                        $out = substr( $out, 0, $precision + 1 );

                        if ( strpos( $out, '.' ) !== FALSE ) {

                            for ($i=strlen($out); $i < $precision; $i++ )
                                $out .= '0';

                        } else {
                            // Becamed an int
                            if ( strlen( $out ) < $precision ) {
                                $out .= '.';
                                for ( $i=strlen($out); $i<$precision; $i++)
                                    $out .= '0';
                            }
                        }
                    }
                    break;
                default:
                    // number is integer
                    if ( $precision !== NULL ) {
                        $out = substr( $out, 0, $precision );

                        if ( strlen( $out ) < $precision ) {
                            $out .= '.';
                            for ($i= strlen($out); $i<= $precision; $i++ )
                                $out .= '0';
                        }
                    }
                    break;
            }
            
            return (new Base_String() )->init( $this->_value >= 0 ? $out : "-$out" );
        }
        
        public function __toString() {
            return $this->_value . '';
        }
        
        public function toString() {
            return ( new Base_String() )->init( $this->__toString() );
        }
        
        /* Returns the primitive value of a String */
        public function valueOf() {
            return $this->_value;
        }
    }
    
    Prototype('Base_Number')->defineProperty( 'MIN_VALUE', [
        "get" => function(){
            return PHP_INT_MIN;
        },
        "set" => function() {
        }
    ] );

    Prototype('Base_Number')->defineProperty( 'MAX_VALUE', [
        "get" => function(){
            return PHP_INT_MAX;
        },
        "set" => function() {
        }
    ] );

?>