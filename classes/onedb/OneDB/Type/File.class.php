<?php
    
    require_once __DIR__ . '/../Type.class.php';
    
    class OneDB_Type_File extends OneDB_Type {
        
        private $_fileId        =    0;  // sample value: 35
        private $_fileVersions  =   [];  // sample value: { "original": "http://....", "240p.mp4": "http://...." }
        private $_fileSize      =    0;  // sample value: 10230123
        private $_fileType      =   '';  // sample value: "video", "audio", "image", "etc"
        private $_fileDuration  =    0;  // sample value: 233.45
        private $_fileMime      =   '';  // sample value: "video/mp4"
        private $_fileExtension =   '';  // sample value: "mp4"
        private $_fileVideoInfo = NULL;  // sample value: { "width": <int>, "height": <int>, "bitrate": <int>, "codec": <string>, "codecDetails": <string>, "fps": <float>, "canvasSize": <int>, "totalFrames": <int> }
        private $_fileAudioInfo = NULL;  // sample value: { "samplerate": <int>, "bitrate": <int>, "codec": <string>, "codecDetails": <string> }
        
        public function exportOwnProperties( array &$properties ) {
            
            $properties[ 'fileId' ]        = $this->_fileId;
            $properties[ 'fileVersions' ]  = $this->_fileVersions;
            $properties[ 'fileSize' ]      = $this->_fileSize;
            $properties[ 'fileType' ]      = $this->_fileType;
            $properties[ 'fileDuration' ]  = $this->_fileDuration;
            $properties[ 'fileMime' ]      = $this->_fileMime;
            $properties[ 'fileExtension' ] = $this->_fileExtension;
            $properties[ 'fileVideoInfo' ] = $this->_fileVideoInfo;
            $properties[ 'fileAudioInfo' ] = $this->_fileAudioInfo;
        }
        
        public function importOwnProperties( array $properties ) {
            
            $this->_fileId       = isset( $properties[ 'fileId' ] )       ? $properties[ 'fileId' ]       : NULL;
            $this->_fileVersions = isset( $properties[ 'fileVersions' ] ) ? $properties[ 'fileVersions' ] : [];
            $this->_fileSize     = isset( $properties[ 'fileSize' ] )     ? $properties[ 'fileSize']      : 0;
            $this->_fileType     = isset( $properties[ 'fileType' ] )     ? $properties[ 'fileType' ]     : '';
            $this->_fileDuration = isset( $properties[ 'fileDuration' ])  ? $properties[ 'fileDuration' ] : 0;
            $this->_fileMime     = isset( $properties[ 'fileMime' ] )     ? $properties[ 'fileMime' ]     : '';
            $this->_fileExtension= isset( $properties[ 'fileExtension' ] )? $properties[ 'fileExtension'] : '';
            $this->_fileVideoInfo= isset( $properties[ 'fileVideoInfo' ] )? $properties[ 'fileVideoInfo' ]: NULL;
            $this->_fileAudioInfo= isset( $properties[ 'fileAudioInfo' ] )? $properties[ 'fileAudioInfo' ]: NULL;
        }
        
        public function __mux() {
            return [
                'fileId'          => $this->_fileId,
                'fileVersions'    => $this->_fileVersions,
                'fileSize'        => $this->_fileSize,
                'fileType'        => $this->_fileType,
                'fileDuration'    => $this->_fileDuration,
                'fileMime'        => $this->_fileMime,
                'fileVideoInfo'   => $this->_fileVideoInfo,
                'fileAudioInfo'   => $this->_fileAudioInfo
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
            return $this->_root->server->storage->getBestFileMatchVersion( $this->_fileVersions, $format );
        }
        
        // Internal validation functions
        protected function _is_files_array_( $arr ) {
            
            if ( !is_array( $arr ) )
                return FALSE;
            
            foreach ( array_keys( $arr ) as $key ) {
                if ( !is_string( $arr[ $key ] ) )
                    return FALSE;
            }
            
            return isset( $arr[ 'original' ] )
                ? TRUE : FALSE;
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
    
    OneDB_Type_File::prototype()->defineProperty( "fileDuration", [
        "get" => function() {
            return $this->_fileDuration;
        }
    ] );
    
    OneDB_Type_File::prototype()->defineProperty( "fileMime", [
        "get" => function() {
            return $this->_fileMime;
        }
    ] );
    
    OneDB_Type_File::prototype()->defineProperty( "fileExtension", [
        "get" => function() {
            return $this->_fileExtension;
        }
    ] );
    
    OneDB_Type_File::prototype()->defineProperty( "fileVideoInfo", [
        "get" => function() {
            return $this->_fileVideoInfo;
        }
    ] );
    
    OneDB_Type_File::prototype()->defineProperty( "fileAudioInfo", [
        "get" => function() {
            return $this->_fileAudioInfo;
        }
    ] );
    
    /*
    
    // THIS IS A BROWSER CL SAMPLE UPLOAD API RESPONSE FOR TESTING PURPOSES.

    {
        "name": "file.mp4",

        "size": 2578488,

        "files": {
            "original": "http://storage01:80/2013/11/25/1385414456283-1493-file.mp4",
            "240p.mp4": "http://storage01:80/2013/11/25/1385414456283-1493-file.mp4.240p.mp4",
            "webm"    : "http://storage01:80/2013/11/25/1385414456283-1493-file.mp4.webm"
        },

        "uploadId": 35,

        "fileInfo": {
            "type": "video",
            "extension": "mp4",
            "mime": "video/mp4",
            "parser": "VideoParser"
        },

        "parserFileInfo": {
            "duration": 39.41,
            "video": {
                "width": 432,
                "height": 240,
                "bitrate": 484,
                "codec": "h264",
                "codecDetails": "avc1 / 0x31637661",
                "fps": 29.97002997003,
                "canvasSize": 103680,
                "totalFrames": 1181
            },
            "audio": {
                "samplerate": 44100,
                "bitrate": 31,
                "codec": "aac",
                "codecDetails": "mp4a / 0x6134706D"
            }
        }
    }
    
    */
    
    // Use this property to initialize a file
    OneDB_Type_File::prototype()->defineProperty( "storageResponseData", [
        
        "get" => function() {
            return [
                "fileId"        => $this->_fileId,
                "fileVersions"  => $this->_fileVersions,
                "fileSize"      => $this->_fileSize,
                "fileType"      => $this->_fileType,
                "fileDuration"  => $this->_fileDuration,
                "fileMime"      => $this->_fileMime,
                "fileExtension" => $this->_fileExtension,
                "fileVideoInfo" => $this->_fileVideoInfo ? $this->_fileVideoInfo : [],
                "fileAudioInfo" => $this->_fileAudioInfo ? $this->_fileAudioInfo : []
            ];
            
        },
        
        "set" => function( $storageApiResponse ) {
        
            if ( !is_array( $storageApiResponse ) )
                throw Object( 'Exception.Storage', 'Invalid property. Expected a storage api response object!' );
            
            // reset all file fields
            $this->_fileId        =    0;  // [x] sample value: 32232
            $this->_fileVersions  =   [];  // [x] sample value: { "original": "http://....", "240p.mp4": "http://...." }
            $this->_fileSize      =    0;  // [x] sample value: 10230123
            $this->_fileType      =   '';  // [x] sample value: "video", "audio", "image", "etc"
            $this->_fileDuration  =    0;  // [x] sample value: 233.45
            $this->_fileMime      =   '';  // [x] sample value: "video/mp4"
            $this->_fileExtension =   '';  // [x] sample value: "mp4"
            $this->_fileVideoInfo = NULL;  // [x] sample value: { "width": <int>, "height": <int>, "bitrate": <int>, "codec": <string>, "codecDetails": <string>, "fps": <float>, "canvasSize": <int>, "totalFrames": <int> }
            $this->_fileAudioInfo = NULL;  // [x] sample value: { "samplerate": <int>, "bitrate": <int>, "codec": <string>, "codecDetails": <string> }
            
            // populate all file fields while doing strict detection to the
            // fields provided by the storage api response
            if ( isset( $storageApiResponse[ 'size' ] ) && is_int( $storageApiResponse[ 'size' ] ) 
            ) $this->_fileSize = $storageApiResponse[ 'size' ];
            
            if ( isset( $storageApiResponse[ 'files' ] )
                 && $this->_is_files_array_( $storageApiResponse[ 'files' ] )
            ) $this->_root->_change( 'fileVersions', $this->_fileVersions = $storageApiResponse[ 'files' ] );
            
            if ( isset( $storageApiResponse[ 'uploadId' ] ) && is_int( $storageApiResponse[ 'uploadId' ] ) 
            ) $this->_root->_change( 'fileId', $this->_fileId = $storageApiResponse[ 'uploadId' ] );
            
            if ( isset( $storageApiResponse[ 'fileInfo' ] ) && is_array( $storageApiResponse[ 'fileInfo' ] ) ) {
                
                if ( isset ($storageApiResponse[ 'fileInfo' ][ 'type' ] ) && is_string( $storageApiResponse[ 'fileInfo' ][ 'type' ] ) 
                ) $this->_root->_change( 'fileType', $this->_fileType = $storageApiResponse[ 'fileInfo' ][ 'type' ] );
                
                if ( isset( $storageApiResponse[ 'fileInfo' ][ 'extension' ] ) && is_string( $storageApiResponse[ 'fileInfo' ][ 'extension' ] )
                ) $this->_root->_change( 'fileExtension', $this->_fileExtension = $storageApiResponse[ 'fileInfo' ][ 'extension' ] );
                
                if ( isset( $storageApiResponse[ 'fileInfo' ][ 'mime' ] ) && is_string( $storageApiResponse[ 'fileInfo' ][ 'mime' ] )
                ) $this->_root->_change( 'fileMime', $this->_fileMime = $storageApiResponse[ 'fileInfo' ][ 'mime' ] );
                
            }
            
            if ( isset( $storageApiResponse[ 'parserFileInfo' ] ) && is_array( $storageApiResponse[ 'parserFileInfo' ] ) ) {
                
                if ( isset( $storageApiResponse[ 'parserFileInfo' ]['duration'] ) && is_float( $storageApiResponse[ 'parserFileInfo' ][ 'duration' ] )
                ) $this->_root->_change( 'fileDuration', $this->_fileDuration = $storageApiResponse[ 'parserFileInfo' ][ 'duration' ] );
                
                if ( isset( $storageApiResponse[ 'parserFileInfo' ]['video' ] ) && is_array( $storageApiResponse[ 'parserFileInfo' ][ 'video' ] )
                ) $this->_root->_change( 'fileVideoInfo', $this->_fileVideoInfo = $storageApiResponse[ 'parserFileInfo' ][ 'video' ] );
                
                if ( isset( $storageApiResponse[ 'parserFileInfo' ]['audio' ] ) && is_array( $storageApiResponse[ 'parserFileInfo' ][ 'audio' ] )
                ) $this->_root->_change( 'fileAudioInfo', $this->_fileAudioInfo = $storageApiResponse[ 'parserFileInfo' ][ 'audio' ] );
                
            }
        
        }
    ] );
    
?>