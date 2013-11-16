<?php

    require_once __DIR__ . '/../Umask.class.php';

    // The role of this class is to retrieve all the users and all the
    // groups from the mongo database or from a local shadow file
    
    class Sys_Security_Management extends Object {
        
        const SHADOW_EXPIRE = 30; // After 30 seconds do a shadow resync with the database
        
        private $_users  = [];
        private $_groups = [];
        
        private $_server = NULL;
        private $_shadow = NULL;
        
        public function init( OneDB_Client $client, MongoCollection $shadow ) {
            $this->_shadow = $shadow;
            $this->_server = $client;
            
            if ( !$this->loadFromShadow() && !$this->loadFromDatabase() )
                throw Object( 'Exception.Security', 'Failed to ininitialize security management engine' );
        }
        
        /* Returns a Sys.Security.Group based on it's id or it's group name */
        public function group( $groupIdOrGroupName ) {
            
            switch ( TRUE ) {
                
                case is_int( $groupIdOrGroupName ):
                    
                    if ( !isset( $this->_groups[ $groupIdOrGroupName ] ) )
                        return NULL;
                    
                    return Object( 'Sys.Security.Group', $this->_server, $this->_groups[ $groupIdOrGroupName ] );
                    
                    break;
                
                case is_string( $groupIdOrGroupName ):
                    
                    foreach ( array_keys( $this->_groups ) as $gid ) {
                        
                        if ( $this->_groups[ $gid ][ 'name' ] == $groupIdOrGroupName )
                            return Object( 'Sys.Security.Group', $this->_server, $this->_groups[ $gid ] );
                        
                    }
                    
                    return NULL;
                    
                    break;
            }
            
        }

        /* Returns a Sys.Security.User.Unauthenticated based on it's id or it's user name */
        public function user( $userIdOrUserName ) {
            
            switch ( TRUE ) {
                
                case is_int( $userIdOrUserName ):
                    
                    if ( !isset( $this->_users[ $userIdOrUserName ] ) )
                        return NULL;
                    
                    return Object( 'Sys.Security.User.Unauthenticated', $this->_server )->setProperties( $this->_users[ $userIdOrUserName ] );
                    
                    break;
                
                case is_string( $userIdOrUserName ):
                    
                    foreach ( array_keys( $this->_users ) as $uid ) {
                        
                        if ( $this->_users[ $uid ][ 'name' ] == $userIdOrUserName )
                            return Object( 'Sys.Security.User.Unauthenticated', $this->_server )->setProperties( $this->_users[ $uid ] );
                        
                    }
                    
                    return NULL;
                    
                    break;
            }
            
        }
        
        // BEGIN CLASS PRIVATE METHODS
        
        // returns the local shadow file path of the management engine
        private function getLocalShadowPath() {
            return __DIR__ . '/../../../../etc/shadow/' . $this->_server->websiteName . '.' . ( php_sapi_name() == 'cli' ? 'cli' : 'www' ) . '.shadow';
        }
        
        // load the users and groups from local shadow copy
        // returns TRUE or FALSE
        private function loadFromShadow() {
            
            $shadow = $this->getLocalShadowPath();
            
            // test if file readable
            if ( !file_exists( $shadow ) || !is_readable( $shadow ) )
                return FALSE;
            
            $data = @file_get_contents( $shadow );
            
            // file is empty or could not be read?
            if ( empty( $data ) )
                return false;
            
            $info = @json_decode( $data, TRUE );
            
            // corrupted shadow file?
            if ( !is_array( $info ) )
                return FALSE;
            
            if ( !isset( $info[ 'users' ] ) || !isset( $info['groups'] ) || !isset( $info[ 'created' ] ) )
                return FALSE;
            
            if ( time() - $info[ 'created' ] > self::SHADOW_EXPIRE )
                return FALSE;
            
            $this->_users = $info[ 'users' ];
            $this->_groups= $info[ 'groups' ];
            
            return TRUE;
    
        }
        
        // loads the users and groups from mongo database, and
        // creates a local shadow copy on disk when success
        // returns TRUE or FALSE
        private function loadFromDatabase() {
            
            try {
            
                $result = $this->_shadow->find([], [
                    'password' => FALSE
                ]);
            
            
                if ( empty( $result ) )
                    return FALSE;
                
                foreach ( $result as $row ) {
                    
                    switch ( $row[ 'type' ] ) {
                        
                        case 'user':
                            $this->_users[ $row[ '_id' ] . '' ] = $row;
                            break;
                        
                        case 'group':
                            $this->_groups[ $row[ '_id' ] . '' ] = $row;
                            break;
                        
                    }
                    
                }
                
                // ok, we loaded the users and the groups. we now save the
                // data to local shadow file
                
                if ( !( @file_put_contents( $this->getLocalShadowPath(), json_encode( [
                    'users' => $this->_users,
                    'groups'=> $this->_groups,
                    'created' => time()
                ] ) ) ) ) throw Object( 'Exception.Security', 'Failed to store security management engine on local shadow' );
                
                return TRUE;
            
            } catch ( Exception $e ) {
                return FALSE;
            }
        }
        

        
        // BEGIN STATIC MANAGEMENT FUNCTIONS.
        // NOTE THAT THESE FUNCTIONS ARE WORKING ONLY ON root AND onedb ACCOUNTS.
        
        
        // retrieves the local onedb username password from the etc/onedb.shadow.gen.
        // that file should be created by the onedb installer, and it's content should
        // be randomly generated.
        static private function getOneDBPassword() {
            return @file_get_contents( __DIR__ . '/../../../../etc/onedb.shadow.gen' );
        }
        
        // adds a group to the system in mongo collection shadow.
        // some special groups have special meanings:
        // * root
        // * anonymous
        // * onedb
        static public function groupadd( $websiteName, $groupName ) {
            
            try {
                
                if ( !is_string( $websiteName ) || !strlen ( $websiteName ) )
                    throw Object( 'Exception.Security', 'bad website name. expected string not null' );
                
                if ( !is_string( $groupName ) || !strlen( $groupName ) )
                    throw Object( 'Exception.Security', 'bad group name. expected string not null' );
                
                if ( !preg_match( self::FORMAT, $groupName ) )
                    throw Object( 'Exception.Security', 'invalid group name. allowed characters are a-z, 0-9, and dot.' );
                
                $password = self::getOneDBPassword();
                
                echo "password: $password\n";
                
                $client = Object( 'OneDB' )->connect( $websiteName, 'onedb', $password );
                
                $shadow = $client->get_shadow_collection();
                
                $counter = $client->createCounter( 'shadow' )->getNext();
                
                $flags = 0;
                
                switch ( $groupName ) {
                    
                    case 'root':
                        $flags = Umask::AC_SUPERUSER;
                        break;
                    
                    case 'anonymous':
                        $flags = Umask::AC_NOBODY;
                        break;
                    
                    default:
                        $flags = Umask::AC_REGULAR;
                        break;
                }
                
                $data = [
                    '_id'     => $counter,
                    'type'    => 'group',
                    'name'    => $groupName,
                    'members' => [],
                    'flags'   => $flags,
                    'created' => time()
                ];
                
                try {
                
                    $shadow->save( $data, [
                        'fsync' => TRUE
                    ] );
                
                } catch ( Exception $e ) {
                    
                    if ( $e->getCode() == 11000 )
                        throw Object( 'Exception.Security', 'failed to create group ' . $groupName . '. another group with that name allready exists.' );
                    else
                        throw $e;
                    
                }
                
            } catch ( Exception $e ) {
                throw Object( 'Exception.Security', 'Failed to create group ' . $groupName, 20, $e );
            }
            
        }
        
        // Adds a user on onedb database in collection 'shadow'.
        // Some usernames have special meanings:
        // * root
        // * anonymous
        // * onedb
        static public function useradd( $websiteName, $uname, $password ) {
            
            try {
            
                if ( !is_string( $uname ) || !preg_match( self::FORMAT, $uname ) )
                    throw Object( 'Exception.Security', 'invalid username. please use only lowercase and the following characters in lowercase: a..z, dot, and 0..9' );
            
                if ( !is_string( $password ) || !strlen( $password ) )
                    throw Object( 'Exception.Security', 'invalid password format. expected a non-empty string' );
                
                if ( $uname == 'onedb' )
                    throw Object( 'Exception.Security', 'the onedb username is a system user and cannot be used!' );
                
                $flags = 0;
                
                switch ( $uname ) {
                
                    case 'root':
                        $umask = Umask::UR ^ Umask::UW ^ Umask::UX;
                        $flags = Umask::AC_SUPERUSER;
                    
                        break;
                    
                    case 'anonymous':
                        $umask = Umask::UR ^ Umask::UW ^ Umask::UX ^ Umask::GR ^ Umask::GW ^ Umask::GX ^ Umask::AR ^ Umask::AW ^ Umask::AX;
                        $flags = Umask::AC_NOBODY;
                    
                        break;
                
                    default:
                        $umask = Umask::UR ^ Umask::UW ^ Umask::UX ^ Umask::GR;
                        $flags = Umask::AC_REGULAR;
                        
                        break;
                }
            
                $server = OneDB::connect( $websiteName, 'onedb', self::getOneDBPassword() );
                
                $conn   = $server->get_shadow_collection();
                
                $uid = $server->createCounter( 'shadow' )->getNext();
                
                try {
                
                    $conn->save( [
                        "_id"       => $uid,
                        "type"      => "user",
                        "name"      => $uname,
                        "members"   => [],
                        "umask"     => $umask,
                        "flags"     => $flags,
                        "password"  => md5( $password ),
                        "created"   => time()
                    ], [
                        "fsync" => TRUE
                    ]);
                
                } catch ( Exception $e ) {
                    
                    if ( $e->getCode() == 11000 )
                        throw Object( 'Exception.Security', 'failed to create user ' . $uname . ': user allready exists!' );
                    else
                        throw $e;
                    
                }
            
            } catch ( Exception $e ) {
                
                throw Object( 'Exception.Security', 'failed to create user ' . $uname . ' on website ' . $websiteName, 12, $e );
                
            }
        }

        
    }

?>