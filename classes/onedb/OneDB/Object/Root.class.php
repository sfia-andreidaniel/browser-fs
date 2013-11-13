<?php
    
    require_once __DIR__ . '/../Object.class.php';
    
    class OneDB_Object_Root extends OneDB_Object implements IDemuxable {
        
        protected $_server   = NULL;
        protected $_type     = NULL;   //Override the type
        protected $_online   = TRUE;
        protected $_owner    = 'everybody';
        protected $_modifier = 'noone';
        
        static protected $_isContainer = TRUE;

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
            
            return Object( 'OneDB.Iterator', $out, $this->_server );
        }
        
        public function __mux() {
            return $this->_server->__mux();
        }
        
        /* @param data of type string in format websitename[:servername] */
        
        static public function __demux( $data ) {
            
            if ( !is_string( $data ) )
                throw Object('Exception.RPC', "Failed to demux instance: data is not string!" );
            
            $params = explode(':', $data );
            
            $params[1] = isset( $params[1] ) ? implode( ':', array_slice( $params, 1 ) ) : 'anonymous';
            
            return Object( 'OneDB.Object.Root', Object( 'OneDB.Client', $params[0], $params[1] ) );

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
    
    OneDB_Object_Root::prototype()->defineProperty( 'owner', [
        "get" => function() {
            return 'everybody';
        }
    ]);
    
    OneDB_Object_Root::prototype()->defineProperty( 'modifier', [
        "get" => function() {
            return 'noone';
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