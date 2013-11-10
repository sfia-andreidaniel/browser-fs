<?php

    
    class OneDB_Storage_FileClone {
    
        private $_article = NULL;
        private $_properties = array();
        
        public function __construct( $article ) {
            $this->_article = $article;
        }
        
        public function __get( $propertyName ) {
            if ( !isset( $this->_properties[ $propertyName ] ) ) {
                return $this->_article->{"$propertyName"};
            } else return $this->_properties[ $propertyName ];
        }
        
        // We never allow setters to propagate on original article
        public function __set( $propertyName, $propertyValue ) {
            $this->_properties[ $propertyName ] = $propertyValue;
        }
        
        public function getModifiedProperties() {
            return $this->_properties;
        }
        
        public function __call( $methodName, $arguments ) {
            return call_user_func_array( array( $this->_article, $methodName ), $arguments );
        }
    }

    abstract class OneDB_Storage {
    
        protected $_article = NULL;
    
        function __construct( &$article ) {
            $this->_article = $article;
        }
        
        /* Stores a file located on disk in current item document */
        abstract public function storeFile( $filePath );
        
        /* Stores a file located on a URL in current item document */
        abstract public function storeUrl( $url );
        
        /* Executes a custom plugin to current item. If a file version
           is situated on disk, provide it to $fileOnDisk
         */
        abstract public function executePlugin( $pluginName, $fileLocationOnDisk = NULL );
        
        /* Sets the content to current file */
        abstract public function setContent( $bytes = NULL, $mime = NULL );
        
        /* Returns a file pointer to current item file */
        abstract public function getFile();
        
        /* Trigger that is executed when the item is deleted.
           Here you should code database-cleanup, dependencies deletion, etc
         */
        abstract public function onDelete();
        
        /* Returns a file pointer or a stream to current image
           or icon of this item
         */
        abstract public function getImageStream();
        
        /* Returns the available transcoded file formats */
        abstract public function getTranscodedFileFormats();
        
        /* Returns the best format for the available video file
           based on input $format
         */
        abstract public function bestTranscodeMatch( $format );
        
        /* Some storage engines might need a routine for cleanup
           after moving to other storage type has been successfully made */
        
        public function migrationBegin() {
            return true;
        }
        
        public function migrationEnd() {
            return true;
        }
        
        /* A function that returns an array containing the video snapshots
           of the file if the file is a video one */
        public function getVideoSnapshotsImageList() {
            if ( preg_match('/^video(\/|$)/', $this->_article->mime ) ) {
                return array();
            } else return NULL;
        }
        
        /* A function that returns the available video qualities of the
           file is the file is a video one */
        
        public function getVideoTranscodedVersions() {
            if ( preg_match('/^video(\/|$)/', $this->_article->mime ) ) {
                return array( 'mp4' );
            } else
                return NULL;
        }
        
        public function moveToStorage( $storageType ) {
        
            if ($storageType == $this->_article->storageType)
                return TRUE;
            
            $this->migrationBegin();
            
            /* Saves the file to disk first */
            
            $tmp = sys_get_temp_dir();
            $fName = $this->_article->name;
            
            $prefix= '';
            
            $diskName = '';
            
            while ( file_exists( $diskName = ( $tmp . DIRECTORY_SEPARATOR . ( $prefix == '' ? '' : $prefix . '-' ) . $fName ) ) ) {
                $prefix = $prefix == '' ? 1 : $prefix++;
            }
            
            $fhandle = fopen( $diskName, 'w' );
            
            if (!is_resource( $fhandle )) {
                throw new Exception("Could not create a copy of the file on disk - File creation denied!");
            }
            
            $stream = $this->getFile()->getResource();
            
            while (!feof( $stream ) ) {
                $buffer = @fread( $stream, 8192 );
                if ($buffer != FALSE) {
                    @fwrite( $fhandle, $buffer );
                }
            }
            
            @fclose( $stream );
            @fclose( $fhandle );
            
            if (filesize( $diskName ) != $this->_article->size ) {
                @unlink( $diskName );
                throw new Exception("Disk version differs with storage version (" . $this->_article->size . " != " . filesize( $diskName ) . ")" );
            }
            
            try {
                /* We have a file on disk, now we try to move the file on the cloud */
                $articleClone = new OneDB_Storage_FileClone( $this->_article );
            
                $StorageTypeClassName = 'OneDB_Storage_' . $storageType;
            
                if (!class_exists( $StorageTypeClassName ) ) {
                    if (file_exists( dirname(__FILE__) . '/plugins/core.OneDB_Storage.class/' . $StorageTypeClassName . '.class.php' )) {
                        require_once dirname(__FILE__) . '/plugins/core.OneDB_Storage.class/' . $StorageTypeClassName . '.class.php';
                        if (!class_exists( $StorageTypeClassName ))
                            throw new Exception("Storage class $StorageTypeClassName not found (although a require has been successfully made on it's class)");
                    } else
                    throw new Exception("Storage class $StorageTypeClassName not found!");
                } 
            
                $articleClone->storageType = $storageType;
                $articleClone->_storage = new $StorageTypeClassName( $articleClone );
                $articleClone->_storage->storeFile( $diskName );
                
                /* If an error occurs after this point, it's crytical! File might be lost! */
                
                $this->onDelete();
                
                $modifiedProperties = $articleClone->getModifiedProperties();
                
                foreach (array_keys( $modifiedProperties ) as $propertyName ) {
                    if (!in_array( $propertyName, array( '_storage', 'name', 'mime', 'size', '_id', 'date', 'modified', 'modifier', 'keywords', 'tags', 'owner', 'order', 'type' ) ) ) {
                        $this->_article->{"$propertyName"} = $modifiedProperties[ $propertyName ];
                    }
                }
                
                $this->_article->setStorageType( $storageType );
                $this->_article->save();
                
                $this->migrationEnd();
                
            } catch (Exception $e) {
                // echo "Exception: ", $e->getMessage(), " in ", $e->getFile(), ":", $e->getLine(), "\n\n";
                @unlink( $diskName );
                throw $e;
            }
            
            @unlink( $diskName );
        }
        
        public function __toString() {
            return '';
        }
    }

?>