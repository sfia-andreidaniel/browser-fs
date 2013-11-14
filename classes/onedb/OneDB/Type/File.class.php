<?php
    
    require_once __DIR__ . '/../Type.class.php';
    
    class OneDB_Type_File extends OneDB_Type {
        
        private $_fileId       = 0;
        private $_fileVersions = [];
        private $_fileSize     = 0;
        private $_fileType     = '';
        
        public function exportOwnProperties( array &$properties ) {
            
            $properties[ 'fileId' ]       = $this->_fileId;
            $properties[ 'fileVersions' ] = $this->_fileVersions;
            $properties[ 'fileSize' ]     = $this->_fileSize;
            $properties[ 'fileType' ]     = $this->_fileType;
            
        }
        
        public function importOwnProperties( array $properties ) {
            
            $this->_fileId       = isset( $properties[ 'fileId' ] )       ? $properties[ 'fileId' ]       : NULL;
            $this->_fileVersions = isset( $properties[ 'fileVersions' ] ) ? $properties[ 'fileVersions' ] : [];
            $this->_fileSize     = isset( $properties[ 'fileSize' ] )     ? $properties[ 'fileSize']      : 0;
            $this->_fileType     = isset( $properties[ 'fileType' ] )     ? $properties[ 'fileType' ]     : '';
            
        }
        
        public function __mux() {
            return [
                'fileId'       => $this->_fileId,
                'fileVersions' => $this->_fileVersions,
                'fileSize'     => $this->_fileSize,
                'fileType'     => $this->_fileType
            ];
        }
        
        protected function on_unlink() {
            // Make cleanup...
        }
        
        /* @parameter: $extension: string. e.g.: "240p.mp4", "jpg", "360p.mp4", "webm", etc.
        
           RETURN: 
        
           - on success  : Object {
                "format": <string>,
                "url"   : <string>,
                "exact" : TRUE | FALSE
           }
        
           - on not found:   NULL
        */
        public function getFileFormat( $format ) {
            
        }
    }
    
    OneDB_Type_File::prototype()->defineProperty( "fileId", [
        
        "get" => function() {
            return $this->_fileId;
        }
        
    ] );
    
    OneDB_Type_File::prototype()->defineProperty( "fileVersions", [
        
        "get" => function() {
            return $this->_fileVersions;
        }
        
    ] );
    
    OneDB_Type_File::prototype()->defineProperty( "fileSize", [
    
        "get" => function() {
            return $this->_fileSize;
        }
    
    ] );
    
    OneDB_Type_File::prototype()->defineProperty( "fileType", [
        
        "get" => function() {
            return $this->_fileType;
        }
        
    ] );
    
?>