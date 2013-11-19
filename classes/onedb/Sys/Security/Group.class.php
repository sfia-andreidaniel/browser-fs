<?php
    
    require_once __DIR__ . '/../Umask.class.php';
    
    /*
    
        Group Struc:
            {
                "_id"     : <int>,           // group id
                "type"    : "group",         // constant, = 'group'
                "name"    : <string>,        // group name
                "members" : [ 2, 4, 8, 9 ],  // user id apartenence list
                "flags"   : <int>            // other user flags
            }
    
     */
    
    class Sys_Security_Group extends Object {
        
        const TYPE          = 'group'; // OBJECT TYPE
        const FORMAT        = '/[a-z\d]+((\.[a-z\d]+)+)?$/'; // REGEXP FOR GROUP NAME
        const SHADOW_EXPIRE = 30;      // AFTER 30 SECONDS FORCE A SHADOW FILE RESYNC FROM SERVER
        
        protected $_id      = 0;       // THE id ( gid ) of this group
        protected $_name    = '';      // THE NAME OF THIS GROUP
        protected $_flags   = 0;       // THE LIST WITH THE FLAGS FOR THIS GROUP
        protected $_members = NULL;    // THE LIST WITH THE USERS THAT ARE MEMBERS OF THIS GROUP
        
        protected $_client  = NULL;    // A LINK TO ONEDB CLIENT
        
        public function init( OneDB_Client $server, array $properties ) {
            $this->_client = $server;
            $this->_id     = $properties[ '_id' ];
            $this->_name   = $properties[ 'name' ];
            $this->_flags  = $properties[ 'flags' ];
        }
        
        // Does the group has an user with it's id $uid?
        // RETURNS TRUE OR FALSE
        public function contains( $uid ) {
            throw Object( 'Exception.Security', 'group.contains() not implemented' );
        }
        
        // converts this object to the string group name representation
        public function __toString() {
            return $this->_client->websiteName . '/' . $this->_name;
        }
    }
    
    Sys_Security_Group::prototype()->defineProperty( 'id', [
        'get' => function() {
            return $this->_id;
        }
    ] );

    Sys_Security_Group::prototype()->defineProperty( 'gid', [
        'get' => function() {
            return $this->_id;
        }
    ] );
    
    Sys_Security_Group::prototype()->defineProperty( 'name', [
        'get' => function() {
            return $this->_name;
        }
    ]);
    
    Sys_Security_Group::prototype()->defineProperty( 'flags', [
        'get' => function() {
            return $this->_flags;
        }
    ]);
    
    Sys_Security_Group::prototype()->defineProperty( 'users', [
        'get' => function() {
            return $this->_members === NULL
                ? ( $this->_members = $this->_client->sys->getMembers( 'group', $this->_id, TRUE ) )
                : $this->_members;
        }
    ] );
?>