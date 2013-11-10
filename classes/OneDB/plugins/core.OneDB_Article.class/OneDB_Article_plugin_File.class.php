<?php

    class OneDB_Article_plugin_File {
    
        protected $_ = NULL;
        public    $_storage = NULL;
        protected $_defaultStorage = 'database';
        
        public function __construct( &$that ) {
            $this->_ = $that;
            
            $this->_defaultStorage = ( $storageType = $that->_collection->db->onedb->defaultStorageType ) ? $storageType : 'database';
            
            $this->_->_addGetter("type", function( &$that ) {
                return "File " . $that->mime;
            });
            
            $this->setStorageType( $this->storageType !== NULL ? $this->storageType : $this->_defaultStorage );
            
            $this->_->addEventListener('delete', function( &$that ) {
                $that->_getStorage()->onDelete();
            });
        }
        
        public function _getStorage() {
            return $this->_storage;
        }
        
        public function _getStorageType() {
            return $this->_storage . '';
        }
        
        public function metaFile() {
            require_once "OneDB_MetaFile.class.php";
            $metaObject = new OneDB_MetaFile( $this );
            return $metaObject;
        }
        
        public function setStorageType( $storageTypeName = 'database' ) {
            
            if ( empty( $storageTypeName ) )
                throw new Exception("Must supply a name for storageTypeName");
            
            if ($storageTypeName == $this->_->storageType && $this->_storage !== NULL)
                return;
            
            if (!class_exists( "OneDB_Storage_$storageTypeName" )) {
                if (file_exists( $requireFile = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'core.OneDB_Storage.class' . DIRECTORY_SEPARATOR . 'OneDB_Storage_' . $storageTypeName . '.class.php' ) ) {
                    require_once $requireFile;
                    if (!class_exists( "OneDB_Storage_$storageTypeName" ))
                        throw new Exception("StorageType class not found in required file $requireFile");
                } else {
                    throw new Exception("A StorageType class was not found in 'plugins/core.OneDB_Storage.class/$storageTypeName.class.php");
                }
            }
            
            $className = "OneDB_Storage_$storageTypeName";
            
            $this->_storage = new $className( $this );
            $this->_->storageType = $storageTypeName;
        }
        
        public function __get( $propertyName ) {
            return $this->_->{$propertyName};
        }
        
        public function __set( $propertyName, $propertyValue ) {
            $this->_->{$propertyName} = $propertyValue;
        }
        
        public function __call( $methodName, $args ) {
            if (!method_exists( $this, $methodName ))
                return call_user_func_array( array( $this->_, $methodName ), $args );
            else 
                return call_user_func_array( array( $this, $methodName ), $args );
        }
        
        private function getUniqueFileName( $fileName ) {
        
        
            $filteredName = preg_replace('/[^a-z0-9\-\_\.\s]+/i', " ", $fileName);
            $filteredName = preg_replace('/[\s]+/', " ", $filteredName);
            
            $parts = explode('.', $filteredName);
            
            if (count($parts) > 1) {
                $extension = "." . implode('.', array_slice( $parts, 1 ));
                $baseName  = $parts[0];
            } else {
                $baseName  = $parts[0];
                $extension = "";
            }
            
            $add = "";
            
            $exists = @$this->_collection->db->articles->findOne(
                array(
                    "_parent" => MongoIdentifier( $this->_parent ),
                    "name"    => "$baseName$add$extension"
                )
            );
            
            if (!$exists) return "$baseName$add$extension";
            
            $add = 0;
            
            do {

                $add++;
            
                $exists = @$this->_collection->db->articles->findOne(
                    array(
                        "_parent" => MongoIdentifier( $this->_parent ),
                        "name"    => "$baseName-$add$extension"
                    )
                );
            
            } while ($exists);
            
            return "$baseName-$add$extension";
        }
        
        public function storeFile( $fPath ) {
            return $this->_storage->storeFile( $fPath );
        }
        
        public function executePlugin( $pluginName, $fileOnDisk = NULL ) {
            return $this->_storage->executePlugin( $pluginName, $fileOnDisk );
        }
        
        public function storeURL( $url ) {
            return $this->_storage->storeURL( $url );
        }
        
        /* The content should be an array in format:
          "name" => <file_name>,
          "mime" => <file_mime>,
          "content" => file_raw_data
        */

        public function setContent( $content = NULL, $mime = NULL ) {
            return $this->_storage->setContent( $content, $mime );
        }
        
        public function getFile() {
            return $this->_storage->getFile();
        }
    }

?>