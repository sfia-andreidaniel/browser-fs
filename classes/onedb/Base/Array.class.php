<?php

    require_once "String.class.php";
    require_once dirname(__FILE__) . "/Object.class.php";

    class Base_Array extends Base_Object {
    
        protected $_values = [];
    
        public function __set( $propertyName, $propertyValue ) {

            if ( is_int( $propertyName ) ) {

                $propertyValue = Base_Object::primitive( $propertyValue );

                if ( is_object( $propertyValue ) ) {
                    
                    if ( method_exists( $propertyValue, '__setObjectName' ) )
                        $propertyValue->__setObjectName( '*' );
                    
                    if ( method_exists( $propertyValue, '__setObjectParent' ) )
                        $propertyValue->__setObjectParent( $this );
                }
                
                /* We trigger the change event */
                $this->on( "change", $propertyName, [
                    'value'   => $propertyValue,
                    'details' => 'setIndex'
                ] );
                
                $this->_values[ $propertyName ] = $propertyValue;
            }
            else
                parent::__set( $propertyName, $propertyValue );
        }
        
        public function offsetSet( $propertyName, $value ) {
            if ( empty( $propertyName ) )
                $this->push( $value );
            else {
                if ( is_int ($propertyName) ) {
                    $this->__set( $propertyName, $value );
                }
                parent::offsetSet( $propertyName, $value );
            }
        }
        
        public function __get( $propertyName ) {

            if ( is_int( $propertyName ) )
                return $this->_values[ $propertyName ];
            else
                return parent::__get( $propertyName );
        }
        
        public function offsetGet( $propertyName ) {
            return $this->__get( $propertyName );
        }
    
        public function init( $values ) {
            
            if ( is_object( $values ) && $values instanceof Base_Array )
                $values = $values->valueOf();
            
            if ( !is_array( $values ) )
                throw new Exception("Cannot init array: 1st argument is not an array!");
            
            $this->_values = [];
            
            for ($i=0, $len=count($values); $i<$len; $i++ ) {
                $this->_values[] = $values[ $i ];
            }
            
            return $this;
        }
        
        public function count() {
            return count( $this->_values );
        }
        
        /* Joins two or more arrays, and returns a copy of the joined arrays */
        public function concat( /* $arg1, $arg2, ... $argn  */ ) {
            $out = $this->_values;
            foreach ( func_get_args() as $arg ) {
                
                switch (TRUE) {
                    case is_array( $arg ):
                    case is_object( $arg ) && $arg instanceof Base_Array:
                        for ($i=0,$len=count($arg); $i<$len; $i++)
                            $out[] = $arg[$i];
                        break;
                    default:
                        $out[] = $arg;
                }
            
            }
            
            return (new Base_Array())->init( $out );
        }
        
        /* Search the array for an element and returns its position */
        public function indexOf( $item ) {
            $result = array_search( $item, $this->_values );
            return $result === FALSE ? -1 : $result;
        }
        
        /* Joins all elements of the array into a string */
        public function join( $separator = ', ' ) {
            return implode( $separator, $this->_values );
        }
        
        /* Search the array for an element, starting at the end, and returns its position */
        public function lastIndexOf( $item, $start = 0 ) {
            $result = array_search( $item, array_slice( array_reverse( $this->_values ), $start ) );
            return $result === FALSE ? -1 : $result;
        }
        
        /* Removes the last element of an array, and returns that element */
        public function pop() {
            /* We trigger the change event */
            $this->on( "change", '*', [
                'value' => NULL,
                'details' => 'pop'
            ]);
        
            return array_pop( $this->_values );
        }
        
        /* Adds new elements to the end of an array, and returns the new length */
        public function push( /* $item1, $item2, ... */ ) {
            foreach ( func_get_args() as $arg ) {

                $arg = Base_Object::primitive( $arg );
                
                /* We trigger the change event */
                $this->on( "change", count( $this->_values ), [
                    'value' => $arg,
                    'details' => 'push'
                ]);
                
                $this->_values[] = $arg;

                if ( is_object( $arg ) ) {
                    
                    if ( method_exists( $arg, '__setObjectName' ) )
                        $arg->__setObjectName( '*' );
                    
                    if ( method_exists( $arg, '__setObjectParent' ) )
                        $arg->__setObjectParent( $this );
                }
                
            }
        }
        
        /* Reverses the order of the elements in an array */
        public function reverse() {
            /* We trigger the change event */
            $this->on( "change", '*', [
                'value' => NULL,
                'details' => 'reverse'
            ]);
            
            array_reverse( $this->_values );

            return $this;
        }
        
        /* Removes the first element of an array, and returns that element */
        public function shift() {

            /* We trigger the change event */
            $this->on( "change", '*', [
                'value' => NULL,
                'details' => 'shift'
            ]);
            
            return array_shift( $this->_values );
        }
        
        /* Selects a part of an array, and returns the new array */
        public function slice( $start, $len = NULL ) {

            /* We trigger the change event */
            $this->on( "change", '*', [
                'value' => NULL,
                'details' => 'slice'
            ]);
            
            return ( new Base_Array() )->init(
                array_slice( $this->_values, $start, $len )
            );
        }
        
        /* Sorts the elements of an array */
        public function sort( $callback = NULL ) {
            /* We trigger the change event */
            $this->on( "change", '*', [
                'value' => NULL,
                'details' => 'sort'
            ]);
            
            if ($callback == NULL) {

                sort( $this->_values );
                return $this;
            } else {
                if ( !is_callable( $callback ) )
                    throw new Exception("Array.Sort: callback is not a function!");
                usort( $this->_values, $callback );
                return $this;
            }
        }
        
        /* The splice() method adds/removes items to/from an array, and returns the removed item(s). */
        public function splice( $index, $howMany /*, $item1, $item2, ... $itemN */ ) {

            $addItems = array_slice( func_get_args(), 2 );
            
            /* We trigger the change event */
            $this->on( "change", '*', [
                'value' => NULL,
                'details' => 'splice(' . $index . ',' . $howMany . ',+' . count($addItems ) . ')'
            ]);
            
            for ( $i=0, $len = count($addItems); $i<$len; $i++ ) {
            
                $addItems[$i] = Base_Object::primitive( $addItems[$i] );
            
                if ( is_object( $addItems[$i] ) ) {
                    if ( method_exists( $addItems[$i], '__setObjectName' ) )
                        $addItems[$i]->__setObjectName( '*' );
                    
                    if ( method_exists( $addItems[$i], '__setObjectParent' ) )
                        $addItems[$i]->__setObjectParent( $this );
                }
            }
            
            switch ( count($addItems) ) {
                case 0:
                    return (new Base_Array())->init( array_splice( $this->_values, $index, $howMany ) );
                    break;
                case 1:
                    return (new Base_Array())->init( array_splice( $this->_values, $index, $howMany, reset( $addItems ) ) );
                    break;
                default:
                    return (new Base_Array())->init( array_splice( $this->_values, $index, $howMany, $addItems ) );
                    break;
            }
        }
        
        public function __toString() {
            return 'Array(' . count($this->_values) . ')';
        }
        
        /* Converts an array to a string, and returns the result */
        public function toString() {
            return ( new Base_String() )->init( $this->__toString() );
        }
        
        /* Adds new elements to the beginning of an array, and returns the new length */
        public function unshift( /* $item1, $item2, ... $itemX */ ) {
            return count(
                $this->_values = array_merge( func_get_args(), $this->_values )
            );
        }
        
        /* Returns the primitive value of a Base.Array */
        public function valueOf() {
            return $this->_values;
        }
        
        public function _keys() {
            return array_unique( array_merge( array_keys( $this->_values ), parent::_keys() ) );
        }
        
        public function propertyIsEnumerable( $key ) {
            if ( is_int( $key ) && $key >= 0 && $key < count( $this->_values ) )
                return TRUE;
            else
                return parent::propertyIsEnumerable( $key );
        }
        
        public function hasOwnProperty( $key ) {
            if ( is_int( $key ) && $key >= 0 && $key < count( $this->_values ) )
                return TRUE;
            else
                return parent::hasOwnProperty( $key );
        }
    }
    
    Prototype('Base_Array')->defineProperty( 'length', [
        "get" => function(){
            return count( $this->_values );
        },
        "set" => function() {
        }
    ] );

?>