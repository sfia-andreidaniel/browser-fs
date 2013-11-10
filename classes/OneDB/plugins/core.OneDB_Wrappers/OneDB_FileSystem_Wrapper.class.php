<?php

    /** This class implements a filesystem wrapper in format
        onedb://path/to/something for OneDB files and categories
     **/
    
    require_once dirname(__FILE__) . "/../../OneDB.class.php";
    require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_FileStream.class.php";
    require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_JSON.class.php";
    require_once dirname(__FILE__) . "/../core.OneDB_FileSystem_Wrapper.class/OneDB_FileSystem_Wrapper_OneDBWriteableFile.class.php";
    
    if (!isset( $_SESSION ) )
        session_start();
    
    try {
        OneDB::get();
    } catch (Exception $e) {
        $my = new OneDB(); unset( $my );
    }
    

    class OneDB_FileSystem_Wrapper {
        
        static $_db = NULL;
        static $_dirs = array();
        static $_path = "";

        protected $_stream  = NULL;
        protected $_item = NULL;
        
        public function __construct( ) {
        }
        
        protected function onedb() {
            
            if ( self::$_db === NULL ) {
                self::$_db = OneDB::get();
            }
            
            if ( self::$_db === NULL)
                throw new ExceptioN("Failed to obtain OneDB instance!");
            
            return self::$_db;
        }
        
        private function _path( $path, $dir = TRUE ) {
            $path = preg_replace('/^onedb\:\/\//i', '', $path);
            
            $path = trim( str_replace('//', '/', $path ), '/' );
            $path = empty( $path ) ? '/' : '/' . $path . ($dir ? '/' : '');
            return $path;
        }
        
        // directories
        public function dir_opendir( $url, $options ) {
        
            $my = self::onedb();
            
            $path = self::_path( $url );
            
            try {

                $category = $my->getElementByPath( $path );
            
                if ( !in_array( $category->_type, array( 'Category', '' ) ) )
                    throw new Exception("$path is not a `category`");
            
                $out = array();
                
                $my->categories(array(
                    "selector" => "$path > *"
                ))->each( function( $cat ) use (&$out) {
                    if (!$cat->hidden)
                    $out[] = $cat->name;
                });
                
                $my->categories( array(
                    "selector" => $path
                ) )->articles( NULL )->each(function( $article ) use (&$out) {
                    $out[] = $article->name;
                });
            
                self::$_dirs["$path"] = array(
                    "pointer" => 0,
                    "length"  => count($out),
                    "items"   => $out
                );
            
                self::$_path = $path;
            
                return TRUE;
            
            } catch (Exception $e) {
                self::$_path = "";
                return FALSE;
            }
        }
        
        function dir_readdir( ) {
            if (!isset( self::$_dirs[self::$_path]) )
                return FALSE;
            
            return self::$_dirs[self::$_path]['pointer'] >= self::$_dirs[self::$_path]['length'] 
                ? FALSE 
                : self::$_dirs[self::$_path]['items'][self::$_dirs[self::$_path]['pointer']++];
        }
        
        function dir_rewinddir( ) {
            self::$_dirs[self::$_path]['pointer'] = 0;
        }
        
        function dir_closedir( ) {
            self::$_path = "";
        }
        
        function mkdir( $path, $mode, $options ) {

            $path = str_replace('//', '/', $path );
            $path = str_replace('/^[a-z]+\:[\/]+/i', '$1://', $path);
            $path = preg_match('/\/$/', $path) ? $path : $path . '/';
            
            $path = self::_path( $path );

            $path = preg_replace('/^\/onedb\:[\/]+/', '/', $path );
            
            if ($path == '/')
                return FALSE;
            
            $dirname = end( $parts = explode('/', trim( $path, '/' ) ) );
            
            $parent = '/' . implode('/', array_slice( $parts, 0, count( $parts ) - 1 ) );
            $parent = $parent == '/' ? '/' : $parent . '/';
            
            try {
                $my = self::onedb();
                
                try {
                    if ($my->getElementByPath( $path )) {
                        trigger_error("A directory with that name allready exists!");
                        return FALSE;
                    }
                } catch (Exception $f) {
                }
                
                try {
                    if ($my->getElementByPath( "$parent$dirname" )) {
                        trigger_error("An article with that name allready exists!");
                        return FALSE;
                    }
                } catch (Exception $f) {
                }
                
                $parent = $my->categories( array(
                    "selector" => $parent
                ) )->get(0);
                
                $newCategory = $parent->createCategory();
                
                $newCategory->name = $dirname;
                
                $newCategory->save();
                
                return TRUE;
                
            } catch (Exception $e) {
                trigger_error( $e->getMessage(), E_WARNING );
                return FALSE;
            }
        }
        
        function rename( $oldName, $newName ) {
            throw new Exception("rename function not implemented on this wrapper!");
        }
        
        function rmdir( $dirName ) {
            $path = self::_path( $dirName );
            $my   = self::onedb();
            
            try {
                
                if ($path == '/')
                    return FALSE;
                
                $category = $my->getElementByPath( $path );
                
                $category->delete();
                
                return TRUE;
                
            } catch (Exception $e) {
                trigger_error( "Cannot remove folder $dirName: " . $e->getMessage(), E_WARNING );
                return FALSE;
            }
        }
        
        // streams
        function stream_open( $url, $mode, $options, $opened_path ) {

            $url = preg_replace('/[\/]+/', '/', $url);
            $url = preg_replace('/^onedb\:[\/]+/', 'onedb://', $url);

            if ( !in_array( $mode, array('r', 'r+', 'w') ) ) {
                trigger_error( "This wrapper supports only the r, r+, and w filemodes!" );
                return FALSE;
            }

            if (in_array( $mode, array( 'r', 'r+' ) ) ) {

                $path = self::_path( $url, FALSE );

                try {

                    $my = self::onedb();

                    $item = $my->getElementByPath( $path );

                    $this->_item   = $item;

                    switch (TRUE) {
                        case preg_match('/^File([\s]+|$)/', $item->type):
                            $resource = $item->getFile()->getResource();
                            $this->_stream = new OneDB_FileSystem_Wrapper_FileStream( $resource, $item );
                            break;

                        default:
                            $resource = $this->_item->toArray();
                            $this->_stream = new OneDB_FileSystem_Wrapper_JSON( $resource, $item );
                            break;
                    }

                    return TRUE;

                } catch (Exception $e) {
                    return FALSE;
                }
            } else {
                try {
                    $my = self::onedb();

                    $this->_stream = new OneDB_FileSystem_Wrapper_OneDBWriteableFile( $url, 'w', $my );
                    
                    return TRUE;
                    
                } catch (Exception $e) {
                    trigger_error( $e->getMessage(), E_WARNING );
                    return FALSE;
                }
            }
        }

        function stream_close( ) {
            return $this->_stream->close();
        }

        function stream_read( $count ) {
            return $this->_stream->read( $count );
        }

        function stream_write( $data ) {
            return $this->_stream->write( $data );
        }

        function stream_eof( ) {
            return $this->_stream->eof( );
        }

        function stream_tell( ) {
            return $this->_stream->tell( );
        }

        function stream_seek( $offset, $whence = SEEK_SET ) {
            return $this->_stream->seek( $offset, $whence );
        }
        
        function unlink( $path ) {
            try {
                
                $path = self::_path( $path, FALSE );
                
                $my = self::onedb();
                
                $my->getElementByPath( $path )->delete();
                
                return TRUE;
                
            } catch (Exception $e) {
                return FALSE;
            }
        }

        function url_stat( $url, $flags = STREAM_URL_STAT_LINK ) {

            $url = preg_replace('/[\/]+/', '/', $url);
            $url = preg_replace('/^onedb\:[\/]+/', 'onedb://', $url);

            try {

                $my = self::onedb();

                try {
                    $path = self::_path( $url, FALSE );
                    
                    $item = $my->getElementByPath( $path );

                    $size = $item->size;

                    $stat = stat( __FILE__ );
                    
                    $stat['size'] = $stat[7] = ( $size === NULL ? strlen( json_encode( $item->toArray() ) ) : $item->size );
                    
                    return $stat;
                    
                } catch (Exception $e) {
                    try {
                    
                        $path = self::_path( $url, TRUE );
                    
                        $item = $my->getElementByPath( $path );
                    
                        return stat( sys_get_temp_dir() );
                    } catch (Exception $e) {
                        throw $e;
                    }
                }

            } catch (Exception $e) {
                return FALSE;
            }
        }

        function __destruct() {
        }
    }

    if (!stream_wrapper_register('onedb', 'OneDB_FileSystem_Wrapper', STREAM_IS_URL))
        throw new Exception("Could not register protocol onedb!");
        
    // echo is_file( $argv[1] ) ? "yes" : "no";

?>