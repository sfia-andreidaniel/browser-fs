<?php

    class OneDB_Client extends Object implements IDemuxable {
        
        protected static $_cfg      = NULL; // <Utils_Parsers_OneDBCfg>  configuration parser singleton
        protected static $_sites    = [];   // <OneDB_Client[]>          singletons to this class instance
        private          $_counters = [];   // <MongoCounter[]>          MongoCounter singletons
        
        protected $_connection  = NULL;     // <MongoClient>             the connection this client is using
        protected $_databaseName= NULL;     // <string>                  mongodb database name
        protected $_websiteName = NULL;     // <string>                  website name
        protected $_storageName = NULL;     // <string>                  storage name type

        protected $_storage     = NULL;     // <OneDB_Storage>           instance of the <OneDB_Storage> of this server
        
        protected $_runAs       = NULL;     // <string>                  the name of the user that's using this website
        protected $_password    = NULL;     // <string>                  md5ed password this user is using
        protected $_shChallenge = NULL;     // <string>                  shadow challenge string sent from client

        protected $_objects     = NULL;     // <MongoCollection>         link to connection db.objects MongoDB collection

        protected $_user        = NULL;     // <Sys_Security_User>       instance of the user this website is operating
        protected $_sys         = NULL;     // <Sys_Security_Management> local server accounts enumerator
        
        public function init( $websiteName, $userName = 'anonymous', $password = '', $shadowChallenge = '' ) {
            
            // singleton
            if ( isset( self::$_sites[ $websiteName . ':' . $userName ] ) )
                return self::$_sites[ $websiteName . ':' . $userName ];
            
            if ( !isset( self::$_cfg ) )
                self::$_cfg = Utils_Parsers_OneDBCfg::create();
            
            $this->_websiteName = $websiteName;
            $this->_runAs       = $userName;
            
            if ( !is_string( $password ) )
                throw Object( 'Exception.OneDB', 'the password parameter should be of type string!' );
            else {

                $this->_password = strlen( $password )
                    ? md5( $password )
                    : '';

            }
            
            $this->_storageName = self::$_cfg->{$this->_websiteName}->connection->storage_engine;

            if ( !isset( self::$_sites[ $websiteName . ':' . $userName ] ) ) {
                
                // Connect to database
                $this->connect();
                
                // Initialize the storage engine
                $this->_storage = Object( 'OneDB.Storage.' . $this->_storageName, $this );
            
                // Setup a singleton for the instance in order to obtain it quickly next time
                self::$_sites[ $websiteName . ':' . $userName ] = $this;
            }
            
            //print_r( self::$_cfg );
        }
        
        // @mux format sample: <string> = loopback:andrei:Cloud:31c84aa266b7b60067cbea82d6eafeea
        // THE RPC IS RESPONSIBLE FOR STORING IT'S SHADOW KEY AND SEND IT AS CHALLENGE WHEN
        // MUXING THE CLIENT INSTANCE NEXT TIME!!!
        public function __mux() {
            return $this->_websiteName . ':' . $this->_runAs . ':' . $this->_storageName . ':' . $this->_user->shadowKey;
        }
        
        private function getDatabaseName( $str ) {
            
            if ( preg_match( '/\/([a-z\d\-_]+)$/i', $str, $matches ) )
                return $matches[1];
            else
                return NULL;
            
        }
        
        /* - connects to MongoDB server
         * - instantiates the storage engine on this server
         * - creates the user instance on this server
         */
        protected function connect( ) {
            try {
            
                $uri    = self::$_cfg->{$this->_websiteName}->connection->server;
                $this->_databaseName = $this->getDatabaseName( $uri );
            
                if ( $this->_databaseName === NULL )
                    throw Object( 'Exception.OneDB', "The connection setting from the ini file does not ends up in a database name!" );
            
                $this->_connection   = new MongoClient( $uri );
                
                // select database
                $db = $this->_connection->selectDB( $this->_databaseName );
                
                // initialize connections
                $this->_objects      = $db->objects;
                $this->_sys          = Object( 'Sys.Security.Management', $this, $db->shadow );
                $this->_user         = Object( 'Sys.Security.User', $this, $db->shadow, $this->_runAs, $this->_password, $this->_shChallenge );
                
            } catch ( Exception $e ) {
                throw Object('Exception.OneDB', "Failed to connect to mongo!", 0, $e );
            }
        }
        
        /* Get an instance to the shadow collection on server. The instance
           is returned only if the local authenticated user is called onedb.
         */
        
        public function get_shadow_collection() {
            if ( $this->_runAs != 'onedb' && $this->_runAs != 'root' )
                throw Object('Exception.Security', 'access to shadow collection is forbidden for user ' . $this->_runAs );
            return $this->_connection->selectDB( $this->_databaseName )->shadow;
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
        
        /* This method is intended for internal usage, and should not be
           exposed via rpc.
         */
        public function createCounter( $counterName ) {
            
            if ( isset( $this->_counters[ $counterName ] ) )
                return $this->_counters[ $counterName ];
            
            else
                return $this->_counters[ $counterName ] = Object( 'Mongo.Counter', $this->_connection->selectDB( $this->_databaseName ), $counterName );
            
        }
        
        public static function __demux( $data ) {
            if ( !is_string( $data ) )
                throw Object( 'Exception.RPC', "Bad demuxing input. Expected a string!" );
            
            
            $data = explode( ':', $data );
            
            // $data[0] = <websitename>
            
            // username
            $data[1] = isset( $data[1] ) 
                ? $data[1]
                : 'anonymous';
            
            // when demuxing, we're not authenticating via password,
            // but via shadow challenge mechanism
            
            // shadow key challenge
            $data[2] = isset( $data[2] )
                ? $data[2]
                : '';
            
            return Object( 'OneDB.Client', $data[0], $data[1], '', $data[2] );
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
    
    OneDB_Client::prototype()->defineProperty( 'storage', [
        "get" => function() {
            return $this->_storage;
        }
    ] );
    
    OneDB_Client::prototype()->defineProperty( 'user', [
        "get" => function() {
            return $this->_user;
        }
    ] );
    
    OneDB_Client::prototype()->defineProperty( 'sys', [
        "get" => function() {
            return $this->_sys;
        }
    ]);
    
    OneDB_Client::prototype()->defineProperty( 'websiteName', [
        "get" => function() {
            return $this->_websiteName;
        }
    ] );

?>