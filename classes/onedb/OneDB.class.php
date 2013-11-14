<?php

    class OneDB extends Object implements IDemuxable {
        
        public function login( $websiteName, $connectAs = 'anonymous', $password = '' ) {
            // passwords are not implemented
            return self::connect( $websiteName, $connectAs, $password );
        }
        
        static public function connect( $websiteName, $connectAs = 'anonymous', $password = '' ) {
            
            return Object( 'OneDB.Client', $websiteName, $connectAs, $password );
            
        }
        
        public function __mux() {
            return 'OneDB';
        }
        
        static public function __demux( $data ) {
            return Object( 'OneDB' );
        }
    }
    
    OneDB::prototype()->defineProperty( 'websites', [
        
        "get" => function() {
            
            return Object('Utils.Parsers.OneDBCfg', __DIR__ . '/../../conf/onedb.ini' )->getWebsitesNames();
            
        }
        
    ] );

?>