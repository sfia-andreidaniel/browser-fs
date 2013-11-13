<?php

    require_once dirname(__FILE__) . "/@/Prototype.class.php";
    
    class Object extends Prototype {
        
        private       $_key      = 0;
        
        public function __construct() {
        }
        
        public function import( $includeTraits = NULL, $extenders = NULL ) {
            
            if ( $includeTraits !== NULL ) {
                
                if ( is_string( $includeTraits ) )
                    $includeTraits = [ $includeTraits ];
                else
                    if ( !is_array( $includeTraits ) )
                        throw new Exception("Error instantiating class: packageName(s) parameter should be a string or an array!");
                
                $numPackages = count( $includeTraits );
                $packageIndex= 0;
                
                /* Parse arguments from extenter */
                
                if ( $extenders !== NULL ) {
                    
                    if (!is_array( $extenders ) )
                        throw new Exception("Error instantiating class: extenders argument should be an array!");
                    
                    $import = array();
                    
                    foreach ($extenders as $extender) {
                        
                        if ( !preg_match( '/^([a-z\_]([a-z\_\d]+))(\:([\d]+))?$/i', $extender, $matches ) )
                            throw new Exception("Illegal class extender name ($extender)!");
                        
                        $add = array(
                            'method' => $matches[1],
                            'from'   => $matches[3] ? (int)$matches[4] : -1
                        );
                        
                        if ($add['from'] >= 0 && $add['from'] >= $numPackages)
                            throw new Exception("Illegal extender index $add[method]:$add[from]");
                        
                        $import[] = $add;
                    }
                } else $import = NULL;
                
                foreach ( $includeTraits as $package ) {
                    
                    $packageClassName = preg_replace( '/[\.]+/', '_', $package );
                    
                    if ( !class_exists( $packageClassName ) ) {
                        $packagePathFile  = __DIR__ . '/' . preg_replace( '/[\._]+/', DIRECTORY_SEPARATOR, $package ) . ".class.php";
                        
                        if (!file_exists( $packagePathFile ))
                            throw new Exception("trait Class $packageClassName not found in $packagePathFile");
                        
                        require_once $packagePathFile;
                        
                        if (!class_exists( $packageClassName ) )
                            throw new Exception("Class $packageClassName was not found in it's package file $packagePathFile!");
                        
                    }
                    
                    $methods       = NULL;
                    $ensureMethods = [];
                    
                    if ($import !== NULL) {
                        
                        $methods = array();
                        
                        for ($i=0,$len=count( $import ); $i<$len; $i++ ) {
                            
                            if ( $import[ $i ][ 'from' ] == -1 )
                                $methods[] = $import[$i]['method'];
                            else
                            if ( $import[ $i ][ 'from' ] == $packageIndex )
                                $ensureMethods[] = $import[$i]['method'];
                        }
                    }
                    
                    $packageClassName::prototype()->extend( $this, FALSE, $methods );
                    
                    if ( count( $ensureMethods ) )
                        $packageClassName::prototype()->extend( $this, TRUE, $ensureMethods );
                    
                    $packageIndex++;
                }
            }
            
            return $this;
        }
        
        private function _get( $propertyValue, $castAs = 'public' ) {
            
            switch ($castAs) {
                case 'public':
                    return $propertyValue;
                    break;
                case 'method':
                    return $propertyValue;
                    break;
                case 'property':
                    $fn = $propertyValue->get->callable->bindTo( $this, $this );
                    return $fn();
                    break;
            }
        }
        
        public function __get( $propertyName ) {
            switch (TRUE) {
                case $propertyName == 'prototype':
                    return $this::prototype();
                    break;

                default:
                    switch ( $insType = $this->typeof( $propertyName ) ) {
                        case 'undefined':
                            switch ( $type = Prototype( get_called_class() )->typeof( $propertyName ) ) {
                                case 'undefined':
                                    $parents = class_parents( $this );
                                    foreach ( $parents as $protoParent ) {
                                        $proto = Prototype( $protoParent );
                                        switch ( $inheritType = $proto->typeof( $propertyName ) ) {
                                            case 'undefined':
                                                break;
                                            default:
                                                return $this->_get( $proto->_getProperty( $propertyName ), $inheritType );
                                                break;
                                        }
                                    }
                                    return NULL;
                                default:
                                    return $this->_get( Prototype( get_called_class() )->_getProperty( $propertyName ), $type );
                                    break;
                            }
                            break;
                        default:
                            return $this->_get( $this->_getProperty( $propertyName ), $insType );
                            break;
                    }
            }
        }
        
        public function __getOwnProperty( $propertyName ) {
            return array_key_exists( $propertyName, $this->_keys() )
                ? $this->__get( $propertyName )
                : NULL;
        }
        
        public function __set( $propertyName, $propertyValue ) {
            if ( $propertyName == 'prototype' )
                throw new Exception("Prototype not implemented!");
                
            parent::__set( $propertyName, $propertyValue );
        }
        
        /* final */ public function __call( $methodName, $args ) {
        
            $mtd = $this->__get( $methodName );
            
            if ( $mtd === NULL )
                throw new Exception( "$methodName is not a method in $this!" );
            
            return call_user_func_array( $mtd->callable->bindTo( $this, $this ), $args );
        }
        
        public function _keys() {
            $protoKeys = self::prototype()->_keys();
            $myKeys    = parent::_keys();
            return array_unique( array_merge( $protoKeys, $myKeys ) );
        }
        
    }

    function Object( $objectType = 'Object' ) {

        $packageClassName = preg_replace( '/[\.]+/', '_', $objectType );
            
        if ( !class_exists( $packageClassName ) ) {
            $packagePathFile  = __DIR__ . '/' . preg_replace( '/[\._]+/', DIRECTORY_SEPARATOR, $objectType ) . ".class.php";
            
            if (!file_exists( $packagePathFile ))
                throw new Exception("trait Class $packageClassName not found in $packagePathFile");
            
            require_once $packagePathFile;
            
            if (!class_exists( $packageClassName ) )
                throw new Exception("Class $packageClassName was not found in it's package file $packagePathFile!");
        }
        
        $o = new $packageClassName;
        
        if ( method_exists( $o, 'init' ) )
        
            $result = call_user_func_array( [ $o, 'init' ], array_slice( func_get_args( ), 1 ) );
        
        else
        
            $result = NULL;
        
        return $result === NULL ? $o : $result;
    }

    require_once __DIR__ . "/@/ListenerInterface.trait.php";
    require_once __DIR__ . "/@/IDemuxable.interface.php";
    require_once __DIR__ . "/Base/Undefined.class.php";

?>