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
        
        /* Returns an element by it's mongoID.
           @param $elementId.
                - When null: returns OneDB.Object.Root
                - When NOT null: returns OneDB.Object
                - When not found: returns NULL
         */
        
        public function getElementById( $elementId ) {
            
            if ( $elementId === NULL ) {
                
                return Object( 'OneDB.Object.Root', $this );
            
            } else {
            
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
        }
        
        /* NOTE: if the path contains segments with "/" characters,
           specify the $elementPath into an array.
           
           @param: $elementPath
                   
                   When '/': returns OneDB.Object.Root

                   Otherwise:
                        returns OneDB.Object on Found,
                                NULL         on not Found.
           
         */
        
        public function getElementByPath( $elementPath ) {
            
            if ( is_string( $elementPath ) && $elementPath == '/' ) {
                
                return Object( 'OneDB.Object.Root', $this );
                
            } else {
            
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
        
        /* Finds data on server and returns it as OneDB.Object's.
        
           @param $query -> mandatory,
                  
                  array defining a Mongo Collection query.
                  
                  however, some magic query fields are treated by OneDB:
                  
                  $id        => <string> will be converted to [
                                    '_id' => MongoId( $<string> )
                                ]

                  $parent    => <string> will be converted to [
                                    '_parent' => MongoId( $<string> )
                                ]

                  $childOf   => <string> will be converted to [
                                    'url' => startsWith <string>
                                ]

                  $childOf   => <array of string> will be converted with [
                                    '$or' => [
                                        [ 'url' startswith element 1 ]
                                        ...
                                        [ 'url' startswith element n ]
                                    ]
                                ]

                  'anyField' => '$func:<function_name>'
                  'anyField' => '$func:<function_name>:<function_arguments_as_array_json_notation>'
                                will be converted to [ 
                                    'anyField' => <result_of_function_name>(
                                        <function_arguments_as_array_json_notation>
                                    )
                  
                  Note that excepting the $childOf operator, all the fields are altered
                       in a multi-level way in the @query ( recursive ).
                  
            @param $limit
                  
                  if NOT NULL and > 0, the query max results will be limited
                  to this argument
            
            @param $orderBy
                
                  if NOT NULL and of array type, a native mongodb sort
                  will be executed on results before returning them
         */
        
        public function find( array $query, $limit = NULL, $orderBy = NULL ) {
            
            try {
                $query = Object( 'OneDB.Query.Parser', $this, $query )->compile;
            } catch ( Exception $e ) {
                throw Object( 'Exception.OneDB', "Failed to compile query: " . $e->getMessage(), 0, $e );
            }
            
            $result = $this->_objects->find( $query );
            
            if ( is_array( $orderBy ) )
                $result = $result->sort( $orderBy );
            
            if ( $limit !== NULL && $limit > 0 )
                $result = $result->limit( $limit );
            
            $out = [];
            
            foreach ( $result as $item )
                $out[] = Object( 'OneDB.Object', $this, $item['_id'], $item );
            
            return Object( 'OneDB.Iterator', $out, $this );
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