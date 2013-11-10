<?php

    abstract class OneDB_FileSystem_Wrapper_Abstract {
        
        protected $_handle;
        protected $_item;
        protected $_events = array();
        
        function __construct( &$handle, &$item ) {
            $this->_handle = $handle;
            $this->_item   = $item;
        }
        
        function bind( $eventName, $callable ) {
            if (!isset( $this->_events[ $eventName ] ) )
                $this->_events[ $eventName ] = array();
            
            $this->_events[ $eventName ][] = $callable;
        }
        
        function on( $eventName ) {
            if (!isset( $this->_events[ $eventName ] ) )
                return;
            foreach ($this->_events[ $eventName ] as $callable ) {
                $callable( $this );
            }
        }
        
        abstract function close( );
        abstract function eof( );
        abstract function read( $count );
        abstract function seek( $offset, $whence = SEEK_SET );
        abstract function tell( );
        abstract function truncate( $new_size );
        abstract function write( $data );
        
        function __destruct() {
            $this->on( 'close' );
        }
    }

?>