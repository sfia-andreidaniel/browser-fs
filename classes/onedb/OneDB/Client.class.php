<?php

    class OneDB_Client extends Object {
        
        protected static $_cfg   = NULL;   // @type Utils.Parsers.OneDBCfg
        protected static $_sites = [];     // @type OneDB_Client[]
        
        protected $_connection  = NULL;
        protected $_websiteName = NULL;
        
        protected $_runAs       = NULL;
        protected $_objects     = NULL;
        
        public function init( $websiteName, $runAsUserName = 'anonymous' ) {
            
            if ( !isset( self::$_cfg ) )
                self::$_cfg = Utils_Parsers_OneDBCfg::create();
            
            $this->_websiteName = $websiteName;
            $this->_runAs       = $runAsUserName;
            
            if ( !isset( self::$_sites[ $websiteName ] ) ) {
                
                $this->connect();
                
                self::$_sites[ $websiteName ] = $this;
            }
            
        }
        
        private function getDatabaseName( $str ) {
            
            if ( preg_match( '/\/([a-z\d\-_]+)$/i', $str, $matches ) )
                return $matches[1];
            else
                return NULL;
            
        }
        
        protected function connect() {
            try {
            
                $uri    = self::$_cfg->{$this->_websiteName}->connection->server;
                $dbName = $this->getDatabaseName( $uri );
            
                if ( $dbName === NULL )
                    throw Object( 'Exception.OneDB', "The connection setting from the ini file does not ends up in a database name!" );
            
                $this->_connection = new MongoClient( $uri );
                $this->_objects = $this->_connection->selectDB( $dbName )->objects;
                
            } catch ( Exception $e ) {
                throw Object('Exception.OneDB', "Failed to connect to mongo!", 0, $e );
            }
        }
        
        public function get() {
            
            return self::$_sites[ $this->_websiteName ];
            
        }
        
        public function getElementById( $elementId ) {
            
            if ( is_string( $elementId ) )
                $elementId = new MongoId( $elementId );
            
            if ( !( $elementId instanceof MongoId ) )
                return NULL;
            
            try {
                
                return Object( 'OneDB.Object', $this, $elementId );
                
            } catch ( Exception $e ) {
                return NULL;
            }
        }
        
        /* NOTE: if the path contains segments with "/" characters,
           specify the $elementPath into an array.
         */
        
        public function getElementByPath( $elementPath ) {
            
            if ( is_string( $elementPath ) ) {
                $assumeGoodPath = $elementPath;
                $elementPath = explode( "/", $elementPath );
            } else $assumeGoodPath = NULL;
            
            for ( $i=0, $len = count( $elementPath ); $i<$len; $i++ ) {
                $elementPath[$i] = urlencode( $elementPath[$i] );
            }
            
            $path = preg_replace( '/[\/]+/', '/', implode( "/", $elementPath ) );
            
            if ( $assumeGoodPath !== NULL ) {
                $query = [
                    "url" => [
                        '$in' => [
                            $assumeGoodPath,
                            $path
                        ]
                    ]
                ];
            } else {
                $query = [
                    'url' => $path
                ];
            }
            
            $data = $this->_objects->findOne( $query );
            
            if ( $data === NULL )
                return NULL;
            
            return Object( 'OneDB.Object', $this, $data['_id'], $data );
        }
        
    }
    
    OneDB_Client::prototype()->defineProperty( 'runAs', [
        "get" => function() {
            return $this->_runAs;
        }
    ]);
    
    OneDB_Client::prototype()->defineProperty( 'objects', [
        "get" => function() {
            return $this->_objects;
        }
    ]);

?>