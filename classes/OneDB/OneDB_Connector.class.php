<?php

    function guessMimeByExtension( $path, $context = '' ) {
        $info = pathinfo( $path );
        
        $extension = isset( $info['extension'] ) ? strtolower( $info['extension'] ) :  '';
        
        switch ($extension) {
            case 'php':
            case 'html':
            case 'css':
                return 'text/' . $extension;
                break;
            case 'js':
                return 'text/javascript';
                break;
            case 'htm':
                return 'text/html';
                break;
            case 'gif':
            case 'png':
            case 'jpeg':
            case 'jpg':
            case 'bmp':
                return 'image/' . $extension;
                break;
            case 'text':
            case 'txt':
            case 'conf':
            case 'log':
            case 'ini':
                return 'text/plain';
                break;
            
            case 'zip':
                return 'application/' . $extension;
                break;
            
            case 'gz':
                return 'application/x-gzip';
                break;
            
            case 'mp3':
            case 'wav':
                return 'audio/' . $extension;
                break;
            
            case '':
            case 'sql':
            case 'dump':
                if ($context == 'sql') {
                    return 'database-dump';
                    break;
                } else {
                    return 'text/plain';
                }
            
            case 'table':
                if ($context == 'sql') {
                    return 'database-table';
                    break;
                }
            
            default:
                return 'application/octet-stream';
        }
    }

    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "OneDB_Protocol.class.php";

    class OneDB_Connector {
        
        protected $_uri      = NULL;
        protected $_protocol = NULL;
        
        function __construct( $uri ) {

            $uri = preg_replace('/^(onedb|file)\:[\/]/', '$1://localhost/', $uri);
            $info = parse_url( $uri );
            
            if (!isset( $info['scheme'] ) || !isset( $info['host'] ))
                throw new Exception("Invalid connection URL.");
            
            if ( $info['scheme'] == 'onedb' )
                unset( $info['host'] );
            
            $info['scheme'] = strtolower( $info['scheme'] );
            
            if (!empty( $info['user'] ) )
                $info['user'] = urldecode( $info['user'] );
            
            if (!empty( $info['pass'] ) )
                $info['pass'] = urldecode( $info['pass']);
            
            $info['path'] = isset( $info['path'] ) ? $info['path'] : '';
            $info['path'] = str_replace('//', '/', $info['path']);
            
            switch ($info['scheme']) {
                case 'sftp':
                case 'fish':

                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Protocol.class/OneDB_Protocol_SFTP.class.php";

                    $port = isset( $info['port'] ) ? $info['port'] : 22;

                    $this->_protocol = new OneDB_Protocol_SFTP( 
                        $info['host'] . ':' . $port, 
                        $info['user'], 
                        $info['pass'], 
                        $info['path'] 
                    );

                    break;

                case 'mysql':

                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Protocol.class/OneDB_Protocol_MySQL.class.php";

                    $port = isset( $info['port'] ) ? $info['port'] : 3306;

                    $this->_protocol = new OneDB_Protocol_MySQL(
                        $info['host'] . ':' . $port,
                        $info['user'],
                        $info['pass'],
                        $info['path']
                    );

                    break;
                
                case 'ftp':
                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Protocol.class/OneDB_Protocol_FTP.class.php";

                    $port = isset( $info['port'] ) ? $info['port'] : 21;

                    $this->_protocol = new OneDB_Protocol_FTP( 
                        $info['host'] . ':' . $port, 
                        $info['user'], 
                        isset( $info['pass'] ) ? $info['pass'] : '', 
                        $info['path'] 
                    );

                    break;
                
                case 'smb':
                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Wrappers/OneDB_SMB_Wrapper.class.php";
                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Protocol.class/OneDB_Protocol_SMB.class.php";
                    
                    $this->_protocol = new OneDB_Protocol_SMB(
                        $info['host'],
                        $info['user'],
                        isset( $info['pass'] ) ? $info['pass'] : '',
                        $info['path']
                    );
                    
                    break;
                    
                
                case 'onedb':
                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Wrappers/OneDB_FileSystem_Wrapper.class.php";
                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Protocol.class/OneDB_Protocol_OneDB.class.php";
                    
                    $this->_protocol = new OneDB_Protocol_OneDB(
                        $info['path']
                    );
                    
                    break;
                
                case 'file':
                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Wrappers/OneDB_FileSystem_Wrapper.class.php";
                    require_once dirname(__FILE__) . "/plugins/core.OneDB_Protocol.class/OneDB_Protocol_File.class.php";
                    
                    $this->_protocol = new OneDB_Protocol_File(
                        $info['path']
                    );
                    
                    break;
                
                default:
                    throw new Exception("Protocol not supported: " . $info['scheme'] );
            }

        }

        public function __call( $method, $args ) {
            return call_user_func_array( array( $this->_protocol, $method ), $args );
        }

        function __get( $propertyName ) {
            switch ($propertyName) {
            
                case 'connection':
                    return $this->_protocol->connection();
                    break;
            
                default:
                    trigger_error( "OneDB_Connector: Invalid property name: $propertyName", E_WARNING );
                    return NULL;
                    break;
            }
        }

        function __toString() {
            return $this->_protocol->__toString();
        }
    }

?>