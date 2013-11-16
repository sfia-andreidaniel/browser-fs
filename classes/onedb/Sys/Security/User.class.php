<?php
    
    require_once __DIR__ . '/../Umask.class.php';
    
    /*
    
        User Struc:
            {
                "_id"     : <int>,           // user id
                "type"    : "user",          // constant, = 'user'
                "name"    : <string>,        // user name
                "members" : [ 2, 4, 8, 9 ],  // groups id apartenence list
                "umask"   : <int>            // default user mask
                "flags"   : <int>            // other user flags
            }
    
     */
    
    class Sys_Security_User extends Object {
        
        const TYPE               = 'user';   // OBJECT TYPE
        const FORMAT             = '/[a-z\d]+((\.[a-z\d]+)+)?$/'; // REGEXP FOR USER NAME
        const CHALLENGE_EXPIRE   = 600;      // AFTER 10 MINUTES OF INACTIVITY, EXPIRE THE CHALLENGE KEY
        
        protected $_id           = NULL;     // USER ID
        protected $_name         = NULL;     // USER NAME
        protected $_umask        = 0;        // DEFAULT USER WRITE MASK
        protected $_flags        = 0;        // ADDITIONAL USER FLAGS. NOT USED AT THIS POINT
        protected $_members      = NULL;     // USER MEMBERS ID LIST

        protected $_sh_key       = '';       // SHADOW KEY, GENERATED WHEN LOGIN IS DONE VIA THE MONGO DATABASE.
        protected $_sh_challenge = '';    // SHADOW KEY CHALLENGE
        
        private   $_password     = NULL;     // MD5ED PASSWORD
        
        protected $_client       = NULL;     // THE ONEDB SERVER WHERE THIS USER ACCOUNT IS
        protected $_shadow       = NULL;     // <MongoCollection> shadow collection
        
        // NOTE THAT THE @param shadow and @param $userName are of type string and empty
        // FOR PHP STRICT STANDARDS PURPOSES.
        public function init( OneDB_Client $server, $shadow = '', $userName = '', $password = '', $shadowKey = '' ) {

            if ( !( $shadow instanceof MongoCollection ) )
                throw Object( 'Exception.Security', 'bad shadow argument. it should be of type MongoCollection' );
            
            if ( !is_string( $userName ) || !strlen( $userName ) )
                throw Object( 'Exception.Security', 'bad username. it should be of type string and not empty' );

            $this->_client = $server;
            $this->_shadow = $shadow;
            $this->_sh_challenge = $shadowKey;
            
            switch ( TRUE ) {
                
                case is_string( $userName ):
                    
                    if ( !preg_match( self::FORMAT, $userName ) )
                        throw( 'Invalid username. A username must contain only letters a..z, numbers 0..9, and dots' );
                
                    $this->_name = $userName;
                    $this->_login( $this->_name, $password );
                    break;
                
                default:
                    throw Object( 'Exception.Security', 'Bad username: expected uid or uname!' );
                    break;
            }
            
        }

        // BEGIN PUBLIC METHODS

        // the representation of this user in string format is the name of the user
        // converted to string
        public function __toString() {
            return $this->_client->websiteName . '/' . $this->_name;
        }
        
        // is the user member of the group with the id $gid?
        public function memberOf( $gid ) {
            throw Object( 'Exception.Security', 'user.memberof not implemented' );
        }
        
        // BEGIN PRIVATE METHODS
        
        /* Generates an unique hash for an account. The hash is used to store
           the user on the shadow local file in /dev/shm/onedb.acl.@getLoginHash()
         */
        private function getLoginHash() {
            
            $ip = isset( $_SERVER['REMOTE_ADDR'] )
                ? $_SERVER['REMOTE_ADDR']
                : '0.0.0.0';
            
            $sapi = php_sapi_name();
            
            $ua   = isset( $_SERVER['HTTP_USER_AGENT'] )
                ? $_SERVER['HTTP_USER_AGENT']
                : 'none';
            
            return md5( $ip . ':' . $sapi . ':' . $ua . ':' . $this->_name . ':' . $this->_client->websiteName );
            
        }
        
        // Performs an authentication on mongodb shadow collection.
        // return TRUE on success, or FALSE on failure if the user does not
        // exists or if the password is bad.
        //
        // IF the mongo authentication is done, a local shadow file
        // will be written on the disk in etc/shadow for this user.
        //
        // The name of the shadow file is computed with $this->getLocalShadowPath()
        private function mongoLogin( $uname, $password ) {
            
            $result = $this->_shadow->findOne( [
                'type' => 'user',
                'name' => $this->_name,
                'password' => $password
            ] );
            
            // user not found
            if ( $result === NULL )
                return FALSE;
            
            // user found
            $this->_id      = $result[ '_id' ];
            $this->_umask   = $result[ 'umask' ];
            $this->_flags   = $result[ 'flags' ];
            
            // generate a shadow key
            $this->_sh_key = md5( time() . '-' . rand( 1000000, 9999999 ) );
            
            // save user in local shadow file in order to not
            // interogate mongodb each time and require user a password
            $this->saveInLocalShadow();
            
            // login successfull
            return TRUE;
        }
        
        // returns the full name to the local user shadow file
        private function getLocalShadowPath( $acl ) {
            return __DIR__ . '/../../../../etc/shadow/' . $this->_name . '.' . $acl . '.shadow';
        }
        
        // saves the user data in local shadow file, in order to make authentication
        // next time via the shadow challenge.
        //
        // if the shadow file is not used for 10 minutes, the file will be considered
        // expired.
        private function saveInLocalShadow() {
            // creates a /dev/shm/onedb.acl.@getLoginHash() file containing
            // the format of the file is:
            $data = json_encode([
                '_id' => $this->_id,
                '_name' => $this->_name,
                '_umask' => $this->_umask,
                '_flags' => $this->_flags,
                '_created' => time(),
                '_hash' => $acl = $this->getLoginHash(),
                '_sh_key' => $this->_sh_key
            ]);
            
            file_put_contents( $this->getLocalShadowPath( $acl ), $data );
        }
        
        // attempts to load the user from it's shadow file, not from
        // the mongo database. this is to ensure that the rpc is not
        // sending the client password when muxing / demuxing, and to
        // not make queries on the mongo server each time a new
        // OneDB_Client is instantiated
        protected function shadowLogin( $uname ) {
            $acl    = $this->getLoginHash();
            $shadow = $this->getLocalShadowPath( $acl );
            
            if ( !file_exists( $shadow ) || !is_readable( $shadow ) )
                return FALSE;
            
            $data = file_get_contents( $shadow );
            
            $info = @json_decode( $data, TRUE );
            
            if ( !is_array( $info ) )
                // Failed to decode data from the shadow file as json
                return FALSE;
            
            // test fields
            $fields = [ '_id', '_name', '_umask', '_flags', '_created', '_hash', '_sh_key' ];
            
            foreach ( $fields as $key )
                if ( !isset( $info[ $key ] ) )
                    // The shadow file don't contain all the required fields
                    return FALSE;
            
            // check if the shadow challenge matches with the shadow key
            if ( $this->_sh_key != $this->_sh_challenge )
                return FALSE;
            
            // if the login by shadow has not been used for more than 10 minutes, request authentication
            // again
            
            if ( time() - $info[ '_created' ] > self::CHALLENGE_EXPIRE )
                throw Object( 'Exception.Security', 'auth expired. please reauthenticate' );
            
            // Initialize the fields
            $this->_id = $info[ '_id' ];
            $this->_name = $info['_name'];
            $this->_umask = $info['_umask'];
            $this->_flags = $info[ '_flags' ];
            $this->_sh_key = $info[ '_sh_key' ];
            
            // update the shadow file
            $info[ '_created' ] = time();
            
            // replace the shadow file with a new one
            @file_put_contents( $shadow, json_encode( $info ) );
            
            return TRUE;
        }
        
        // performs a login via username and password.
        // if param password is blank, a shadow login attempt
        // will be made, otherwise a mongo login attempt
        // will be made.
        //
        // !!!IMPORTANT: if the username is 'onedb', this is a special
        // login, and a special login mode will be made.
        protected function _login( $uname, $password ) {
            
            if ( $uname != 'onedb' ) {
            
                if ( !strlen( $password ) ) {
                    // login via shadow file
                    if ( !$this->shadowLogin( $uname ) )
                        throw Object( 'Exception.Security', 'failed to login via shadow mechanism, please reauthenticate', 11 );
                } else {
                    // login via onedb server
                    if ( !$this->mongoLogin( $uname, $password ) )
                        throw Object( 'Exception.Security', 'invalid username or password', 10 );
                }
            
            } else {
                
                $this->_id       = 0;
                $this->_name     = 'onedb';
                $this->_umask    = 0;
                $this->_flags    = 0;
                
                if ( $password != md5( self::getOneDBPassword() ) )
                    throw Object( 'Exception.Security', 'failed to login as onedb: bad password', 13 );
                
            }
            
        }
        
        // retrieves the local onedb username password from the etc/onedb.shadow.gen.
        // that file should be created by the onedb installer, and it's content should
        // be randomly generated.
        static protected function getOneDBPassword() {
            return @file_get_contents( __DIR__ . '/../../../../etc/onedb.shadow.gen' );
        }
    }
    
    Sys_Security_User::prototype()->defineProperty( 'id', [
        'get' => function() {
            return $this->_id;
        }
    ]);

    Sys_Security_User::prototype()->defineProperty( 'name', [
        'get' => function() {
            return $this->_name;
        }
    ]);

    Sys_Security_User::prototype()->defineProperty( 'umask', [
        'get' => function() {
            return $this->_umask;
        }
    ]);

    Sys_Security_User::prototype()->defineProperty( 'flags', [
        'get' => function() {
            return $this->_flags;
        }
    ]);

    Sys_Security_User::prototype()->defineProperty( 'shadowKey', [
        'get' => function() {
            return $this->_sh_key;
        }
    ]);
    
    Sys_Security_User::prototype()->defineProperty( 'groups', [
        'get' => function() {

            if ( $this->_members === NULL ) {
                return ( $this->_members = $this->_client->sys->getMembers(
                    'user', $this->_id, TRUE
                ) );
            } else return $this->_members;
        }
    ] );

    Sys_Security_User::prototype()->defineProperty( 'uid', [
        'get' => function() {
            return $this->_id;
        }
    ] );

    Sys_Security_User::prototype()->defineProperty( 'gid', [
        'get' => function() {
            if ( $this->_members === NULL )
                $this->_members = $this->_client->sys->getMembers(
                    'user', $this->_id, TRUE
                );
            return count( $this->_members ) ? $this->_members[0]->id : NULL;
        }
    ] );
    
?>