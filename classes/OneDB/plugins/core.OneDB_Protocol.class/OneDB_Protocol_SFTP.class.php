<?php

    class OneDB_Protocol_SFTP extends OneDB_Protocol {

        protected $_ssh2 = NULL;
        protected $_conn = NULL;
        
        public function __construct( $host, $user, $pass, $path ) {
        
            if (!extension_loaded('ssh2'))
                throw new Exception("In order to be able to use the SSH connector, you should have loaded the php-ssh2 extension!");
        
            $hostPort = explode(':', $host );
            $port = count( $hostPort ) > 1 ? $hostPort[1] : '22';
            $host = $hostPort[0];
            
            $path = strlen( $path ) ? ( '/' . trim( $path, '/' ) ) : '';
            
            parent::__construct( 'sftp', $host, $port, $user, $pass, $path );
            
            $this->_ssh2 = ssh2_connect( $host, $port );
            
            if (!is_resource( $this->_ssh2 ))
                throw new Exception("Cannot connect to sftp://$host:$port!");
            
            if (!empty( $pass ) && !empty( $user )) {
                if (!ssh2_auth_password($this->_ssh2, $user, $pass))
                    throw new Exception("Invalid username or password on sftp://$host:$port");
            }
            
            $this->_conn = ssh2_sftp( $this->_ssh2 );
            
            if (!$this->_conn)
                throw new Exception("Could not initialize the SFTP subsystem on sftp://$host:$port");
        }
        
        public function getItems( $path = '/' ) {
        
            $items = @scandir( $p = $this->_path( "ssh2.sftp://$this->_conn/$this->_path/$path") );
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
                        'online'  => TRUE
                    );
                    
                    if ($addItem['type'] == 'item') {
                        $addItem['type'] .= '/File ' . guessMimeByExtension( $pth );
                    }

                    $out[] = $addItem;
                }
            } else throw new Exception("Invalid sftp path: sftp://$this->_host:$this->_port$this->_path$path");
            
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
        
            $size = @filesize( $this->_path( "ssh2.sftp://$this->_conn/$this->_path/$path") );
            
            $die = $dumpDirectly;

            if ($die) {
                require_once "MIME/Type.php";
                header("Content-Type: " . @MIME_Type::autoDetect( "ssh2.sftp://$this->_conn$this->_path$path" ));
                header("Content-Disposition: attachment; name=" . end( explode('/', $path ) ) );
                header("Content-Length: $size");
            }
            
            $fh = @fopen( "ssh2.sftp://$this->_conn$this->_path$path", 'r' );

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
                throw new Exception("Cannot open file $this->_path$path via sftp protocol");
            
            if ($die) {
                die('');
            } else 
                return $out;
        }
        
        public function setBytes( $path, $bytes ) {
            $fhandle = @fopen( $this->_path("ssh2.sftp://$this->_conn/$this->_path/$path"), 'w' );
            
            if (!is_resource( $fhandle ))
                return FALSE;
            
            $wrote = @fwrite( $fhandle, $bytes, strlen( $bytes ) );

            @fclose( $fhandle );
            
            return $wrote == strlen( $bytes );
        }
        
        public function deleteItemByPath( $path ) {
            
            $pth = $this->_path("ssh2.sftp://$this->_conn/$this->_path/$path");
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
        
            $pthSource = $this->_path("ssh2.sftp://$this->_conn/$this->_path/$source");
            $pthDest   = $this->_path("ssh2.sftp://$this->_conn/$this->_path/$destination");
            
            if ( file_exists( $pthDest ) )
                throw new Exception("Cannot rename: Another item allready exists with that name!");
            
            if (!@rename( $pthSource, $pthDest ))
                throw new Exception("Failed to rename $source to $destination");
            
            return TRUE;
        }
        
        public function createFolder( $path ) {
            $dest = $this->_path("ssh2.sftp://$this->_conn/$this->_path/$path");

            if (is_dir( $dest ))
                return TRUE;

            if ( @mkdir( $dest, 0777, TRUE ) ) {
                return TRUE;
            } else return FALSE;
        }
        
        public function openFile( $path, $mode = 'r' ) {
            return fopen( $this->_path("ssh2.sftp://$this->_conn/$this->_path/$path"), $mode );
        }
        
        public function fileSize( $path ) {
            return filesize( $this->_path("ssh2.sftp://$this->_conn/$this->_path/$path") );
        }
        
        public function connection() {
            return $this->_conn;
        }
    }

?>