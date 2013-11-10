<?php

    require_once "OneDB.class.php";

    class OneDB_plugin_backendObjects extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function backendObjects() {
            global $__OneDB_Backend_Objects__;
            return $__OneDB_Backend_Objects__;
        }
    }
    
?>