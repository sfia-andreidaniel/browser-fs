<?php

    /* Generic OneDB type */
    
    class OneDB_Type extends Object {
        
        protected $_root = NULL;
        
        public function init( OneDB_Object $root ) {
            $this->_root = $root;
        }
        
        public function importOwnProperties( array $properties ) {
            throw Object( 'Exception.OneDB', "You must implement the importOwnProperties method in class " . get_class( $this ) );
        }
        
        public function exportOwnProperties( array &$rootProperties ) {
            throw Object( 'Exception.OneDB', "You must implement the exportOwnProperties method in class " . get_class( $this ) );
        }
        
    }
    
    OneDB_Type::prototype()->defineProperty( 'name', [
        
        "get" => function() {
            
            $myName = get_class( $this );
            return preg_replace( '/^OneDB_Type_/', '', $myName );
            
        }
        
    ] );

?>