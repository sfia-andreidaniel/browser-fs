<?php

    require_once dirname(__FILE__) . "/OneDB_FileSystem_Wrapper_Abstract.class.php";

    class OneDB_FileSystem_Wrapper_Process extends OneDB_FileSystem_Wrapper_Abstract {
        
        protected $_handle = NULL;
        protected $_tmpFile = NULL;
        protected $_tmpName = NULL;
        
        function __construct( $cmdLine ) {
            $dummy = NULL;
            
            $this->_handle = popen( $cmdLine, 'r' );
            
            if (!is_resource( $this->_handle )) {
                throw new Exception("Cannot open process '" . $cmdLine + ".");
            }
            
            $this->_tmpName = tempnam( sys_get_temp_dir(), 'process-' );
            
            $this->_tmpFile = fopen( $this->_tmpName, 'w+');
            
            if (!is_resource( $this->_tmpFile ) ) {
                fclose( $this->_handle );
                @unlink( $this->_tmpName );
                throw new Exception("Cannot create process temporary file on disk! ($this->_tmpName)");
            }
            
            while (!feof( $this->_handle ) ) {
                fwrite( $this->_tmpFile, fread( $this->_handle, 8192 ) );
            }
            
            pclose( $this->_handle );
            
            fseek( $this->_tmpFile, 0 );
        }
        
        function stat() {
            return fstat( $this->_tmpFile );
        }
        
        function close( ) {
            if (is_resource( $this->_tmpFile ) ) {
                fclose( $this->_tmpFile );
                @unlink( $this->_tmpName );
            }
            return TRUE;
        }
        
        function eof( ) {
            return @feof( $this->_tmpFile );
        }
        
        function read( $count ) {
            return @fread( $this->_tmpFile, $count );
        }
        
        function seek( $offset, $whence = SEEK_SET ) {
            return @fseek( $this->_tmpFile, $whence );
        }
        
        function tell( ) {
            return @ftell( $this->_tmpFile );
        }
        
        function truncate( $new_size ) {
            trigger_error("Process streams are not truncateable", E_WARNING );
            return FALSE;
        }
        
        function write( $data ) {
            trigger_error("Process streams are not writeable", E_WARNING );
            return FALSE;
        }
        
        function __destruct( ) {
            $this->close();
        }
        
    }

?>