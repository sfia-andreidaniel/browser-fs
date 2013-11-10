<?php

    class OneDB_URLFile {
        
        protected $_url = NULL;
        protected $_params = NULL;
        
        public function __construct( $url ) {
            $this->_url = $url;
        }
        
        private function getParams() {
        
            if ($this->_params !== NULL)
                return $this->_params;
        
            $ch = curl_init();
            
            curl_setopt( $ch, CURLOPT_URL, $this->_url );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
            curl_setopt( $ch, CURLOPT_REFERER, 'OneDB' );
            curl_setopt( $ch, CURLOPT_HEADER, 1 );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_NOBODY, 1 );
            
            $buffer = curl_exec( $ch );
            
            if ($buffer === NULL || $buffer === FALSE)
                return FALSE;
            
            $buffer = curl_getinfo( $ch );
            
            return $this->_params = array(
                'size' => isset( $buffer['download_content_length'] ) ? $buffer['download_content_length'] : NULL,
                'mime' => isset( $buffer['content_type'] ) ? $buffer['content_type'] : NULL
            );
        }
        
        public function getURL() {
            return $this->_url;
        }
        
        public function getSize() {
            $params = $this->getParams();
            return $params['size'];
        }
        
        public function getBytes() {
            return @file_get_contents( $this->_url );
        }
        
        private function error( $msg ) {
            throw new Exception( $msg );
        }
        
        public function getResource() {
            $fhandle = @fopen( $this->_url, 'r' );
            return is_resource( $fhandle ) ? $fhandle : $this->exception("Cannot obtain URL resource!");
        }
        
        public function getFilename() {
            $path = parse_url( $this->_url, PHP_URL_PATH );
            $path = end( explode( '/', trim("$path", "/") ) );
            
            return empty( $path ) ? "index" : $path;
        }
        
        public function write( $fileName = NULL ) {
            $fileName = !empty( $fileName ) ? $fileName : $this->getFileName();
            $src = $this->getResource();
            $dest = @fopen( $fileName, 'w' );
            if (!is_resource( $dest )) {
                @fclose( $src );
                @fclose( $dest );
                return FALSE;
            }
            
            $wrote = 0;
            
            while (!feof( $src )) {
                $buffer = @fread( $src, 8192 );
                
                if ($buffer !== FALSE) {
                    @fwrite( $dest, $buffer );
                    $wrote += strlen( $buffer );
                }
            }
            
            fclose( $dest );
            fclose( $src );
            
            return $wrote;
        }
    }

    /*
    
    $file = new remote_File( 'http://s2.digisport.ro/onedb/transcode:50870da995f9cfb52e000454.mp4' );
    echo $file->getFilename(), '=>', $file->getSize(), "\n";
    
    */

?>