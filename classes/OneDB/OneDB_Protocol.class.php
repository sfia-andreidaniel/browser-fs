<?php

    class OneDB_Protocol {
        
        protected $_protocol = NULL;
        protected $_host = NULL;
        protected $_user = NULL;
        protected $_pass = NULL;
        protected $_port = NULL;
        protected $_path = NULL;
        
        private $_toString = NULL;
        
        public function __construct( $protocol, $host, $port, $user, $pass, $path ) {
            $this->_protocol = $protocol;
            $this->_host     = $host;
            $this->_port     = $port;
            $this->_user     = $user;
            $this->_pass     = $pass;
            $this->_path     = $path;
        }
        
        public function getURI( $secure = TRUE ){
            $out = $this->_protocol . '://';
        
            if ($this->_user !== NULL) {
                if ( $this->_pass !== NULL && !$secure )
                    $out .= urlencode( $this->_user ) . ( $this->_pass == '' ? '' : ( ':' . urlencode( $this->_pass ) ) ) . '@';
                else
                    $out .= urlencode( $this->_user ) . '@';
            }
            
            if ( !in_array( $this->_protocol, array('file', 'onedb') ) ) {
                $out .= $this->_host == NULL ? 'localhost' : $this->_host;
    
                if ($this->_protocol != 'smb')
                    $out .= $this->_port == NULL ? '' : ( ':' . $this->_port );
            }
            
            $out .= $this->_path == NULL ? '' : ( '/' . ( strlen( $this->_path ) ? trim( $this->_path, '/' ) . '/' : '' ) );
            
            $out = preg_replace('/[\/]+$/', '/', $out );
            $out = preg_replace('/([a-z]+)\:[\/]+/i', '$1://', $out);
            
            return $out;
        }
        
        public function _path( $path ) {
            $path = preg_replace('/[\/]+/', '/', $path );
            $path = preg_replace('/^([a-z\.\d]+)\:[\/]+/i', '$1://' . ( $yes = preg_match('/^file$/i', $this->_protocol ) ? '/' : '' ), $path);
            if ( $yes ) {
                $path = substr( $path, 7);
            }
            return $path;
        }
        
        public function __toString( ) {

            if ($this->_toString == NULL)
                $this->_toString = $this->getURI( TRUE );

            return $this->_toString;
        }
        
        public function getItems( $path = '' ) {
            return array();
        }
        
        public function getItemByPath( $path = '', $dumpDirectly = FALSE ) {
            return "This procol does not support fetching items by their absolute path!";
        }
        
        public function setBytes( $path, $bytes ) {
            throw new Exception("Method not supported on this protocol");
        }
        
        public function deleteItemByPath( $path = '' ) {
            throw new Exception("Method not supported on this protocol");
        }
        
        public function renameItem( $source, $destination ) {
            throw new Exception("Method not supported by this protocol");
        }
        
        public function createFolder( $path ) {
            throw new Exception("Method not supported by this protocol");
        }
        
        public function openFile( $pth, $mode = 'r' ) {
            throw new Exception("Direct file access is not provided by this protocol");
        }
        
        public function fileSize( $pth ) {
            throw new Exception("File size is not provided by this protocol!");
        }
        
        public function connection() {
            return NULL;
        }
    }

?>