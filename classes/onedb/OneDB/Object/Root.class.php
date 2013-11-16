<?php
    
    require_once __DIR__ . '/../Object.class.php';
    
    class OneDB_Object_Root extends OneDB_Object implements IDemuxable {
        
        protected $_server   = NULL;
        protected $_type     = NULL;   //Override the type
        protected $_online   = TRUE;
        
        static protected $_isContainer = TRUE;
        static private   $_singletons  = [];

        public function init( OneDB_Client $client, $objectId = NULL, $loadFromProperties = NULL ) {
            $this->_server = $client;
        }
        
        public function __destruct() {}
        
        public function save() {
            // The root object is not saveable
        }
        
        public function load( $fromData = NULL ) {
            // The root object is not loadable
        }
        
        protected function getChildNodes() {
            
            /* Fetch childs... */
            
            $result = $this->_server->objects->find([
                "_parent" => NULL
            ]);
            
            $out = [];
            
            foreach ( $result as $item ) {
                
                $out[] = Object( 'OneDB.Object', $this->_server, $item['_id'], $item );
                
            }
            
            $out = Object( 'OneDB.Iterator', $out, $this->_server );
            
            return $out;
        }
        
        public function __mux() {
            return $this->_server->__mux();
        }
        
        /* @param data of type string in format websitename[:servername] */
        
        static public function __demux( $data ) {
            
            if ( !is_string( $data ) )
                throw Object('Exception.RPC', "Failed to demux instance: data is not string!" );
            
            if ( isset( self::$_singletons[ $data ] ) )
                return self::$_singletons[ $data ];
            
            $params = explode(':', $data );
            
            //$params[1] = isset( $params[1] ) ? implode( ':', array_slice( $params, 1 ) ) : 'anonymous';
            
            $params[1] = isset( $params[1] ) ? $params[1] : 'anonymous';
            $params[2] = isset( $params[2] ) ? $params[2] : '';
            
            return ( self::$_singletons[ $data ] = Object( 'OneDB.Object.Root', Object( 'OneDB.Client', $params[0], $params[1], '', $params[2] ) ) );

        }
        
        protected function getFlags() {
            
            return ONEDB_OBJECT_ROOT ^ ONEDB_OBJECT_CONTAINER;
            
        }
        
    }
    
    OneDB_Object_Root::prototype()->defineProperty( '_id', [
        'get' => function() {
            return NULL;
        }
    ] );
    
    OneDB_Object_Root::prototype()->defineProperty( 'name', [
        "get" => function() {
            return '';
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'type', [
        "get" => function() {
            return NULL;
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'uid', [
        "get" => function() {
            return 1; // hardcoded uid of the "root" account
        }
    ] );
    
    OneDB_Object_Root::prototype()->defineProperty( 'gid', [
        "get" => function() {
            return 3; // hardcoded gid of the "root" account
        }
    ] );
    
    OneDB_Object_Root::prototype()->defineProperty( 'muid', [
        "get" => function() {
            return 1; // hardcoded uid of the "root" account
        }
    ] );
    
    OneDB_Object_Root::prototype()->defineProperty( 'created', [
        "get" => function() {
            return NULL;
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'modified', [
        "get" => function() {
            return FALSE;
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'description', [
        "get" => function() {
            return 'OneDB Root object';
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'icon', [
        "get" => function() {
            return NULL;
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'keywords', [
        "get" => function() {
            return [];
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'tags', [
        "get" => function() {
            return [];
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'parent', [
        "get" => function() {
            return NULL;
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'online', [
        "get" => function() {
            return TRUE;
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'autoCommit', [
        "get" => function() {
            return TRUE;
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'url', [
        "get" => function() {
            return "/";
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'childNodes', [
        "get" => function() {
            return $this->getChildNodes();
        }
    ] );
?>