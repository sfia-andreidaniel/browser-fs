<?php

    require_once "OneDB.class.php";
    require_once "OneDB_Security.class.php";

    class OneDB_plugin_security extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function security( $userName ) {
            $ret = new OneDB_Security( $this );
            $ret->login( $userName );
            return $ret;
        }
        
    }
    
?>