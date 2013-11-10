<?php

    require_once "OneDB.class.php";
    require_once "OneDB_DatabaseExtender.class.php";

    class OneDB_plugin_database extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function database() {
            return new OneDB_DatabaseExtender( $this->_cfg['db'] );
        }
        
    }
    
?>