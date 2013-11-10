<?php

    if (!isset($_SESSION))
        session_start();
    

    class OneDB_WidgetCache {

        protected $_inited = FALSE;
        protected $_cfg    = array();
        protected $_cache  = NULL;
        
        protected $_cacheAge = 0;
        protected $_cacheName = '';

        public    $widget = NULL;

        function __construct( $cacheName, $cacheAge ) {
        
            global $_FRONTEND_CFG_;

            if (!is_array( $_FRONTEND_CFG_ ))
                return;

            $this->_cfg = $_FRONTEND_CFG_;
            
            if (!isset( $this->_cfg['memcache.host'] ) ||
                !isset( $this->_cfg['memcache.port'] )
            ) {
                $this->_cfg = array();
                return;
            }
            
            $this->_cacheName = "[$_SERVER[SERVER_NAME]] $cacheName";
            
            $this->_cacheAge  = $cacheAge;
            
            if (!$this->_cacheAge)
                return;
            
            $this->_cache = new Memcache;
            
            if (!$this->_cache->connect( $this->_cfg['memcache.host'], $this->_cfg['memcache.port'] ))
                throw new Exception("Could not connect to memcache accelerator!");
            
            if ( $this->dumpCache() === TRUE )
                return;
            
            $this->_inited = TRUE;
        }
        
        public function dumpCache() {
        
            if (empty( $this->_cacheName ))
                return FALSE;
        
            $widget = $this->_cache->get( $this->_cacheName );
            
            if ($widget === FALSE)
                return FALSE;
            
            $this->widget = $widget;
            
            return TRUE;
        }
        
        public function store( $buffer ) {
            
            if ($this->_cacheAge > 0 && $this->_inited) {
                if (!$this->_cache->set( $this->_cacheName, $buffer, FALSE, $this->_cacheAge ))
                    throw new Exception("Could not save data to memcache accelerator!");
            }
            
            return $buffer;
        }

    }
    
    function CacheWidget( $cacheName, $cacheAge, $generateFunc, $allowHtmlCommentsForCachedContent = TRUE ) {
    
        if (!is_callable( $generateFunc ) )
            throw new Exception("CacheWidget: 3rd argument should be a function that returns the widget code");
    
        if ( !isset($_SESSION['CACHE.DISABLED']) ) {
    
            $cache = new OneDB_WidgetCache(
                $cacheName, $cacheAge
            );
        
            return $cache->widget === NULL ? 
            
                $cache->store( $generateFunc() ) : 
            
                (!$allowHtmlCommentsForCachedContent ? 
                    $cache->widget : 
                    "<!-- memcache: " . htmlentities( $cacheName ) . " -->" . $cache->widget . "<!-- /memcache -->"
                );
        } else {
            return $generateFunc();
        }
    }

?>