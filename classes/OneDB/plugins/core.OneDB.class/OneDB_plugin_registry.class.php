<?php

    require_once "OneDB.class.php";
    require_once "OneDB_Registry.class.php";

    class OneDB_plugin_registry extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function registry() {
            return new OneDB_Registry(
                $this
            );
        }
        
    }
    
?>