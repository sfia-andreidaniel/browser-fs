<?php

    require_once dirname(__FILE__) . "/OneDB_FileSystem_Wrapper_Abstract.class.php";

    class OneDB_FileSystem_Wrapper_FileStream extends OneDB_FileSystem_Wrapper_Abstract {
        
        function __construct( &$handle, &$item ) {
            parent::__construct( $handle, $item );
        }
        
        function close( ) {
            return fclose( $this->_handle );
        }
        
        function eof( ) {
            return feof( $this->_handle );
        }
        
        function read( $count ) {
            return fread( $this->_handle, $count );
        }
        
        function seek( $offset, $whence = SEEK_SET ) {
            return fseek( $this->_handle, $offset, $whence );
        }
        
        function tell( ) {
            return ftell( $this->_handle );
        }
        
        function truncate( $new_size ) {
            return ftruncate( $this->_handle, $new_size );
        }
        
        function write( $data ) {
            return FALSE;
        }
        
    }

?>