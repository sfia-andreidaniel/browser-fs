<?php

    ignore_user_abort(true);
    set_time_limit( 600 );
            
    /* The class OneDB_MetaFile is used in order to obtain
       information from a file from the .docsplit zip file
       created with the OneDB_DocSplit.class library.
     */

    class OneDB_MetaFile {
        
        protected $_article    = NULL;
        protected $_hasMeta    = FALSE;
        protected $_cachedMeta = FALSE;
        protected $_onedb      = NULL;
        protected $_cacheFile  = NULL;
        protected $_unlink     = array();
        protected $_metaInfoFileID = NULL;
        
        protected $_zip = NULL;
        
        public function __construct( &$article ) {
        
            $metaModified = FALSE;
        
            try {
        
                $this->_article = $article;
            
                $this->_metaInfoFileID = $this->_article->_metaInfo;
            
                $this->_hasMeta = ( !empty( $this->_metaInfoFileID ) );
            
                $this->_onedb = $this->_article->_collection->db->onedb;

                $this->_cacheFile = $this->_onedb->temp( "$article->_id" ) . DIRECTORY_SEPARATOR . $article->_id . ".docsplit";
                // $this->debug("$this->_cacheFile");
            
                if (!file_exists( $this->_cacheFile )) {
                
                    if ($this->_article->_metaInfo == -1)
                        throw new Exception("Article is currently processing, please wait!");
            
                    if (!$this->_hasMeta) {
                    
                        $this->_article->_metaInfo = -1;
                        $metaModified = TRUE;
                        
                        $this->_article->save();
                    
                        /* Test file extension. */
                        
                        $extension = strtolower( pathinfo( $this->_article->name, PATHINFO_EXTENSION ) );
                        
                        if (!in_array( $extension, array(
                            'doc',
                            'xls',
                            'pdf',
                            'odt',
                            'ods',
                            'odp',
                            'odg',
                            'odf',
                            'docx',
                            'ppt'
                        ) ) ) throw new Exception("ERR_INVALID_EXTENSION");
                            
                
                        // $this->debug("The document does not have a meta file created!");
                
                        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "OneDB_DocSplit.class.php";
                    
                        /* we try to build the meta package */
                    
                        $tempFile = $this->_bringOriginalFileToDisk();
                    
                        // $this->debug("Creating docsplit...");
                    
                        $docsplit = new OneDB_DocSplit( $tempFile );
                    
                        $docsplitFile = $docsplit->all;
                    
                        // $this->debug("DocSplit file: ", $docsplitFile );
                    
                        if ( empty( $docsplitFile ) )
                            throw new Exception("Could not generate a docsplit file!");
                    
                        $this->_unlink[] = $docsplitFile;
                    
                        if (!filesize( $docsplitFile ))
                            throw new Exception("DocsplitFile is empty!");
                    
                        /* good, we generated a meta info file through DocSplit. We now create a file
                           in the OneDB filesystem */
                    
                        $OneDB_File = $this->_onedb->getElementByPath(
                            '/metafiles/'
                        )->createArticle( 'File' );
                    
                        $OneDB_File->storeFile( $docsplitFile );
                        $OneDB_File->name = $this->_article->_id . "";
                        $OneDB_File->save();
                    
                        /* We created the OneDB File, we update the metaInfo on the $this->_article */
                        $this->_article->_metaInfo = "$OneDB_File->_id";
                        
                        /* Also we update the _unlink property */
                        $_unlink = $this->_article->_unlink;
                        
                        if ( is_array( $_unlink ) ) {
                            $_unlink[] = array(
                                'collection' => 'articles',
                                'id' => "$OneDB_File->_id"
                            );
                        } else {
                            $_unlink = array(
                                array(
                                    'collection' => 'articles',
                                    'id' => "$OneDB_File->_id"
                                )
                            );
                        }
                        
                        $this->_article->_unlink = $_unlink;
                        
                        $this->_article->save();
                    
                        unset( $OneDB_File );
                        unset( $docsplitFile );

                        $this->_hasMeta = TRUE;
                        $this->_metaInfoFileID = $this->_article->_metaInfo;
                    
                    }
                
                    // $this->debug("Bringing the meta file to local cache...");
                
                    /* We bring the meta file to local cache */
                    $theMetaDBFile = $this->_onedb->articles(
                        array(
                            "_id" => MongoIdentifier( $this->_metaInfoFileID )
                        )
                    )->get(0);
                
                    $local = @fopen( $this->_cacheFile, 'w' );
                
                    if (!is_resource( $local ))
                        throw new Exception("Could not create a meta-file on the disk in temporary folder ($this->_cacheFile)!");
                
                    $remote = $theMetaDBFile->getFile()->getResource();
                
                    if (!$remote)
                        throw new Exception("Could not obtain a link to meta stream from OneDB!");
                
                    while (!feof( $remote ) ) {
                        $buffer = fread( $remote, 8192 );
                
                        if ($buffer !== FALSE)
                            @fwrite( $local, $buffer );
                        else
                            break;
                    }
                
                    @fclose( $remote );
                    @fclose( $local );

                    // $this->debug("File has been successfully bringed to the cache!");

                }
            
                $this->_zip = new ZipArchive();
            
                // $this->debug("Opening archive: ", $this->_cacheFile );
            
                if (!$this->_zip->open( $this->_cacheFile )) {
                    throw new Exception("Could not open the zip metaFile!");
                }
                
                // $this->debug("Archive opened. MetaFile is ready!");
                
            } catch (Exception $e) {

                $this->_zip = NULL;
                
                if ($this->_article->_metaInfo == -1 && $metaModified) {
                    $this->_article->deleteProperty("_metaInfo");
                    $this->_article->save();
                }

                if ( !in_array( $msg = $e->getMessage(), array('ERR_INVALID_EXTENSION') ) )
                    throw $e;
                else
                    trigger_error( $msg, E_USER_NOTICE );
            }
        }
        
        private function _bringOriginalFileToDisk() {
            // $this->debug("Bringing original file to disk");
            
            $name = $this->_article->name;
            $info = pathinfo( $name );
            
            $extension = isset( $info['extension'] ) ? strtolower( $info['extension'] ) : '';
            
            $name = $extension == '' ? $name : substr( $name, 0, strlen( $name ) - 1 - strlen( $extension ) );
            
            $tries = '';
            
            $tmpDir = sys_get_temp_dir();
            
            while ( TRUE ) {
                $tempFile = $tmpDir . DIRECTORY_SEPARATOR . $name . ( $tries == '' ? '' : "-$tries" ) . ( $extension == '' ? '' : ".$extension" );

                if ( !file_exists( $tempFile ) ) {
                    
                    if ( @file_put_contents( $tempFile, '' ) === FALSE )
                        throw new Exception("Could not bring file to disk!");
                    
                    break;
                }
                
                $tries = ( $tries == '' ? 1 : $tries + 1 );
                
                if ($tries > 1000)
                    throw new Exception("Could not create temp file on disk while trying to get the meta!");
            }
            
            $this->_unlink[] = $tempFile;
            
            // $this->debug("Bringing to: ", $tempFile );
            
            $handle = $this->_article->getFile()->getResource();
            
            $tmp = @fopen( $tempFile, 'w' );
            
            if (!$tmp) {
                throw new Exception("Could not obtain a resource to the file meta!");
            }
            
            while (!feof( $handle )) {
                $buffer = fread( $handle, 8192 );
                if ($buffer === FALSE)
                    break;
                else
                    @fwrite( $tmp, $buffer );
            }
            
            fclose( $tmp );
            fclose( $handle );
            
            // $this->debug( "File was bringed to disk!" );
            
            return $tempFile;
        }
        
        public function __destruct() {
            // $this->debug("__destruct");
            foreach ($this->_unlink as $file) {
                // $this->debug("unlink: ", $file);
                @unlink( $file );
            }
        }
        
        public function __get( $propertyName ) {
            
            if ( empty( $this->_zip ) )
                return NULL;
            
            $stream = @$this->_zip->getStream( $propertyName );
            
            if (!is_resource( $stream ))
                return array(
                    'mime' => NULL,
                    'data' => NULL
                );
            
            $buffer = stream_get_contents( $stream );
            
            @fclose( $buffer );
            
            switch (TRUE) {
                
                case !strlen( $buffer ):
                    return array(
                        'mime' => NULL,
                        'data' => ''
                    );
                    break;
                
                case preg_match( '/^pages$/', $propertyName ):
                    return array(
                        'mime' => 'text/json',
                        'data' => $buffer
                    );
                    break;
                
                case preg_match( '/^text$/', $propertyName ):
                    return array(
                        'mime' => 'text/plain',
                        'data' => $buffer
                    );
                    break;
                
                case preg_match( '/^[\d]+$/', $propertyName ):
                    return array(
                        'mime' => 'image/png',
                        'data' => $buffer
                    );
                    break;
                
                case preg_match( '/^pdf$/', $propertyName ):
                    return array(
                        'mime' => 'application/pdf',
                        'data' => $buffer
                    );
                    break;
                
                default:
                    return array(
                        'mime' => 'application/octet-stream',
                        'data' => $buffer
                    );
                    break;
            }
        }
        
        private function debug( ) {
            echo "Debug: ";
            foreach ( func_get_args() as $arg )
                echo $arg, " ";
            echo "\n";
        }
    }

?>