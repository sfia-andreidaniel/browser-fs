<?php

    require_once dirname(__FILE__) . "/OneDB_FileSystem_Wrapper_Abstract.class.php";

    class OneDB_FileSystem_Wrapper_OneDBWriteableFile extends OneDB_FileSystem_Wrapper_Abstract {
        
        protected $_tempFile = NULL;
        protected $_fhandle   = NULL;
        protected $_override = NULL;
        protected $_fpath    = NULL;
        protected $_name     = NULL;
        
        protected $_destruct = FALSE;
        
        function __construct( $filePath, $mode, &$onedb ) {
            
            $filePath = preg_replace('/^onedb\:[\/]+/', '/', $filePath );
            
            /* Test if a file with that name can exist */
            
            $namePart = end( explode( '/', $filePath ) );
            
            if (!preg_match('/^[a-z\d\s\-\._]+$/i', $namePart ) ) {
                throw new Exception("Invalid file name!");
            }
            
            $this->_name = $namePart;
            
            $this->_handle = $onedb;
            $this->_item   = NULL;
            
            //$this->_tempFile = tempnam( sys_get_temp_dir(), 'OneDB-write-');
            
            $index = '';
            
            while ( file_exists( sys_get_temp_dir() . '/' . ( $index == '' ? '' : $index . '-' ) . $namePart ) ) {
                $index = $index == '' ? 1 : $index++;
            }
            
            $this->_tempFile = sys_get_temp_dir() . '/' . ( $index == '' ? '' : $index . '-' ) . $namePart;
            
            file_put_contents( $this->_tempFile, '' );
            
            $this->_fhandle = @fopen( $this->_tempFile, $mode );
            
            try {

                if (!is_resource( $this->_fhandle )) {
                    throw new Exception("ERR_OPEN_FILE");
                }
            
                $this->_override = $this->_handle->getElementByPath( $filePath );
                
                if (!preg_match('/^File([\s]+|$)/', $this->_override->type))
                    throw new Exception("ERR_OPEN_FILE");
                
            } catch (Exception $e) {
            
                if ($e->getMessage() == 'ERR_OPEN_FILE')
                    throw $e;
                
                $this->_override = FALSE;
            }
            
            $this->_fpath = $filePath;
            
            $this->_destruct = TRUE;
            
        }
        
        function close( ) {
        
            try {
        
                if (!$this->_destruct)
                    return FALSE;
        
                //could not delete file from disk
                if (!fclose( $this->_fhandle ))
                    return FALSE;
            
                if ($this->_override !== FALSE) {
                    //Unlink old file
                    
                    if (!preg_match('/^File([\s]+|$)/', $this->_override->type ) ) {
                        $this->_override->delete();
                    } else {
                        
                        try {
                        
                        /* We've got an existing file, we only need to override it's bytes */
                        $storage = $this->_override->_getStorage();
                        $storage->onDelete();
                        $storage->storeFile( $this->_tempFile );
                        $this->_override->size = filesize( $this->_tempFile );
                        $this->_override->save();
                        $this->_override->name = $this->_name;
                        unset( $this->_override );
                        return TRUE;
                        
                        } catch (Exception $e) {
                            die($e->getMessage());
                        }
                    }
                }
            
                /* Create a new file */
                $pathParts    = explode('/', preg_replace('/[\/]+/', '/', $this->_fpath ) );
                $categoryName = '/' . implode('/', array_slice( $pathParts, 0, count( $pathParts ) - 1 ) ) . '/';
                $categoryName = preg_replace( '/[\/]+/', '/', $categoryName );
            
                $theCategory = $this->_handle->categories( array(
                    "selector" => $categoryName
                ) )->get(0);
                
                $this->_override = $theCategory->createArticle( 'File' );
                $this->_override->storeFile( $this->_tempFile );
                $this->_override->name = $this->_name;
                $this->_override->owner = ( isset( $_SESSION ) && isset( $_SESSION['UNAME'] ) ) ? "JSPlatform/Users/$_SESSION[UNAME]" : "OneDBFS/Anonymous";
                $this->_override->save();
                
                return TRUE;
            } catch (Exception $e) {
                trigger_error( $e->getMessage(), E_WARNING );
                return FALSE;
            }
        }
        
        function eof( ) {
            return feof( $this->_fhandle );
        }
        
        public function stat() {
            return fstat( $this->_fhandle );
        }
        
        function read( $count ) {
            return fread( $this->_fhandle, $count );
        }
        
        function seek( $offset, $whence = SEEK_SET ) {
            return fseek( $this->_fhandle, $offset, $whence );
        }
        
        function tell( ) {
            return ftell( $this->_fhandle );
        }
        
        function truncate( $new_size ) {
            return ftruncate( $this->_fhandle, $new_size );
        }
        
        function write( $data ) {
            return fwrite( $this->_fhandle, $data, strlen( $data ) );
        }
        
        public function __destruct() {
            if ($this->_tempFile !== FALSE)
                @unlink( $this->_tempFile );
        }
    }

?>