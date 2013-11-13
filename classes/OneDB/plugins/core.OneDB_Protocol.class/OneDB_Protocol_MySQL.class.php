<?php

    require_once dirname(__FILE__) . "/../core.OneDB_Wrappers/OneDB_MySQL_Wrapper.class.php";

    class OneDB_Protocol_MySQL extends OneDB_Protocol {

        protected $_conn = NULL;
        
        public function __construct( $host, $user, $pass, $path ) {
        
            if (!extension_loaded('mysql'))
                throw new Exception("In order to be able to use the MySQL connector, you should have loaded the php-ftp extension!");
        
            $hostPort = explode(':', $host );
            $port = count( $hostPort ) > 1 ? $hostPort[1] : '3306';
            $host = $hostPort[0];
            
            $path = strlen( $path ) ? ( '/' . trim( $path, '/' ) ) : '';
            
            parent::__construct( 'mysql', $host, $port, strlen( $user ) ? $user : 'anonymous', $pass, $path );
            
            // $this->_conn = @ftp_connect( $host, $port, 10 );
            
            // if (!$this->_conn)
            //    throw new Exception("Cannot connect to ftp://$host:$port!");
            
            // if (!empty( $user )) {
            //     if (!ftp_login( $this->_conn, $user, strlen( $pass ) ? $pass : '' ))
            //         throw new Exception("Invalid username or password on ftp://$host:$port");
            // }
            
            $this->_conn = $this->getURI( FALSE );
        }
        
        public function getItems( $path = '/' ) {
        
            $items = @scandir( $p = $this->_path("$this->_conn/$path") );
            
            $out = array();
            
            if ( is_array( $items ) ) {
                $len = count( $items );
                for ($i=0; $i<$len; $i++) {
                    if ($items[$i] == '.' || $items[$i] == '..') continue;
                    $addItem = array(
                        'name'    => $items[$i],
                        'type'    => is_dir( $pth = $p . DIRECTORY_SEPARATOR . $items[$i] ) ? 'category' : 'item',
                        'path'    => str_replace( '//', '/', $path . '/' . $items[$i] ),
                        'size'    => 0,
                        'online'  => TRUE
                    );
                    
                    if ( $addItem['type'] == 'item')
                        $addItem['size'] = @filesize( $pth );
                    
                    if ($addItem['type'] == 'item') {
                        $addItem['type'] .= '/File ' . @guessMimeByExtension( $pth, 'sql' );
                    }

                    $out[] = $addItem;
                }
            } else throw new Exception("Invalid mysql path: mysql://$this->_host:$this->_port$this->_path$path");
            
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
        
            $size = @filesize( $this->_path( "$this->_conn/$path" ) );
            
            $die = $dumpDirectly;

            if ($die) {
                header("Content-Type: " . @guessMimeByExtension( $this->_path( "ftp://$this->_conn/$path", 'sql' )));
                header("Content-Disposition: attachment; name=" . end( explode('/', $path ) ) );
                header("Content-Length: $size");
            }
            
            $fh = @fopen( $this->_path( "$this->_conn/$path" ), 'r' );

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
                throw new Exception("Cannot open file $this->_path$path via ftp protocol");
            
            if ($die) {
                die('');
            } else 
                return $out;
        }
        
        public function setBytes( $path, $bytes ) {
            $fhandle = @fopen( $this->_path( "$this->_conn/$path" ), 'w' );
            
            if (!is_resource( $fhandle ))
                return FALSE;
            
            $wrote = @fwrite( $fhandle, $bytes, strlen( $bytes ) );

            @fclose( $fhandle );
            
            return $wrote == strlen( $bytes );
        }
        
        public function deleteItemByPath( $path ) {
            
            $pth = $this->_path( "$this->_conn/$path" );
            $rmdir = FALSE;
            
            switch (TRUE) {
                case is_file( $pth ):
                    if (@unlink( $pth ))
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
            return @fopen( $this->_path( "$this->_conn/$path" ), $mode);
        }
        
        public function fileSize( $path ) {
            return @filesize( $this->_path( "$this->_conn/$path" ) );
        }
        
        public function connection() {
            return $this->_conn;
        }
    }

?>