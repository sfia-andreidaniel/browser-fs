<?php

    require_once __DIR__ . '/../../Umask.class.php';
    require_once __DIR__ . '/../User.class.php';
    
    class Sys_Security_User_Unauthenticated extends Sys_Security_User {
        
        public function init( OneDB_Client $server, $shadow = '', $userName = '', $password = '', $shadowKey = '' ) {
            
            $this->_client = $server;

        }
        
        public function setProperties( array $properties ) {
            
            $this->_id = $properties[ '_id' ];
            $this->_name = $properties[ 'name' ];
            $this->_umask = $properties[ 'umask' ];
            $this->_flags = $properties[ 'flags' ];

            return $this;

        }
        
    }

    Sys_Security_User_Unauthenticated::prototype()->defineProperty( 'id', [
        'get' => function() {
            return $this->_id;
        }
    ]);

    Sys_Security_User_Unauthenticated::prototype()->defineProperty( 'name', [
        'get' => function() {
            return $this->_name;
        }
    ]);

    Sys_Security_User_Unauthenticated::prototype()->defineProperty( 'umask', [
        'get' => function() {
            return $this->_umask;
        }
    ]);

    Sys_Security_User_Unauthenticated::prototype()->defineProperty( 'flags', [
        'get' => function() {
            return $this->_flags;
        }
    ]);

    Sys_Security_User_Unauthenticated::prototype()->defineProperty( 'groups', [
        'get' => function() {

            if ( $this->_members === NULL ) {
                return ( $this->_members = $this->_client->sys->getMembers(
                    'user', $this->_id, TRUE
                ) );
            } else return $this->_members;
        }
    ] );

    Sys_Security_User_Unauthenticated::prototype()->defineProperty( 'uid', [
        'get' => function() {
            return $this->_id;
        }
    ] );

    Sys_Security_User_Unauthenticated::prototype()->defineProperty( 'gid', [
        'get' => function() {
            if ( $this->_members === NULL )
                $this->_members = $this->_client->sys->getMembers(
                    'user', $this->_id, TRUE
                );
            return count( $this->_members ) ? $this->_members[0]->id : NULL;
        }
    ] );

?>