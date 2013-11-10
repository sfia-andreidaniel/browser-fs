<?php

    class OneDB_Protocol_File extends OneDB_Protocol {

        protected $_conn = NULL;
        
        public function __construct( $path ) {
        
            $path = strlen( $path ) ? ( '/' . trim( $path, '/' ) ) : '';
            
            parent::__construct( 'file', NULL, NULL, NULL, NULL, $path );
            
            $this->_conn = $this->getURI( FALSE );
        }
        
        public function getItems( $path = '/' ) {
        
            require_once "MIME/Type.php";
        
            $items = @scandir( $p = $this->_path( "$this->_conn/$path") );
            
            $out = array();
            
            if ( is_array( $items ) ) {
                $len = count( $items );
                for ($i=0; $i<$len; $i++) {
                    if ($items[$i] == '.' || $items[$i] == '..') continue;
                    $addItem = array(
                        'name'    => $items[$i],
                        'type'    => @is_dir( $pth = $p . DIRECTORY_SEPARATOR . $items[$i] ) ? 'category' : 'item',
                        'path'    => str_replace( '//', '/', $path . '/' . $items[$i] ),
                        'size'    => @filesize( $pth ),
                        'online'  => TRUE,
                        'date'    => @filectime( $pth ),
                        'modified'=> @filemtime( $pth )
                    );
                    
                    $addItem['owner'] = @fileowner( $pth );
                    
                    if ($addItem['owner'] !== FALSE) {
                        $addItem['owner'] = @posix_getpwuid( $addItem['owner'] );
                        $addItem['owner'] = $addItem['owner']['name'];
                        
                        $gr = @filegroup( $pth );
                        if ($gr !== FALSE) {
                            $gr = posix_getgrgid( $gr );
                            $addItem['owner'] .= ':' . $gr['name'];
                        }
                    }
                    
                    if ($addItem['type'] == 'item') {
                        $addItem['type'] .= '/File ' . @MIME_Type::autoDetect( $pth );
                    }

                    $out[] = $addItem;
                }
            } else throw new Exception("Invalid path: " . $this->_path("$this->_conn/$path") );
            
            usort( $out, function( $a, $b ) {
                
                $ta = reset( explode('/', $a['type'] ) );
                $tb = reset( explode('/', $b['type'] ) );
                
                switch (TRUE) {
                    case $ta == $tb:
                        return strcmp( strtolower( $a['name'] ), strtolower( $b['name'] ) );
                        break;
                    case $ta == 'category':
                        return -1;
                        break;
                    default:
                        return 1;
                }
                
            } );
            
            return $out;
        }
        
        public function getItemByPath( $path, $dumpDirectly = FALSE ) {
        
            $size = @filesize( $this->_path( "$this->_conn/$path") );
            
            $die = $dumpDirectly;

            if ($die) {
                require_once "MIME/Type.php";
                header("Content-Type: " . @guessMimeByExtension( $this->_path( "$this->_conn/$path") ));
                header("Content-Disposition: attachment; name=" . end( explode('/', $path ) ) );
                header("Content-Length: $size");
            }
            
            $fh = @fopen( $this->_path("$this->_conn/$path"), 'r' );

            $numRead = 0;
            $out     = '';

            if (is_resource( $fh )) {
            
                if ($size > 0)
                while (!feof( $fh )) {
                    $buffer = fread( $fh, 8192 );

                    $numRead += $buffer === FALSE ? 0 : strlen( $buffer );

                    if ($die)
                        echo $buffer;
                    else
                        $out .= $buffer === FALSE ? '' : $buffer;
                    
                    if ($numRead == $size)
                        break;
                }
                
                fclose( $fh );
                
            } else
                throw new Exception("Cannot open file $this->_path$path");
            
            if ($die) {
                die('');
            } else 
                return $out;
        }
        
        public function setBytes( $path, $bytes ) {
            $fhandle = @fopen( $p = $this->_path( "$this->_conn/$path" ), 'w' );
            
            if (!is_resource( $fhandle )) {
                return FALSE;
            }

            $wrote = @fwrite( $fhandle, $bytes, strlen( $bytes ) );

            @fclose( $fhandle );
            
            return $wrote == strlen( $bytes );
        }
        
        public function deleteItemByPath( $path ) {
            
            $pth = $this->_path("$this->_conn/$path" );
            $rmdir = FALSE;
            
            switch (TRUE) {
                case is_file( $pth ):
                    if (unlink( $pth ))
                        return TRUE;
                    else
                        throw new Exception("Could not delete file: $path!");
                    break;
                
                case is_dir( $pth ):
                    
                    $rmdir = TRUE;
                    
                    $files = scandir( $pth );
                    
                    if ($files === FALSE)
                        throw new Exception("Could not fetch files from: $path");
                    
                    foreach ($files as $file) {
                        if (!in_array( $file, array( '.', '..' ) ) ) 
                            $this->deleteItemByPath( $path . DIRECTORY_SEPARATOR . $file );
                    }
                    
                    break;
                    
                default:
                    throw new Exception("Unknown item type: $path");
                    break;
            }
            
            if ($rmdir) {
                if (!@rmdir( $pth ))
                    throw new Exception("Could not remove directory: $pth");
                else
                    return TRUE;
            } else return FALSE;
        }
        
        public function renameItem( $source, $destination ) {
        
            $pthSource = $this->_path( "$this->_conn/$source" );
            $pthDest   = $this->_path( "$this->_conn/$destination" );
            
            if ( file_exists( $pthDest ) )
                throw new Exception("Cannot rename: Another item allready exists with that name!");
            
            if (!@rename( $pthSource, $pthDest ))
                throw new Exception("Failed to rename $source to $destination");
            
            return TRUE;
        }
        
        public function createFolder( $path ) {
            $dest = $this->_path( "$this->_conn/$path" );

            if (is_dir( $dest ))
                return TRUE;

            if ( @mkdir( $dest, 0777, TRUE ) ) {
                return TRUE;
            } else return FALSE;
        }
        
        public function openFile( $path, $mode = 'r' ) {
            return @fopen( $this->_path("$this->_conn/$path"), $mode);
        }
        
        public function fileSize( $path ) {
            return @filesize( $this->_path("$this->_conn/$path") );
        }
        
        public function connection() {
            return $this->_conn;
        }
    }

?>