<?php

    require_once "Proto.class.php";
    require_once "Property.class.php";

    class Prototype implements ArrayAccess, Iterator, Countable {
        
        protected $_bucket = array();

        private   $_iterator = NULL;
        private   $_ikeys    = NULL;
        private   $_ikeysLen = NULL;
        
        public function __construct( ) {
        }
        
        public function _getProperty( $propertyName, $ensureType = NULL ) {
            if ($ensureType === NULL)
                return isset( $this->_bucket[ $propertyName ] ) ? $this->_bucket[ $propertyName ][ 'value' ] : NULL;
            else
                return isset( $this->_bucket[ $propertyName ] ) 
                       && $this->_bucket[ $propertyName ][ 'type' ] == $ensureType 
                            ? $this->_bucket[ $propertyName ]['value']
                            : NULL;
        }
        
        public function set( $propertyName, $propertyValue, $typeCast = 'public', $override = TRUE ) {
            if ($override) {
                $this->_bucket[ $propertyName ] = array(
                    'value' => $propertyValue,
                    'type'  => $typeCast
                );
            } else
                if ( !isset( $this->_bucket[ $propertyName ] ) ) {
                    $this->_bucket[ $propertyName ] = array(
                        'value' => $propertyValue,
                        'type'  => $typeCast
                    );
                }
        }
        
        /*
        public function bucketSet( $bucketName, $bucketValue, $override = TRUE ) {
            if ( $override ) {
                $this->_bucket[ $bucketName ] = $bucketValue;
            } else {
                
                $this->_bucket[ $bucketName ] =
                    isset( $this->_bucket[ $bucketName ] )
                        ? $this->_bucket[ $bucketName ]
                        : $bucketValue;
                
            }
        }
        */
        
        public function __get( $propertyName ) {
            return $this->_getProperty( $propertyName );
        }
        
        public function __set( $propertyName, $propertyValue ) {
            
            switch ( TRUE ) {
                
                case $this->_is_property( $propertyName ):
                    $this->_call_property( $propertyName, 'set', $this, $propertyValue );
                    break;
                
                case Proto::get( get_called_class() )->_is_property( $propertyName ):
                    $this->prototype->_call_property( $propertyName, 'set', $this, $propertyValue );
                    break;
                
                /* Check sub-parents if propery exists as a setter */
                case TRUE:
                    $parents = class_parents( $this );
                    foreach ($parents as $p) {
                        $proto = Proto::get( $p );
                        if ( $proto->_is_property( $propertyName ) ) {
                            $proto->_call_property( $propertyName, 'set', $this, $propertyValue );
                            break 2;
                        }
                    }
                
                case is_callable( $propertyValue ):
                    $this->set( $propertyName, new Method( $propertyValue, $propertyName ), 'method' );
                    break;
                
                default:
                    $this->set( $propertyName, $propertyValue, 'public' );
                    break;
            }
        }
        
        public function hasOwnProperty( $propertyName ) {
            return isset( $this->_bucket[ $propertyName ] );
        }
        
        public function propertyIsEnumerable( $propertyName ) {
            return !isset( $this->_bucket[ $propertyName ] )
                ? FALSE
                : ( $this->_bucket[ $propertyName ]['type'] == 'public'
                        ? TRUE
                        : ( $this->_bucket[ $propertyName ]['type'] == 'method'
                               ? TRUE
                               : $this->_bucket[ $propertyName ]['value']->enumerable
                          )
                );
        }
        
        public function typeof( $propertyName ) {
            return isset( $this->_bucket[ $propertyName ] ) ? $this->_bucket[ $propertyName ]['type'] : 'undefined';
        }
        
        public function defineProperty( $propertyName, array $property ) {
            $this->set( $propertyName, new Property( $property ), 'property' );
        }
        
        /* Array access interface implementation */
        
        public function offsetExists( $offset ) {
            return isset( $this->_bucket[ $offset ] );
        }
        
        public function offsetGet( $offset ) {
            return $this->$offset;
        }
        
        public function offsetSet( $offset, $value ) {
            $this->$offset = $value;
        }
        
        public function offsetUnset( $offset ) {
            if ( isset( $this->_bucket[ $offset ] ) )
                unset( $this->_bucket[ $offset ] );
        }
        
        public function _keys() {
            $out = array();
            foreach ( array_keys( $this->_bucket) as $property ) {
                if ( $this->propertyIsEnumerable( $property ) )
                    $out[] = $property;
            }
            return $out;
        }
        
        /* Iterator implementation */
        
        public function rewind() {
            $this->_ikeys = $this->_keys();
            $this->_ikeysLen = count( $this->_ikeys );
            $this->_iterator = 0;
        }
        
        public function key() {
            return $this->_ikeys[ $this->_iterator ];
        }
        
        public function current() {
            return $this[ $this->key() ];
        }
        
        public function next() {
            $this->_iterator++;
        }
        
        public function valid() {
            return $this->_iterator < $this->_ikeysLen;
        }
        
        /* Countable implementation */
        public function count() {
            return count( $this->_keys() );
        }
        
        /* Prototype's implementation */
        public static function prototype() {
            return Proto::get( get_called_class() );
        }
        
        private function _extend_apply( $something ) {
            switch ( $something[ 'type' ] ) {
                
                case 'method':
                    return [
                        'value' => $something[ 'value' ]->morph( $this ),
                        'type' => 'method'
                    ];
                    break;
                
                default:
                    return $something;
                
            }
        }
        
        public function _extend_import( $name, $value, $override = TRUE ) {
            
            $this->_bucket[ $name ] = $override 
                ? $this->_extend_apply( $value )
                : ( isset( $this->_bucket[ $name ] ) ? $this->_bucket[ $name ] : $this->_extend_apply( $value ) );
            
        }
        
        public function extend( $instance, $override, $methods = NULL ) {

            if ($methods === NULL) {
                $methods = array_keys( $this->_bucket );
            } else {
                if (!is_array( $methods ) )
                    throw new Exception("The methods argument should be either an array, either NULL");
            }
            
            foreach ($methods as $method) {
                if ( isset( $this->_bucket[ $method ] ) )
                    $instance->_extend_import( $method, $this->_bucket[ $method ], $override );
            }

        }
        
        public function _is_property( $propertyName ) {
            return isset( $this->_bucket[ $propertyName ] ) &&
                   $this->_bucket[ $propertyName ]['type'] == 'property';
        }
        
        public function _call_property( $propertyName, $getOrSet, &$thisArg, $setValue = NULL ) {
            return call_user_func_array(
                $this->_bucket[ $propertyName ]['value']->$getOrSet->callable->bindTo( $thisArg, $thisArg ),
                [ $setValue ] 
            );
        }
        
        public function __toString() {
            return get_called_class();
        }
    }
    
?>