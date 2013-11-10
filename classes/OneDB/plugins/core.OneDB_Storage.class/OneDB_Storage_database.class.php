<?php

    if (!class_exists( 'OneDB_Storage' ) )
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'OneDB_Storage.class.php';

    class OneDB_Storage_database extends OneDB_Storage {
    
        protected $_articleFileID = NULL;

        public function migrationBegin() {
            $this->_articleFileID = $this->_article->fileID;
        }
        
        public function migrationEnd() {
            if (is_object( $this->_articleFileID ))
                $this->_article->_collection->db->getGridFS()->delete( $this->_articleFileID );
        }

        public function __construct( &$article ) {
            parent::__construct( $article );
        }

        public function storeFile( $filePath ) {
            if (!file_exists( $filePath ))
                throw new Exception("Local file `$fPath` was not found");

            $info = pathinfo( $filePath );

            $this->_article->name = $this->_article->getUniqueFileName( $info['basename'] );

            require_once "MIME/Type.php";
            $this->_article->mime = $theMime = MIME_Type::autoDetect( $filePath );
            $this->_article->size = filesize( $filePath );

            $gridFS = $this->_article->_collection->db->getGridFS();
            $fileID = $gridFS->storeFile( $filePath );

            $this->_article->fileID = $fileID;

            if (isset($_SESSION) && isset($_SESSION['UNAME']))
                $this->_article->owner = "JSPlatform/Users/$_SESSION[UNAME]";

            global $__OneDB_Mime_Plugins__;

            foreach (array_keys( $__OneDB_Mime_Plugins__ ) as $regExpr) {
                if (preg_match($regExpr, $theMime)) {
                    foreach ($__OneDB_Mime_Plugins__[ $regExpr ] as $funcToCall) {
                        if (!function_exists( $funcToCall )) {
                            if (file_exists( dirname(__FILE__)."/../$funcToCall.inc.php"))
                                require_once dirname(__FILE__)."/../$funcToCall.inc.php";
                            if (!function_exists( $funcToCall ))
                                throw new Exception("Invalid function name '$funcToCall' in __OneDB_Mime_Plugins__ at expression $regExpr (although a file require was tried to be made from 'plugins/$funcToCall.inc.php'");
                        }
                        $funcToCall( $filePath, $this->_article );
                    }
                }
            }

        }

        public function storeUrl( $url ) {
            $info = parse_url( $url );
            $upath = isset( $info['path'] ) ? $info['path'] : NULL;

            if ($upath === NULL)
                throw new Exception("Cannot parse a path from url $url");

            $fileName = end( explode( "/", trim( str_replace( '..', '.', $upath ), ' /' )));

            if (!strlen( $fileName ) || $fileName == '.')
                throw new Exception("Cannot determine fileName from URL!");

            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_REFERER, $url );
            curl_setopt( $ch, CURLOPT_URL,     $url );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4');

            $buffer = curl_exec( $ch );
            $info = curl_getinfo( $ch );
            
            if (empty( $buffer ) || (int)$info['http_code'] != 200 ) {
                throw new Exception("Error downloading url: $url via cURL ( code = $info[http_code] )");
            }

            file_put_contents( "/tmp/$fileName", $buffer );

            $this->storeFile( "/tmp/$fileName" );

            @unlink( "/tmp/$fileName" );
        }

        public function executePlugin( $pluginName, $fileLocationOnDisk = NULL ) {
        
            if (!function_exists( $pluginName )) {

                if (file_exists( $requiredFile = dirname(__FILE__) . "/../$pluginName.inc.php"))
                    require_once $requiredFile;

                if (!function_exists( $pluginName )) {
                    throw new Exception("Invalid function name '$pluginName' ( attempted to load it from '$requiredFile'! )");
                }
            }

            $infinite = 0;

            $unlinkAfter = FALSE;

            if (NULL === $fileLocationOnDisk) {

                $unlinkAfter = TRUE;

                /* If fileOnDisk is null, we store the file somewhere temporary on disk in the /tmp filesystem */
                while (
                    file_exists( $tempFname = sys_get_temp_dir() . DIRECTORY_SEPARATOR . time() . '-' . rand(1,1000000) . '-' . $this->_article->name )
                ) {
                    if ($infinite++ > 100)
                        throw new Exception("Could not create temporary file on disk");
                }
                
                /* Dump file on disk */
                $stream = $this->getFile()->getResource();
                $dest = fopen( $tempFname, 'w' ) or die("Could not create file on disk: $tempFname while trying to execute plugin $pluginName");

                while (!feof( $stream )) {
                    $buffer = fread( $stream, 8192 );
                    fwrite( $dest, $buffer );
                }
                
                fclose( $stream );
                fclose( $dest );

                $fileLocationOnDisk = $tempFname;
            }
            
            $pluginResult = $pluginName( $fileLocationOnDisk, $this->_article );

            if ($unlinkAfter)
                @unlink( $fileLocationOnDisk );

            return $pluginResult;
        }
                                    //$content
        public function setContent( $bytes = NULL, $mime = NULL ) {
            if ($this->_article->mime === NULL && empty($mime))
                throw new Exception("You must declare file mime at least first time!");

            $oldFileID = $this->_article->fileID . '';
            $gridFS = $this->_article->_collection->db->getGridFS();

            /* Drop old file from database if needed */
            if (!empty($oldFileID)) {
                /* Delete the old file! */
                if (!$gridFS->delete( MongoIdentifier( $oldFileID ) ) ) {
                    throw new Exception("Could not remove the old file!");
                }
            }
            
            if ($mime !== NULL)
                $this->_article->mime = $mime;

            $content = "$bytes";
            $this->_article->size = strlen( $content );

            $fileID = $gridFS->storeBytes( $content, array(
                'safe' => TRUE
            ) );

            $this->_article->fileID = $fileID;
        }

        public function getFile() {

            if ( !( $fileID = $this->_article->fileID) )
                throw new Exception("No file was stored yet in this article!");

            $filePtr = $this->_article->_collection->db->getGridFS()->findOne(
                array(
                    "_id" => MongoIdentifier( $fileID )
                )
            );

            return $filePtr;
        }
        
        public function onDelete() {

            $_unlink = $this->_article->_unlink;
            $_db = $this->_article->_collection->db;

            $this->_article->deleteDependencies();

            if ($this->_article->fileID) {
                $this->_article->_collection->db->getGridFS()->remove(
                    array(
                        "_id" => MongoIdentifier($this->_article->fileID)
                    ),
                    array(
                        "safe" => true
                    )
                );
            }
            
            $this->_article->deleteProperty( 'fileID' );
            $this->_article->deleteProperty( '_unlink' );
        }
        
        public function getImageStream() {
            if (preg_match('/^image(\/|$)/', $this->_article->mime )) {
                return $this->getFile();
            } else {
                if (preg_match('/^video(\/|$)/', $this->_article->mime) &&
                    preg_match('/^ondeb(\:|\/)/', $this->_article->icon) ) {
                    
                    if ( preg_match('/([a-f0-9]+)$/', $this->_article->icon, $matches) ) {
                        $thumbID = $matches[1];
                        
                        $image = $this->_article->_collection->db->onedb->articles( array(
                            "_id" => MongoIdentifier( $thumbID )
                        ), array(), 1 );
                        
                        if ($image->length) {
                            try {
                                return $image->get(0)->_getStorage()->getImageStream();
                            } catch (Exception $e) {}
                        }
                    }
                    
                }
            }
            
            return NULL;
        }
        
        public function getTranscodedFileFormats() {
            return array(
                'mp4',
                'flv'
            );
        }
        
        public function bestTranscodeMatch( $format ) {
            $format = trim( strtolower( $format ) );
            return in_array( $format, $this->getTranscodedFileFormats() ) ? $format : 'mp4';
        }
        
        public function getVideoSnapshotsImageList( ) {
        
            if ( preg_match('/^video(\/|$)/', $this->_article->mime ) ) {
                return array(
                    '/onedb/picture/' . $this->_article->_id
                );
            } else
                return NULL;
        }
        
        public function getVideoTranscodedVersions() {
            if ( preg_match('/^video(\/|$)/', $this->_article->mime ) ) {
                return array(
                    'mp4'
                );
            } else
                return NULL;
        }
        
        public function __toString() {
            return "database";
        }
    }

?>