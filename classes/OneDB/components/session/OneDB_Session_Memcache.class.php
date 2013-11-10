<?php

    /* You should define somewhere:

        $ONEDB_MEMCACHE_SESSION = array(
            'host'  => ''
        );


     */

if (defined('ONEDB_SESSION_TYPE') && ONEDB_SESSION_TYPE == 'MEMCACHE') {

    if (!isset( $GLOBALS['ONEDB_MEMCACHE_SESSION'] ) )
        throw new Exception("Fatal: Global variable \$ONEDB_MEMCACHE_SESSION not defined!");
    
    class OneDB_Session_Memcache {
        
        protected $_conn  = NULL;
        protected $_debug = FALSE;
        
        function __construct() {
        
        }
        
        protected function get_ip_address() {
            foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
                if (array_key_exists($key, $_SERVER) === true) {
                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                            return $ip;
                        }
                    }
                }
            }
            return 'unknown';
        }
        
        public function open( $save_path, $session_name ) {

            global $ONEDB_MEMCACHE_SESSION;
            
            $this->_conn = memcache_connect(
                $ONEDB_MEMCACHE_SESSION['host'],
                isset( $ONEDB_MEMCACHE_SESSION['port'] ) ? $ONEDB_MEMCACHE_SESSION['port'] : 11211
            );
            
            if (!( is_object( $this->_conn )))
                throw new Exception("Could not connect to session database!");
            
            return TRUE;
        }
        
        public function close() {
            return TRUE;
        }
        
        public function read( $session_id ) {
            
            //@header('Set-Cookie: PHPSESSID=' . $session_id . '; Path = /; HttpOnly' );
            
            $_SERVER['SERVER_NAME'] = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'unknown';
            
            $this->fieldArray = memcache_get( $this->_conn, 'session-' . $_SERVER['SERVER_NAME'] . '_' . $session_id );
            
            if ($this->fieldArray === FALSE) {
                $this->fieldArray = array(
                    'session_id'   => $session_id,
                    'session_data' => '',
                    'ip'           => $this->get_ip_address()
                );
            }
            
            return $this->fieldArray['session_data'];
        }
        
        public function write( $session_id, $session_data ) {
            
            $this->fieldArray['session_data'] = $session_data;
            $this->fieldArray['session_id'] = $session_id;
            
            if ( memcache_set( $this->_conn, 'session-' . $_SERVER['SERVER_NAME'] . '_' . $session_id, $this->fieldArray, 0, ini_get( 'session.gc_maxlifetime' ) ) === FALSE )
                throw new Exception("Could not save session data!");
            
            return TRUE;
        }
        
        public function destroy( $session_id ) {
            $this->fieldArray['session_id'] = $session_id;

            memcache_delete( $this->_conn, 'session-' . $_SERVER['SERVER_NAME'] . '_' . $session_id );

            return TRUE;
        }
        
        public function gc( $max_lifetime ) {
            /* Does nothing, as memcache always garbages stuff */
            return TRUE;
        }
        
        public function __destruct( ) {
            return @session_write_close();
        }
    }
    
    $ONEDB_SESSION = new OneDB_Session_Memcache();
    session_set_save_handler( array( &$ONEDB_SESSION, 'open' ),
                              array( &$ONEDB_SESSION, 'close'),
                              array( &$ONEDB_SESSION, 'read'),
                              array( &$ONEDB_SESSION, 'write'),
                              array( &$ONEDB_SESSION, 'destroy'),
                              array( &$ONEDB_SESSION, 'gc')
    );

}

?>