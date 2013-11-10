<?php

    require_once "String.class.php";
    require_once "Number.class.php";

    class Base_RegExp extends Object {
    
        use ListenerInterface;
    
        protected $_modifiers = '';
        protected $_value     = '';
        
        public function __construct() {
        }
        
        /* Initialize the primitive value of a string */
        public function init( $expr = NULL, $modifiers = NULL ) {
            switch (TRUE) {
                case $expr === NULL:
                    $this->_value     = '(?:)';
                    $this->_modifiers = '';
                    break;
                case is_object( $expr ) && $expr instanceof Object:
                    $expr = $expr . '';
                case !is_string( $expr ):
                    $expr = "$expr";
                default:
                    if ( @preg_match( '/^\/(.*)\/([a-z]+)?$/i', $expr, $matches ) ) {
                        $this->_value = $matches[1];
                        $this->_modifiers = $matches[2];
                    } else {
                        $this->_value = $expr;
                        $this->_modifiers = '';
                    }
                    break;
            }
            
            $this->_modifiers = @preg_replace('/[^im]+/', '', $this->_modifiers );
            
            return $this;
        }
        
        public function test( $str ) {
            $str = is_string( $str ) ? $str : $str . '';
            return @preg_match( $this->__toString(), $str );
        }
        
        public function exec( $str ) {
            if ( @preg_match( $this->__toString(), $str . '', $matches ) ) {
                return (new Base_Array())->init( $matches );
            } else
                return NULL;
        }
        
        public function __toString() {
            return '/' . $this->_value . '/' . $this->_modifiers;
        }
        
        public function toString( ) {
            return ( new Base_String() )->init( $this->__toString() );
        }
        
        /* Returns the primitive value of a String */
        public function valueOf() {
            return $this->_value;
        }
    }
    
    Base_RegExp::prototype()->defineProperty('global', [
        "get" => function() {
            return strpos( $this->_modifiers, 'g' ) !== FALSE;
        }
    ]);

    Base_RegExp::prototype()->defineProperty('ignoreCase', [
        "get" => function() {
            return strpos( $this->_modifiers, 'i' ) !== FALSE;
        }
    ]);
    
    Base_RegExp::prototype()->defineProperty('multiline', [
        "get" => function() {
            return strpos( $this->_modifiers, 'm' ) !== FALSE;
        }
    ]);
    
    Base_RegExp::prototype()->defineProperty('source', [
        "get" => function() {
            return new Base_String( $this->_value );
        }
    ]);
    
    
    
?>