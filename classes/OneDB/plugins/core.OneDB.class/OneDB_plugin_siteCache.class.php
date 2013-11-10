<?php

    require_once "OneDB.class.php";
    require_once "OneDB_SiteCache.class.php";

    class OneDB_plugin_siteCache extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function siteCache() {
            return new OneDB_SiteCache( $this );
        }
        
    }
    
?>