<?php

    class OneDB_TextSearch_Server {
        
        protected $_url = NULL;
        protected $_ch = NULL;
        protected $_oneDB = NULL;
        
        public function __construct( $serverURL, $oneDB ) {
            $this->_url = $serverURL;
            $this->_oneDB = $oneDB;
        }
        
        private function curl() {
            if ($this->_ch === NULL) {
                $this->_ch = curl_init();
                curl_setopt( $this->_ch, CURLOPT_USERAGENT, 'OneDB' );
                curl_setopt( $this->_ch, CURLOPT_RETURNTRANSFER, 1 );
                curl_setopt( $this->_ch, CURLOPT_FOLLOWLOCATION, 1 );
            }
            return $this->_ch;
        }
        
        protected function parseResponse( $buff ) {
            if (empty( $buff) )
                return FALSE;
            
            $data = @json_decode( $buff, TRUE );
            
            return ( is_array( $data ) && isset( $data['ok'] ) && $data['ok'] === TRUE && isset( $data['id'] ) ) ? $data['id'] : FALSE;
        }
        
        public function update( $id ) {
            if ($this->_url !== NULL) {
                $ch = $this->curl();
                curl_setopt( $ch, CURLOPT_URL, $this->_url . "/update/$id" );
                return $this->parseResponse( curl_exec( $ch ) );
            } else return TRUE;
        }
        
        public function create( $id ) {
            if ($this->_url !== NULL) {
                $ch = $this->curl();
                curl_setopt( $ch, CURLOPT_URL, $this->_url . "/create/$id" );
                return $this->parseResponse( curl_exec( $ch ) );
            } else return TRUE;
        }
        
        public function delete( $id ) {
            if ($this->_url !== NULL) {
                $ch = $this->curl();
                curl_setopt( $ch, CURLOPT_URL, $this->_url . "/delete/$id" );
                return $this->parseResponse( curl_exec( $ch ) );
            } else return TRUE;
        }
        
        public function setServerAddress( $url ) {
        
            if ($url !== NULL) {
            
                $url = trim( $url, '/ ' );
        
                $info = @parse_url( $url );
                
                if (!isset( $info['path'] ) || !preg_match('/^\/[a-z0-9_]+$/i', $info['path']))
                    throw new Exception("Invalid mongosphinx url!");
                
                if (!isset( $info['scheme'] ) || $info['scheme'] != 'http')
                    throw new Exception("Invalid mongosphinx scheme (only http:// alowed)");
                
                /* save changes */
                $this->_oneDB->db->config->update(
                    array(
                        'name' => 'sphinxSearch'
                    ),
                    array(
                        '$set' => array(
                            'value' => $url
                        )
                    ),
                    array(
                        'upsert' => TRUE,
                        'safe' => TRUE,
                        'multiple' => FALSE
                    )
                );
                
                $this->_url = $url;
                
            } else $this->_url = NULL;
        }
        
        public function getServerAddress() {
            return $this->_url;
        }
        
        public function search( $question, $maxResults ) {
            
            if ($this->_url === NULL)
                return array();
            
            $ch = $this->curl();
            curl_setopt( $ch, CURLOPT_URL, $url = $this->_url . "/search?q=" . urlencode( $question ) . "&limit=" . (int)$maxResults );
            
            $buffer = @curl_exec( $ch );

            if ( empty( $buffer ) )
                throw new Exception("Could not communicate with mongoSphinx server!");
            
            $idList = @json_decode( $buffer, TRUE );
            
            if (!is_array( $idList ))
                throw new Exception("mongoSphinx server did not returned a valid response!: $buffer");
            
            if (isset( $idList['error'] ))
                throw new Exception("MongoSphinx Error: " . $idList['error']);
            
            for ($i=0, $len = count($idList); $i<$len; $i++)
                $idList[$i] = MongoIdentifier( $idList[$i] );
            
            
            return $idList;
        }
        
    }

?>