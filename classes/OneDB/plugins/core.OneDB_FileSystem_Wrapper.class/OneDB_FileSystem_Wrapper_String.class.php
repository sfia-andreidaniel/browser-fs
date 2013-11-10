<?php

    require_once dirname(__FILE__) . "/OneDB_FileSystem_Wrapper_Abstract.class.php";

    class OneDB_FileSystem_Wrapper_String extends OneDB_FileSystem_Wrapper_Abstract {
        
        protected $_data = '';
        protected $_offset = 0;
        
        function __construct( &$handle, &$item ) {
            parent::__construct( $handle, $item );
            $this->_data = $handle;
        }
        
        function close( ) {
            return TRUE;
        }
        
        function eof( ) {
            return $this->_offset >= strlen( $this->_data );
        }
        
        public function stat() {
            $tmp = tempnam( sys_get_temp_dir(), 'string-stat-');
            file_put_contents( $tmp, $this->_data );
            $stat = stat( $tmp );
            unlink( $tmp );
            return $stat;
        }
        
        function read( $count ) {
            $len = strlen( $this->_data );
            
            $out = substr( $this->_data, $this->_offset, $count );
            
            if ($out == '') {
                $this->_offset = $len;
                return FALSE;
            } else {
                $this->_offset += strlen( $out );
                return $out;
            }
        }
        
        function seek( $offset, $whence = SEEK_SET ) {
            $current = $this->_offset;
            $len = strlen( $this->_data );
            
            switch ( $whence ) {
                case SEEK_SET:
                    break;
                case SEEK_CUR:
                    $current += $offset;
                    break;
                case SEEK_END:
                    $current = $len + $offset - 1;
                    break;
            }
            
            if ($current >= $len)
                return -1;
            else {
                $this->_offset = $current;
                return 0;
            }
        }
        
        function tell( ) {
            return $this->_offset;
        }
        
        function truncate( $new_size ) {
            $this->_data = substr( $this->_data, 0, $new_size );
            $len = strlen( $this->_data );
            while ( $len <= $new_size )
                $this->_data .= ' ';
        }
        
        function write( $data ) {
            return FALSE;
        }
        
    }

?>