<?php

    class OneDB extends Object implements IDemuxable {
        
        public function login( $websiteName, $connectAs = 'anonymous', $password = '', $shadowKeyChallenge = '' ) {
            // passwords are not implemented
            return self::connect( $websiteName, $connectAs, $password, $shadowKeyChallenge );
        }
        
        static public function connect( $websiteName, $userName = 'anonymous', $password = '', $shadowKeyChallenge = '' ) {
            
            return Object( 'OneDB.Client', $websiteName, $userName, $password, $shadowKeyChallenge );
            
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
            
            return Object('Utils.Parsers.OneDBCfg', __DIR__ . '/../../etc/onedb/onedb.ini' )->getWebsitesNames();
            
        }
        
    ] );

?>