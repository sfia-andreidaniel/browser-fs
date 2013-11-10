<?php

    if (!class_exists( 'OneDB_Storage' ) )
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'OneDB_Storage.class.php';

    if (!class_exists( 'OneDB_URLFile' ) )
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'OneDB_URLFile.class.php';

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'rdscloud/AddRecordToStorageAdvanced.php';
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'rdscloud/DelRecordFromStorage.php';
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'rdscloud/SetBytesOnStorage.php';

    class OneDB_Storage_rdscloud extends OneDB_Storage {

        private $_user = '';
        private $_pass = '';

        public function __construct( &$article ) {
            parent::__construct( $article );
            
            $authInfo = $article->_collection->db->onedb->registry()->{"Storage.rcsrds.auth"};

            if ($authInfo === NULL)
                throw new Exception("Storage.rcsrds.auth registry setting was not set!");
            
            $arr = explode(':', $authInfo);
            if (count($arr) != 2)
                throw new Exception("Storage.rcsrds.auth registry setting should be in format <user>:<password>");
            
            $this->_user = $arr[0];
            $this->_pass = $arr[1];
        }

        public function storeFile( $filePath ) {

            // determine the file name, weather it's from an URL weather it's from a file
            
            if ( file_exists( $filePath ) ) {
                $info = pathinfo( $filePath );
                $fileBaseName = $info['basename'];
            } else {
                $info = parse_url( $filePath );
                
                if (!isset( $info['path'] ) )
                    throw new Exception("Bad filePath URL! (assumed it's an url because file does not exist on disk!)");
                
                $fileBaseName = $info['path'];
                $fileBaseName = trim( $fileBaseName, '/ ' );
                $fileBaseName = end( explode( '/', $fileBaseName ) );
                $fileBaseName = empty( $fileBaseName ) ? "File" : $fileBaseName;
            }

            $this->_article->name = $this->_article->getUniqueFileName( $fileBaseName );
            
            $data = @bindAddRecordToStorageAdvanced( $this->_user, $this->_pass, $filePath );
            
            $data = @json_decode( $data, TRUE );
            
            if (!is_array( $data ))
                throw new Exception("Could not add file to cloud: Bad cloud api response!");
            
            if (!isset( $data['ok'] ) || !$data['ok'] )
                throw new Exception("Could not add file to cloud: Cloud error!");
            
            if (!isset( $data['path'] ) || empty( $data['path'] ))
                throw new Exception("Cloud did not returned a fileURL");
            
            
            $this->_article->mime   = isset( $data['mime'] ) ? $data['mime'] : 'application/octet-stream';
            $this->_article->size   = isset( $data['filesize'] ) && preg_match('/^[\d]+$/', "$data[filesize]" ) ? (int)$data['filesize'] : -1;

            $this->_article->deleteProperty('fileID');
            
            
            $this->_article->fileURL = $data['path'];
            
            $cloud = array();
            
            if (isset( $data['versions'] ) && is_array( $data['versions'] ) && count( $data['versions'] ) ) {
                $cloud['versions'] = array();
                foreach (array_keys( $data['versions'] ) as $versionName ) {
                    $cloud['versions'][ substr( $versionName, 1 ) ] = $data['versions'][ $versionName ];
                }
            }
            
            if (isset( $data['snapshots'] ) && is_array( $data['snapshots'] ) && count( $data['snapshots']) ) {
                $cloud['snapshots'] = $data['snapshots'];
                $this->_article->cloudIcon = reset( $cloud['snapshots'] );
            }
            
            if (count( array_values( $cloud ))) 
                $this->_article->cloud = $cloud;
            else
                $this->_article->deleteProperty("cloud");

            if (isset($_SESSION) && isset($_SESSION['UNAME']))
                $this->_article->owner = "JSPlatform/Users/$_SESSION[UNAME]";
        }

        public function storeUrl( $url ) {
            $this->storeFile( $url );
        }

        public function executePlugin( $pluginName, $fileLocationOnDisk = NULL ) {
            /* Execution of plugins is not implemented in this storage type */
            return TRUE;
        }

        public function setContent( $bytes = NULL, $mime = NULL ) {
            
            try {
                $response = @bindSetBytesOnStorage( $this->_user, $this->_pass, "$bytes", $this->fileURL );
                $dataJ = @json_decode( $response );
                
                if (!is_array( $dataJ ) || !isset( $dataJ['ok'] ) || $dataJ['ok'] !== TRUE)
                    throw new Exception("RDSCloud Exception: Bad response from cloud!\n" . $data);
                
            } catch (Exception $e) {
                throw $e;
            }
            
            return TRUE;
            
            //throw new Exception("This type of storage does not support file overriding!");
        }

        public function getFile() {

            if ( !( $fileURL = $this->_article->fileURL ) )
                throw new Exception("No file was stored yet in this article!");
            
            return new OneDB_URLFile( $fileURL );
        }
        
        public function onDelete() {
            
            try {
                $data = @bindDelRecordFromStorage( $this->_user, $this->_pass, $this->_article->fileURL );
                $dataJ = json_decode( $data, TRUE );
                
                if (!is_array( $dataJ ) || !isset( $dataJ['ok'] ) || $dataJ['ok'] !== TRUE)
                    throw new Exception("RDSCloud Exception: Bad response from cloud!\n" . $data);
                
            } catch (Exception $e) {
                throw $e;
            }
            
            $this->_article->deleteProperty('fileURL');
            $this->_article->deleteProperty('cloudIcon');
            $this->_article->deleteProperty('cloud');

        }
        
        public function getImageStream( ) {
            if ( preg_match( '/^image(\/|$)/', $this->_article->mime ) ) {
                return $this->getFile();
            } else {
                if ( preg_match('/^video(\/|$)/', $this->_article->mime) ) {
                    $cloudIcon = $this->_article->cloudIcon;
                    if ( strlen( "$cloudIcon" ) ) {
                        return new OneDB_URLFile( $cloudIcon );
                    }
                }
            }
            return NULL;
        }
        
        public function getVideoSnapshots() {
            
            $cloud = $this->_article->cloud;
            
            if (is_array( $cloud )) {
                if (isset( $cloud['snapshots'] ) && is_array( $cloud['snapshots'] ) && count( $cloud['snapshots'] ))
                    return $cloud['snapshots'];
            }
            
            return NULL;
        }
        
        public function setVideoSnapshot( $snapshotURL ) {
            $this->_article->cloudIcon = $snapshotURL;
        }
        
        public function getTranscodedFileFormats() {
            $cloud = $this->_article->cloud;

            if ( is_array( $cloud ) && isset( $cloud['versions'] ) && is_array( $cloud['versions'] ) ) {
                return array_keys( $cloud['versions'] );
            }
            
            return array();
        }
        
        private function storageTranscode( $format ) {
        
            // die( $format );
        
            header( "Location: " . $this->_article->cloud['versions'][ $format ] );
            die();
        }
        
        public function bestTranscodeMatch( $format ) {
            $format = trim( strtolower( $format ) );
            
            if (in_array( $format, $formats = $this->getTranscodedFileFormats() ) )
                return $this->storageTranscode( $format );
            
            $formatClasses = array();
            
            foreach ($formats as $f) {
                $arr = explode('.', $f);
                if (count($arr) == 1) {
                    $formatClasses[ $arr[0] ] = array( $arr[0] );
                } else {
                    if (!isset( $formatClasses[ $arr[1] ] ) )
                        $formatClasses[ $arr[1] ] = array();
                    
                    $formatClasses[ $arr[1] ][] = $f;
                }
            }
            
            header("Content-Type: text/plain");
            
            // echo "Request: $format";
            
            // print_r( $formatClasses );
            
            foreach ( array_keys( $formatClasses ) as $key )
                rsort( $formatClasses[ $key ] );
            
            if ( count( $arr = explode( '.', $format ) ) == 1 ) {
                
                if (isset( $formatClasses[ $format ] ) )
                    return $this->storageTranscode( end( $formatClasses[ $format ] ) );
                else
                    return $this->storageTranscode( '240p.mp4' );
                
            } else {
                
                list( $format, $subFormat ) = $arr;
                
                if ( isset( $formatClasses[ $format ] ) ) {
                    foreach ($formatClasses[ $format ] as $sub ) {
                        if ( strcmp( $sub, $subFormat ) < 0 )
                            return $this->storageTranscode( $format . '.' . $sub );
                    }
                    return $this->storageTranscode( end( $formatClasses[ $format ] ) );
                    
                } else return $this->storageTranscode( '240p.mp4' );
            }
        }
        
        public function getVideoSnapshotsImageList() {
            if ( preg_match('/^video(\/|$)/', $this->_article->mime ) ) {
            
                $data = $this->_article->toArray();
                
                if ( isset( $data['cloud'] ) && is_array( $data['cloud'] ) &&
                     isset( $data['cloud']['snapshots'] ) && is_array( $data['cloud']['snapshots'] )
                ) return $data['cloud']['snapshots'];
                
                return array(
                    '/onedb/picture/' . $this->_article->_id
                );
            
            } else
                return NULL;
        }
        
        public function getVideoTranscodedVersions() {
            if ( preg_match('/^video(\/|$)/', $this->_article->mime ) ) {
            
                $out = array();
                
                $data = $this->_article->toArray();
                
                if ( isset( $data['cloud'] ) && is_array( $data['cloud'] ) &&
                     isset( $data['cloud']['versions'] ) && is_array( $data['cloud']['versions'] )
                ) $out = array_keys( $data['cloud']['versions'] );
                
                $out[] = 'mp4';
                
                sort( $out );
                
                return $out;
            
            } else
                return NULL;
        }
        
        public function __toString() {
            return "rdscloud";
        }
    }

?>