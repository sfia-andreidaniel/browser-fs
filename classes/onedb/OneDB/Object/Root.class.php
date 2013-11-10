<?php
    
    class OneDB_Object_Root extends OneDB_Object {
        
        protected $_server = NULL;
        
        static protected $_isContainer = TRUE;
        
        public function init( OneDB_Client $client, $objectId = NULL ) {
            $this->_server = NULL;
        }
        
        public function __destruct() {}
        
        public function save() {
            // The root object is not saveable
        }
        
        public function load( $fromData = NULL ) {
            // The root object is not loadable
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
    
    OneDB_Object_Root::prototype()->defineProperty( '_type', [
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
    
    OneDB_Object_Root::prototype()->defineProperty( '_parent', [
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
?>