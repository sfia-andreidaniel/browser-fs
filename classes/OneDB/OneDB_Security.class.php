<?php

    define('ONEDB_ACCESS_READ',  'r');
    define('ONEDB_ACCESS_WRITE', 'w');
    define('ONEDB_SUPERUSER', 'root');

    /* This class is used in order to integrate the JSPlatform */
    /* Users and Groups system inside the backend of OneDB     */
    /* This functionality is not used into the frontend !!!    */

    class OneDB_Security {
        
        public $db        = NULL;
        
        protected $_userName = NULL;
        protected $_groups   = array();
        protected $_all      = array();
        
        protected $dbadmin   = NULL;
        protected $conn      = NULL;
        
        function __construct( &$OneDB_Instance ) {
            $this->db = $OneDB_Instance->db;
        
            if (!isset( $GLOBALS['__DBADMIN__'] ))
                throw new Exception("Fatal: OneDB_Security works only in backend mode!");
            
            $this->dbadmin = NULL;
            
            if (!isset( $GLOBALS['mysql_conn'] ))
                throw new Exception("Fatal: OneDB_Security: JSPlatform mysql connection not detected!");
            
            $this->conn = $GLOBALS['mysql_conn'];
        }
        
        function isSuperUser() {
            return (strtolower( $this->_userName ) == strtolower( ONEDB_SUPERUSER )) || 
                ( function_exists('policy_enabled') && policy_enabled('OneDB_Gods') );
        }
        
        function login( $userName ) {
            $sql = "SELECT lower($this->dbadmin.admin_groups.name) AS group_name
                    FROM   $this->dbadmin.admin_auth
                    INNER JOIN $this->dbadmin.admin_group_mappings ON
                          $this->dbadmin.admin_group_mappings.uid = $this->dbadmin.admin_auth.id
                    LEFT  JOIN $this->dbadmin.admin_groups ON
                          $this->dbadmin.admin_groups.gid = $this->dbadmin.admin_group_mappings.gid
                    WHERE $this->dbadmin.admin_auth.user = '" . mysql_real_escape_string( $userName ) . "'";
            $result = mysql_query( $sql, $this->conn );
            
            if (!$result)
                throw new Exception("Fatal: OneDB_Security: MySQL error: " . mysql_error( $this->conn ) );
            
            $this->_groups = array();
            $this->_all    = array();
            
            while ($row = mysql_fetch_row( $result ) ) {
                $this->_groups[] = $row[0];
                $this->_all[]    = ( 'group ' . $row[0] );
            }
            
            $this->_userName = $userName;
            $this->_all[]    = 'user ' . $userName;
            
            return TRUE;
        }
        
        public function fetchObject( $objectID ) {
            
            if (empty( $objectID ) || $objectID === NULL || $objectID === FALSE)
                return new OneDB_SecurityRootToken( $this, array(
                    'r1' => array(),
                    'r0' => array(),
                    'w1' => array(),
                    'w0' => array(),
                    'o'  => NULL
                ) );
            
            $result = $this->db->security->findOne(
                array(
                    'o' => MongoIdentifier( $objectID )
                )
            );
            
            if ($result !== NULL)
                return new OneDB_SecurityToken( $this, $result );
            else
                return new OneDB_SecurityToken( $this, array(
                    'r1' => array(),
                    'r0' => array(),
                    'w1' => array(),
                    'w0' => array(),
                    'o'  => MongoIdentifier( $objectID )
                )
            );
        }
        
        public function __get( $propertyName ) {
            return $propertyName == 'all' ? $this->_all : $this->fetchObject( "$propertyName" );
        }
    }
    
    class OneDB_SecurityToken {
    
        protected $_parent = NULL;
        protected $_tokens = NULL;
        protected $_altered= FALSE;
        
        public function __construct( $_parent, $_tokens ) {
            $this->_parent = $_parent;
            $this->_tokens = $_tokens;
        }
        
        public function canRead( $default = FALSE ) {
        
            if ($this->_parent->isSuperUser())
                return TRUE;
        
            $all = $this->_parent->all;
            return count( array_intersect( $this->_tokens['r0'], $all ) ) ? FALSE : 
                (  count( array_intersect( $this->_tokens['r1'], $all ) ) ? TRUE : $default
                );
        }
        
        public function canWrite( $default = TRUE ) {
        
            if ($this->_parent->isSuperUser())
                return TRUE;
        
            $all = $this->_parent->all;
            return count( array_intersect( $this->_tokens['w0'], $all ) ) ? FALSE :
                (  count( array_intersect( $this->_tokens['w1'],  $all ) ) ? TRUE : $default
                );
        }
        
        public function explain() {
            $out = array();
            
            foreach ( $this->_tokens['w1'] as $allowWriteUser )
                $out[ $allowWriteUser ] = array( 'write' => TRUE );
            
            foreach ( $this->_tokens['w0'] as $denyWriteUser )
                $out[ $denyWriteUser ] = array( 'write' => FALSE );
            
            foreach ( $this->_tokens['r1'] as $allowReadUser )
                if (!isset( $out[ $allowReadUser ] ) )
                     $out[ $allowReadUser ] = array( 'read' => TRUE );
                else $out[ $allowReadUser ]['read'] = TRUE;
            
            foreach ($this->_tokens['r0'] as $denyReadUser ) 
                if (!isset( $out[ $denyReadUser ] ) )
                     $out[ $denyReadUser ] = array( 'read' => FALSE );
                else $out[ $denyReadUser ]['read'] = FALSE;
            
            return $out;
        }
        
        public function reset() {
            $this->_tokens['w1'] =
            $this->_tokens['w0'] =
            $this->_tokens['r1'] =
            $this->_tokens['r0'] = array();
        }
        
        /* setAccess( 'read', TRUE, 'u:andrei' ); */
        
        public function setAccess( $accessType, $accessValue, $entityName ) {
            
            if (!in_array( $accessType, array( ONEDB_ACCESS_READ, ONEDB_ACCESS_WRITE )))
                throw new Exception("Invalid access type!");
            
            if (!is_bool( $accessValue ))
                throw new Exception("Invalid access value (expected bool)");
            
            $entityName = strtolower( $entityName );
            
            $keyName = $accessType . ( $accessValue ? '1' : '0' );
            $negKeyName = $accessType . ( $accessValue ? '0' : '1' );
            
            if (!in_array( $entityName, $this->_tokens[ $keyName ] ) )
                $this->_tokens[ $keyName ][] = $entityName;
            
            if (in_array( $entityName, $this->_tokens[ $negKeyName ] ) ) {
                $this->_tokens[ $negKeyName ] = array_filter( $this->_tokens[ $negKeyName ], function( $item ) use ( $entityName ) {
                    return $item == $entityName ? FALSE : TRUE;
                } );
            }

            $this->_altered = TRUE;
        }
        
        public function unsetAccess( $accessType, $entityName ) {
            if (!in_array( $accessType, array( ONEDB_ACCESS_READ, ONEDB_ACCESS_WRITE )))
                throw new Exception("Invalid access type!");
            
            $entityName = strtolower( $entityName );
            
            if ( in_array( $entityName, $this->_tokens[ $keyName = $accessType . '0' ] ) ) {
                $this->_tokens[ $keyName ] = array_filter( $this->_tokens[ $keyName ], function( $item ) use ($entityName) {
                    return $item == $entityName ? FALSE : TRUE;
                } );
                $this->_altered = TRUE;
            }

            if ( in_array( $entityName, $this->_tokens[ $keyName = $accessType . '1' ] ) ) {
                $this->_tokens[ $keyName ] = array_filter( $this->_tokens[ $keyName ], function( $item ) use ($entityName) {
                    return $item == $entityName ? FALSE : TRUE;
                } );
                $this->_altered = TRUE;
            }
            
        }
        
        private function _scan( $nodeID, &$out, &$collection) {
            $cursor = $collection->find(
                array(
                    "_parent" => $nodeID
                ),
                array(
                    "_id"
                )
            );
            while ($cursor->hasNext()) {
                $row = $cursor->getNext();
                $out[] = "$row[_id]";
                $this->_scan( $row['_id'], $out, $collection );
            }
        }
        
        protected function getPropagateObjects( ) {
            
            $out = array();
            $collection = $this->_parent->db->categories;
            $startNode = MongoIdentifier( $this->_tokens['o'] );
            
            $this->_scan( $startNode, $out, $collection );
            
            return $out;
        }
        
        public function setSecurity( $securityObject ) {
            if (!is_array( $securityObject ))
                throw new Exception("OneDB_SecurityToken::setSecurity: Expected array!");
            
            $this->reset();
            
            $propagate = array();
            
            foreach (array_keys( $securityObject ) as $entityName) {
                if (isset( $securityObject[ $entityName ]['read'] ) )
                    $this->setAccess( ONEDB_ACCESS_READ, $securityObject[ $entityName ]['read'], $entityName );
                else
                    $this->unsetAccess( ONEDB_ACCESS_READ, $entityName );
                    
                if (isset( $securityObject[ $entityName ]['write'] ) )
                    $this->setAccess( ONEDB_ACCESS_WRITE, $securityObject[ $entityName ]['write'], $entityName );
                else
                    $this->unsetAccess( ONEDB_ACCESS_WRITE, $entityName );
                
                if (isset( $securityObject[ $entityName ]['propagate'] ) &&
                    $securityObject[ $entityName ]['propagate'] == TRUE
                ) $propagate[ $entityName ] = $securityObject[ $entityName ];
            }
            
            
            /* Now we propagate the security tokens to children */
            
            if (!count( $propagate ))
                return;
            
            $childrens = $this->getPropagateObjects();
            
            foreach ($childrens as $child) {
                $token = $this->_parent->{"$child"};

                foreach (array_keys( $propagate ) as $entityName ) {

                    if (isset( $propagate[ $entityName ]['read'] ) )
                        $token->setAccess( ONEDB_ACCESS_READ, $propagate[ $entityName ][ 'read' ], $entityName );
                    else
                        $token->unsetAccess( ONEDB_ACCESS_READ, $entityName );

                    if (isset( $propagate[ $entityName ]['write'] ) )
                        $token->setAccess( ONEDB_ACCESS_WRITE, $propagate[ $entityName ][ 'write' ], $entityName );
                    else
                        $token->unsetAccess( ONEDB_ACCESS_WRITE, $entityName );

                }
            }

        }
        
        public function __destruct() {
            if (!$this->_altered)
                return;
            
            if (!in_array( '_id', array_keys( $this->_tokens ) ) )
                $this->_tokens['_id'] = new MongoId();
            
            $this->_parent->db->security->update(
                array(
                    '_id' => $this->_tokens['_id']
                ),
                $this->_tokens,
                array(
                    'upsert' => TRUE,
                    'multiple' => FALSE,
                    'safe' => TRUE
                )
            );
        }
    }
    
    class OneDB_SecurityRootToken extends OneDB_SecurityToken {

        public function setAccess( $accessType, $accessValue, $entityName ) {
            throw new Exception( "Feature not supported on root object!" );
        }

        public function setSecurity( $securityObject, $propagate = FALSE ) {
            throw new Exception( "Feature not supported on root object!" );
        }
        
        public function canRead( $default = FALSE ) {
            return $this->_parent->isSuperUser();
        }
        
        public function canWrite( $default = TRUE ) {
            return $this->_parent->isSuperUser();
        }
        
        public function __destruct() {
            /* Nothing at destructor */
        }
    }

?>