<?php

    /* Generic OneDB type */
    
    class OneDB_Type extends Object {
        
        protected $_root = NULL;
        
        static protected $_isContainer = FALSE;
        static protected $_isReadOnly  = FALSE;
        
        public function init( OneDB_Object $root ) {
            $this->_root = $root;
        }
        
        public function importOwnProperties( array $properties ) {
            throw Object( 'Exception.OneDB', "You must implement the importOwnProperties method in class " . get_class( $this ) );
        }
        
        public function exportOwnProperties( array &$rootProperties ) {
            throw Object( 'Exception.OneDB', "You must implement the exportOwnProperties method in class " . get_class( $this ) );
        }
        
        public function isContainer() {
            return static::$_isContainer;
        }
        
        public function isReadOnly() {
            return static::$_isReadOnly;
        }
        
        public function getChildNodes() {
            return Object( 'OneDB.Iterator', [] );
        }
    }
    
    OneDB_Type::prototype()->defineProperty( 'name', [
        
        "get" => function() {
            
            $myName = get_class( $this );
            return preg_replace( '/^OneDB_Type_/', '', $myName );
            
        }
        
    ] );

?>