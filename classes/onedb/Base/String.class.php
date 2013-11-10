<?php

    require_once "Array.class.php";

    class Base_String extends Object {
    
        use ListenerInterface;
    
        protected $_value = '';

        public function __get( $propertyName ) {
            return ( is_int( $propertyName ) && $propertyName >= 0 )
                ? $this->_value[ $propertyName ]
                : parent::__get( $propertyName );
        }
        
        public function __set( $propertyName, $propertyValue ) {
            if ( is_int( $propertyName ) && $propertyName >= 0 )
                $_value[ $propertyName ] = $propertyValue;
            else
                parent::__set( $propertyName, $propertyValue );
        }
        
        public function offsetSet( $propertyName, $propertyValue ) {
            return $this->__set( $propertyName, $propertyValue );
        }
        
        public function offsetGet( $propertyName ) {
            return $this->__get( $propertyName );
        }
        
        /* Initialize the primitive value of a string */
        public function init( $value ) {
            $this->_value = $value . '';
            return $this;
        }
        
        /* Returns the length of a string */
        public function count() {
            return strlen( $this->_value );
        }
        
        /* Returns the character at position $index from the string */
        public function charAt( $index ) {
            return $this->_value[ $index ];
        }
        
        /* Returns the character code from position $index from the string */
        public function charCodeAt( $index ) {
            return ord( $this->_value[ $index ] );
        }
        
        /* The concat() method is used to join two or more strings */
        public function concat( /* $str1, $str2, ... */ ) {
            $out = $this->_value;
            $args = func_get_args();
            foreach ($args as $str)
                $out .= $str;
            return ( new Base_Array() ).init( $out );
        }
        
        /* Returns the character coresponding to ascii code */
        public function fromCharCode( $code ) {
            return chr( $code );
        }
        
        /* Returns the first occurence of a specified value in a string */
        public function indexOf( $needle ) {
            $pos = strpos( $this->_value, $needle );
            return $pos === FALSE ? -1 : $pos;
        }
        
        /* The lastIndexOf() method returns the position of the last occurrence of a specified value in a string */
        public function lastIndexOf( $needle ) {
            $pos = strrpos( $this->_value, $needle );
            return $pos === FALSE ? -1 : $pos;
        }
        
        /* Test if a string matches a regular expression */
        public function match( $regexp ) {
            return @preg_match( $regExp . '', $this->_value ) ? TRUE : FALSE;
        }
        
        /* JS @replace equivalent */
        public function replace( $searchValue, $newValue ) {
            switch (TRUE) {
                
                case ( is_object( $searchValue ) && $searchValue instanceof Base_RegExp ):
                    return (new Base_String( ))->init( preg_replace( $searchValue . '', $newValue, $this->_value ) );
                    break;
                
                default:
                    return (new Base_String( ))->init( str_replace( $searchValue . '', $newValue, $this->_value ) );
            }
        }
        
        /* JS @search equivalent */
        public function search( $searchValue ) {
            return strpos( $this->_value, $searchValue );
        }
        
        /* Extracts a part of a string and returns a new string */
        public function slice( $start, $end ) {
            return (new Base_String( ))->init( substr( $this->_value, $start, $end ) );
        }
        
        /* The split() method is used to split a string into an array of substrings, and returns the new array */
        public function split( $separator = NULL, $limit = NULL ) {
        
            if ( $separator === NULL )
                return ( new Base_Array() )->init( [ $this->_value ] );
            else {
        
                $out = [];

                if ($limit === NULL)
                    $out = explode( $separator . '', $this->_value );
                else
                    $out = explode( $separator . '', $this->_value, (int)$limit );
                
                for ( $i=0,$len=count($out); $i<$len; $i++ ) {
                    $out[$i] = ( new Base_String() )->init( $out[$i] );
                }
            
                return (new Base_Array())->init( $out );
            }
        }
        
        /* The substr() method extracts parts of a string, beginning at the character 
         * at the specified position, and returns the specified number of characters */
        public function substr( $start = 0, $length = NULL ) {
            if ( $length === NULL )
                return ( new Base_String( ) ) -> init( substr( $this->_value, (int)$start ) );
            else
                return ( new Base_String( ) ) -> init( substr( $this->_value, (int)$start, (int)$length ) );
        }
        
        /* Alias of substr */
        public function substring( $start = 0, $length = NULL ) {
            return $this->substr( $start, $length );
        }
        
        /* Returns a copy of the string with all characters converted to lower case */
        public function toLowerCase() {
            return ( new Base_String() )->init( strtolower( $this->_value ) );
        }
        
        /* Returns a copy of the string with all characters converted to upper case */
        public function toUpperCase() {
            return ( new Base_String() )->init( strtoupper( $this->_value ) );
        }
        
        public function __toString() {
            return $this->_value;
        }
        
        public function toString() {
            return $this;
        }
        
        /* Returns the primitive value of a String */
        public function valueOf() {
            return $this->_value;
        }
    }
    
    Prototype('Base_String')->defineProperty( 'length', [
        "get" => function(){
            return strlen( $this->_value );
        },
        "set" => function() {
        }
    ] );

?>