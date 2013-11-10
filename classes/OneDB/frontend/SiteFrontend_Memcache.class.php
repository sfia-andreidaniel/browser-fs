<?php

    if (!isset($_SESSION))
        session_start();
    
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "SiteFrontend_PageStrip.inc.php";

    class SiteFrontend_Memcache {

        protected $_inited = FALSE;
        protected $_cfg    = array();
        protected $_cache  = NULL;
        
        protected $_cacheAge = 0;
        protected $_cacheUrl = '';
        protected $_cacheRule = NULL;

        function __construct() {
        
            $_SERVER['QUERY_STRING'] = trim( preg_replace('/(\?|^)_FILE_=(.*?)?(&|$)/', '?', $_SERVER['QUERY_STRING']), '?' );
        
            if (!$this->isValidRequest())
                return;
        
            global $_FRONTEND_CFG_;

            if (!is_array( $_FRONTEND_CFG_ ))
                return;

            $this->_cfg = $_FRONTEND_CFG_;
            
            if (!isset( $this->_cfg['memcache.host'] ) ||
                !isset( $this->_cfg['memcache.port'] ) ||
                !isset( $this->_cfg['memcache.urlfile'])
            ) {
                $this->_cfg = array();
                return;
            }
            
            $this->_maxAge = $this->getCacheAgeForThisPage();
            
            if (!$this->_maxAge)
                return;
            
            $this->_cache = new Memcache;
            
            if (!$this->_cache->connect( $this->_cfg['memcache.host'], $this->_cfg['memcache.port'] ))
                throw new Exception("Could not connect to memcache accelerator!");
            
            if ( $this->dumpCache() )
                return;
            
            $this->_inited = TRUE;
        }
        
        public function dumpCache() {
        
            if (empty( $this->_cacheUrl ))
                return FALSE;
        
            $cacheObjectID = '[' . $_SERVER['SERVER_NAME'] . '] ' . $this->_cacheUrl;
        
            header( 'X-OneDB-CacheObjectID: ' . $cacheObjectID );
        
            $page = $this->_cache->get( $cacheObjectID );
            
            if ($page === FALSE) {
                header('X-OneDB-PageCache: Miss');
                return FALSE;
            }
            
            header('X-OneDB-PageCache: Hit');
            
            if (defined('ONEDB_PAGESTRIP'))
                header('X-OneDB-PageStripped: Yes');
            
            die(
            
                defined('ONEDB_PAGESTRIP') ? 
                    OneDB_PageStrip(
                        $page,
                        array('img', 'video', 'script', 'embed', 'object', 'link', 'iframe')
                    ) : $page
            );
        }
        
        protected function isValidRequest() {
        
            if (!isset($_SERVER) || $_SERVER['REQUEST_METHOD'] != 'GET' || isset($_SESSION['CACHE.DISABLED']))
                return FALSE;
            
            return TRUE;
        }
        
        protected function getCacheAgeForThisPage() {
        
            $requestUrl         = @reset(explode('?', $_SERVER['REQUEST_URI']));
            $requestQueryString = $_SERVER['QUERY_STRING'];
        
            $lines = @file_get_contents( $this->_cfg['memcache.urlfile'] );

            if (empty( $lines ))
                return -1;
            
            $lines = explode("\n", $lines);
            
            $num = 1;
            
            foreach ($lines as $line) {
                if (preg_match('/^([\d]+)[\s]+(S|R|\>)[\s]+(\?[\s]+)?([\S]+)([\s]+\#[^*]+)?$/', $line, $matches)) {
                    
                    $time = $matches[1];
                    $compareMethod = $matches[2];
                    $cacheQuery = !empty($matches[3]) ? TRUE : FALSE;
                    $compareString = $matches[4];
                    
                    $urlCache = trim( $cacheQuery ? "$requestUrl?$requestQueryString" : $requestUrl, '?' );
                    
                    switch (TRUE) {
                        case $compareMethod == 'S' && $requestUrl == $compareString:
                        case $compareMethod == '>' && strpos( $requestUrl, $compareString ) === 0:
                        case $compareMethod == 'R' && preg_match( $requestUrl, $compareString ):
                            $this->_cacheUrl = $urlCache;
                            $this->_cacheAge = $time;
                            $this->_cacheRule = $line;
                            return $this->_cacheAge;
                            break;
                    }
                } else {
                    if (empty( $line ) || $line[0] == '#')
                        continue;
                    throw new Exception("SiteFrontend_Memcache: Bad cfg @line: $num in file " . $this->_cfg['memcache.urlfile'] );
                }
            }
            
            $this->_cacheUrl = $requestUrl;
            
            $this->_cacheAge = 0;
            
            return 0; //0 means no caching
        }
        
        public function store( $buffer ) {

            if ($this->_cacheAge > 0 && $this->_inited) {
                if (!$this->_cache->set( '[' . $_SERVER['SERVER_NAME'] . '] ' . $this->_cacheUrl, $buffer, FALSE, $this->_cacheAge ))
                    throw new Exception("Could not save data to memcache accelerator!");
            }

            return defined('ONEDB_PAGESTRIP') ?
                OneDB_PageStrip(
                    $buffer, 
                    array('img','video','embed','object','script', 'link', 'iframe')
                ) : $buffer;
        }
        
        public function cache_get( $key ) {
            $data = $this->_cache->get( $key );
            return array(
                'value' => $data,
                'ok'    => TRUE
            );
        }
        
        public function cache_delete( $key ) {
            return array(
                'ok' => $this->_cache->delete( $key )
            );
        }
        
        public function cache_flush( ) {
            return array(
                'ok' => $this->_cache->flush( )
            );
        }
        
        public function cache_set( $key, $value, $expire = 600 ) {
            return array(
                'ok' => $this->_cache->set( $key, $value, 0, $expire )
            );
        }

    }

?>