<?php

    /* The OneDB_SiteCache interacts with remote sites
       in order to perform operations on their
       local cache
     */
     
     class OneDB_SiteCache {
        
        protected $_onedb = NULL;
        
        protected $_cacheHandler = NULL;
        
        protected $_ch    = NULL;
        
        public function __construct( &$onedb ) {
            $this->_onedb = $onedb;
            
            $this->_cacheHandler = $this->_onedb->registry()->siteURL;

            if ($this->_cacheHandler !== NULL) {
                $this->_cacheHandler .= "/onedb:cache";
            }
            
        }
        
        protected function curl_init() {
            if ($this->_ch === NULL) {
                $this->_ch = curl_init();
                curl_setopt( $this->_ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt( $this->_ch, CURLOPT_FOLLOWLOCATION, 1 );
                curl_setopt( $this->_ch, CURLOPT_USERAGENT, 'OneDB');
                curl_setopt( $this->_ch, CURLOPT_TIMEOUT, 10 );
            }
            
            return $this->_ch;
        }
        
        public function delete( $cacheKey ) {
            if ($this->_cacheHandler) {
                $ch = $this->curl_init();
                curl_setopt( $ch, CURLOPT_URL, $this->_cacheHandler . '/delete?key=' . urlencode( $cacheKey ) );
                $buffer = @curl_exec( $ch );
                
                //server did not responded at all
                if (empty( $buffer ))
                    return FALSE;
                
                $data = @json_decode( $buffer, TRUE );
                
                //server responded, but did not returned a valid JSON response
                if (!is_array( $data ))
                    return FALSE;
                
                return isset( $data['ok'] ) ? $data['ok'] : FALSE;
                
            } else return FALSE;
        }
        
        public function get( $cacheKey ) {
            if ($this->_cacheHandler) {
            
                $ch = $this->curl_init();
                curl_setopt( $ch, CURLOPT_URL, $this->_cacheHandler . '/get?key=' . urlencode( $cacheKey ) );
                $buffer = @curl_exec( $ch );
                
                //server did not responded at all
                if (empty( $buffer ))
                    return NULL;
                
                $data = @json_decode( $buffer, TRUE );
                
                //server responded, but did not returned a valid JSON response
                if (!is_array( $data ))
                    return NULL;
                
                return isset( $data['value'] ) ? $data['value'] : NULL;
                
            } else return NULL;
        }

        public function set( $cacheKey, $value, $expires = 600 ) {
            if ($this->_cacheHandler) {
            
                $ch = $this->curl_init();
                curl_setopt( $ch, CURLOPT_URL, $this->_cacheHandler . '/set?key=' . urlencode( $cacheKey ) . '&value=' . urlencode( $value ) . '&expires=' . (int) $expires );
                $buffer = @curl_exec( $ch );
                
                //server did not responded at all
                if (empty( $buffer ))
                    return FALSE;
                
                $data = @json_decode( $buffer, TRUE );
                
                //server responded, but did not returned a valid JSON response
                if (!is_array( $data ))
                    return FALSE;
                
                return isset( $data['ok'] ) ? $data['ok'] : FALSE;
                
            } else return FALSE;
        }

        public function flush( ) {
            if ($this->_cacheHandler) {
            
                $ch = $this->curl_init();
                curl_setopt( $ch, CURLOPT_URL, $this->_cacheHandler . '/flush' );
                $buffer = @curl_exec( $ch );
                
                //server did not responded at all
                if (empty( $buffer ))
                    return FALSE;
                
                $data = @json_decode( $buffer, TRUE );
                
                //server responded, but did not returned a valid JSON response
                if (!is_array( $data ))
                    return FALSE;
                
                return isset( $data['ok'] ) ? $data['ok'] : FALSE;
                
            } else return FALSE;
        }
        
        public function deleteURLCache( $urlPath ) {
            
            if ($this->_cacheHandler) {
                $info = parse_url( $this->_cacheHandler );
                $this->delete( '[' . $info['host'] . '] ' . $urlPath );
                $arr = explode('/', $urlPath );
                for ($i=0, $len=count($arr); $i<$len; $i++)
                    $arr[$i] = urldecode( $arr[$i] );
                $this->delete( '[' . $info['host'] . '] document://' . implode( '/', $arr ) );
                return TRUE;
            } else return FALSE;
            
        }
        
     }

?>