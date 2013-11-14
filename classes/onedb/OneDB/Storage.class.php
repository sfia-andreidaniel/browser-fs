<?php
    
    class OneDB_Storage extends Object implements IDemuxable {
        
        protected $_name   = 'abstract';
        protected $_root   = NULL;
        
        public function init( OneDB_Client $client ) {

            throw Object( 'Exception.OneDB', 'OneDB_Storage an abstract class and cannot be instantiated directly' );

        }
        
        public function unlinkFile( $fileId ) {
            
        }
        
        public function __mux() {
            
            throw Object( 'Exception.Storage', 'demuxing is not implemented' );
            
        }
        
        static public function __demux( $data ) {
            
            throw Object( 'Exception.Storage', 'muxing not implemented!' );
            
        }
        
        
    }
    
>