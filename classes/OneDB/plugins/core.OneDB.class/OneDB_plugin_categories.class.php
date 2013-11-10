<?php

    require_once "OneDB.class.php";

    class OneDB_plugin_categories extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function categories( $filter, $orderBy = NULL ) {
            $tmp = new OneDB_CategoryConnector( $this );
            return $tmp->categories( $filter, $orderBy );
        }
        
    }
    
?>