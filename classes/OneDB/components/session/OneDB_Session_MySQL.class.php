<?php

    /* You should define somewhere:
        
        $ONEDB_MYSQL_SESSION = array(
            'host'  => '',
            'user'  => '',
            'pass'  => '',
            'db'    => '',
            'table' => ''
        );
        
        the table should point to a mysql table with the following structure:
        
        CREATE TABLE `php_session` (
          `session_id` varchar(32) NOT NULL default '',
          `date_created` int(10) DEFAULT 0,
          `last_updated` int(10) DEFAULT 0,
          `session_data` longtext,
          `session_data_json` longtext,
          `ip` char(15)
          PRIMARY KEY  (`session_id`),
          KEY `last_updated` (`last_updated`)
        ) ENGINE=MyISAM
        
     */

if (defined('ONEDB_SESSION_TYPE') && ONEDB_SESSION_TYPE == 'MYSQL') {

    if (!isset( $GLOBALS['ONEDB_MYSQL_SESSION'] ) )
        throw new Exception("Fatal: Global variable \$ONEDB_MYSQL_SESSION not defined!");
    
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "OneDB_Session_Unserializer.class.php";
        
    class OneDB_Session_MySQL {
        
        protected $_conn  = NULL;
        protected $_table = NULL;
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

            global $ONEDB_MYSQL_SESSION;
            
            $this->_conn = mysql_connect(
                $ONEDB_MYSQL_SESSION['host'],
                $ONEDB_MYSQL_SESSION['user'],
                $ONEDB_MYSQL_SESSION['pass']
            );
            
            if (!is_resource( $this->_conn ))
                throw new Exception("Could not connect to session database: " . mysql_error() );
            
            $this->_table = $ONEDB_MYSQL_SESSION['table'];
            
            return TRUE;
        }
        
        public function close() {
            return TRUE;
        }
        
        public function read( $session_id ) {
            
            // @header('Set-Cookie: PHPSESSID=' . $session_id . '; Path = /; HttpOnly' );
            
            $sql = "SELECT *, 'update' AS method FROM $this->_table WHERE session_id = '" . mysql_escape_string( $session_id ) . "' LIMIT 1";
            $result = mysql_query( $sql, $this->_conn );
            
            if (!$result)
                throw new Exception("Could not fetch session data from server: " . mysql_error( $this->_conn ) );
            
            if ( !mysql_num_rows( $result ) ) {
                $this->fieldArray = array(
                    'session_id'   => $session_id,
                    'date_created' => ($now = time() ),
                    'last_updated' => $now,
                    'session_data' => '',
                    'method'       => 'insert'
                );
            } else
                $this->fieldArray = mysql_fetch_array( $result, MYSQL_ASSOC );
            
            return $this->fieldArray['session_data'];
        }
        
        public function write( $session_id, $session_data ) {
            
            $this->fieldArray['last_updated'] = time();
            $this->fieldArray['session_data'] = $session_data;
            
            if ($session_id != $this->fieldArray['session_id'] ) {
                $this->fieldArray['session_id'] = $session_id;
                $this->fieldArray['method'] = 'insert';
            }
            
            switch ($this->fieldArray['method']) {
                case 'insert':
                    $sql =   "INSERT IGNORE INTO " . $this->_table . " ( session_id, date_created, last_updated, session_data, session_data_json, ip ) "
                           . "VALUES ( '" . mysql_escape_string( $this->fieldArray['session_id'] ) . "', " 
                                          . $this->fieldArray['date_created'] . ", " 
                                          . $this->fieldArray['last_updated'] . ", '" 
                                          . mysql_escape_string( $this->fieldArray['session_data'] ) . "', '"
                                          . mysql_escape_string( json_encode( OneDB_Session_Unserializer::unserialize( $this->fieldArray['session_data'] ) ) ) . "', '" 
                                          . $this->get_ip_address() . "')";
                    break;
                case 'update':
                    $sql =   "UPDATE " . $this->_table . 
                             " SET session_data = '" . mysql_escape_string( $this->fieldArray['session_data'] ) . "', "
                                . "session_data_json = '" . mysql_escape_string( json_encode( OneDB_Session_Unserializer::unserialize( $this->fieldArray['session_data'] ) ) ) . " "
                                . "', last_updated = " . $this->fieldArray['last_updated'] 
                                . " WHERE session_id = '" . mysql_escape_string( $this->fieldArray['session_id'] ) 
                                . "' LIMIT 1";
                    break;
            }
            
            $result = mysql_query( $sql, $this->_conn );
            
            if (!$result)
                throw new Exception("Could not write session data because of a database server query failure: " . mysql_error( $this->_conn ));
            
            return TRUE;
        }
        
        public function destroy( $session_id ) {
            $this->fieldArray['session_id'] = $session_id;

            $sql = "DELETE FROM " . $this->_table . " WHERE session_id = '" . mysql_escape_string( $this->fieldArray['session_id'] ) . "' LIMIT 1";
            $result = mysql_query( $sql, $this->_conn );
            if (!$result)
                throw new Exception("Could not destroy session because of a database sql server failure: " . mysql_error( $this->_conn ) );
            
            $this->fieldArray['session_data'] = '';
            
            return TRUE;
        }
        
        public function gc( $max_lifetime ) {
            $now = @time();
            $sql = "DELETE FROM " . $this->_table . " WHERE $now - last_updated > " . $max_lifetime;
            $result = mysql_query( $sql, $this->_conn );
            if (!$result)
                throw new Exception("Could not garbage-collect session because of a database sql failure: " . mysql_error( $this->_conn ) );
            return TRUE;
        }
        
        public function __destruct( ) {
            return @session_write_close();
        }
    }
    
    $ONEDB_SESSION = new OneDB_Session_MySQL();
    session_set_save_handler( array( &$ONEDB_SESSION, 'open' ),
                              array( &$ONEDB_SESSION, 'close'),
                              array( &$ONEDB_SESSION, 'read'),
                              array( &$ONEDB_SESSION, 'write'),
                              array( &$ONEDB_SESSION, 'destroy'),
                              array( &$ONEDB_SESSION, 'gc')
    );
    
}

?>