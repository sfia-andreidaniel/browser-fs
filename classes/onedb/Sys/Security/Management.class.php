<?php

    require_once __DIR__ . '/../Umask.class.php';

    // The role of this class is to retrieve all the users and all the
    // groups from the mongo database or from a local shadow file
    
    class Sys_Security_Management extends Object {
        
        const SHADOW_EXPIRE = 3; // After 30 seconds do a shadow resync with the database
        const FORMAT        = '/[a-z\d]+((\.[a-z\d]+)+)?$/'; // username or groupname format
        
        const UID_ONEDB     = 0;
        const UID_ROOT      = 1;
        const UID_ANONYMOUS = 2;
        
        const GID_ROOT      = 3;
        const GID_ANONYMOUS = 4;
        
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
                    
                    $groupIdOrGroupName .= '';
                    
                    // echo "get group int $groupIdOrGroupName\n";
                    
                    if ( !isset( $this->_groups[ $groupIdOrGroupName ] ) )
                        return NULL;
                    
                    return Object( 'Sys.Security.Group', $this->_server, $this->_groups[ $groupIdOrGroupName ] );
                    
                    break;
                
                case is_string( $groupIdOrGroupName ):
                    
                    // echo "get group string $groupIdOrGroupName\n";
                    
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
                    
                    $userIdOrUserName .= '';
                    
                    if ( !isset( $this->_users[ $userIdOrUserName ] ) )
                        return NULL;
                    
                    return Object( 'Sys.Security.User.Unauthenticated', $this->_server )->setProperties( $this->_users[ $userIdOrUserName ] );
                    
                    break;
                
                case is_string( $userIdOrUserName ):
                    
                    foreach ( array_keys( $this->_users ) as $uid ) {
                        
                        if ( $this->_users[ $uid ][ 'name' ] == $userIdOrUserName )
                            return Object( 'Sys.Security.User.Unauthenticated', $this->_server )->setProperties( $this->_users[ $uid . '' ] );
                        
                    }
                    
                    return NULL;
                    
                    break;
            }
            
        }
        
        /* Returns all the members from an entity.
           @param entityType:                <string> enum ('user', 'group')
           @param entityId:                  <int> gte 0
           @param returnObjectsInsteadOfIds: <boolean>
         */
        public function getMembers( $entityType, $entityId, $returnObjectsInsteadOfIds = FALSE ) {
            
            try {
                
                if ( $entityId === NULL )
                    return [];
                
                if ( !is_string( $entityType ) || !in_array( $entityType, [ 'user', 'group' ] ) )
                    throw Object( 'Exception.Security', 'getmembers: @param entityType must be string enum ("user", "group")');
                
                if ( !is_int( $entityId ) || $entityId <= 0 )
                    throw Object( 'Exception.Security', 'getmembers: @param entityId must be int gte > 0' );
                
                if ( isset( $this->{ $ikey = '_' . $entityType . 's' }[ $entityId . '' ] ) ) {
                    
                    $out = $this->{ $ikey }[ $entityId . '' ][ 'members' ];
                    
                    if ( $returnObjectsInsteadOfIds ) {
                        
                        for ( $i=0, $len = count( $out ); $i<$len; $i++ ) {
                            
                            //echo $out[ $i ];
                            
                            $out[ $i ] = $this->{ $entityType == 'user' ? 'group' : 'user' }( $out[$i] );
                            
                        }
                        
                    }
                    
                    return array_values( array_filter( $out, function( $item ) { return $item !== NULL; } ) );
                    
                } else return [];
                
            } catch ( Exception $e ) {
                
                throw Object( 'Exception.Security', 'failed to get members' );
                
            }
            
        }
        
        public function canRead( $uid, $gid, $mode, $user ) {
            
            $userID   = $user->id;

            $userGIDS = $this->getMembers( 'user', $userID );

            $flags    = $user->flags;
            
            // is superuser?
            $super = $userID == self::UID_ONEDB || $userID == self::UID_ROOT || ( $flags & Umask::AC_SUPERUSER ) || in_array( self::GID_ROOT, $userGIDS );
            
            // superuser accounts can read everything
            if ( $super ) return TRUE;
            
            // is anonymous?
            $anon  = $userID == self::UID_ANONYMOUS || ( $flags & Umask::AC_NOBODY ) || in_array( self::GID_ANONYMOUS, $userGIDS );
            
            // anonymous accounts can read only what they created
            if ( $anon ) return ( $uid == $userID && ( $mode & Umask::UR ) ) ? TRUE : FALSE;
            
            // compute who wrote the file: owner, group, or others
            $who = ( $uid == $userID )
                ? 1 // owner
                : (
                    in_array( $gid, $userGIDS )
                        ? 2 // group
                        : 3 // others
                );
            
            switch ( $who ) {
                
                case 1: // file has been created by owner
                    return ( $mode & Umask::UR ) ? TRUE : FALSE;
                    break;
                
                case 2: // file has been created by a person from the group of the owner
                    return ( $mode & Umask::GR ) ? TRUE : FALSE;
                    break;
                
                default: // file has been created by someone else
                    return ( $mode & Umask::AR ) ? TRUE : FALSE;
                    break;
            }
        }
        
        public function canWrite( $uid, $gid, $mode, $user ) {
            $userID   = $user->id;
            $userGIDS = $this->getMembers( 'user', $userID );
            $flags    = $user->flags;
            
            // is superuser?
            $super = $userID == self::UID_ONEDB || $userID == self::UID_ROOT || ( $flags & Umask::AC_SUPERUSER ) || in_array( self::GID_ROOT, $userGIDS );
            
            // superuser accounts can write allover the places
            if ( $super ) return TRUE;
            
            // is anonymous?
            $anon  = $userID == self::UID_ANONYMOUS || ( $flags & Umask::AC_NOBODY ) || in_array( self::GID_ANONYMOUS, $userGIDS );
            
            // anonymous accounts can write only what they created
            if ( $anon ) return ( $uid == $userID && ( $mode & Umask::UW ) ) ? TRUE : FALSE;
            
            // compute who wrote the file: owner, group, or others
            $who = ( $uid == $userID )
                ? 1 // owner
                : (
                    in_array( $gid, $userGIDS )
                        ? 2 // group
                        : 3 // others
                );
            
            switch ( $who ) {
                
                case 1: // file has been created by owner
                    return ( $mode & Umask::UW ) ? TRUE : FALSE;
                    break;
                
                case 2: // file has been created by a person from the group of the owner
                    return ( $mode & Umask::GW ) ? TRUE : FALSE;
                    break;
                
                default: // file has been created by someone else
                    return ( $mode & Umask::AW ) ? TRUE : FALSE;
                    break;
            }
        }
        
        public function canExecute( $uid, $gid, $mode, $user ) {
            $userID   = $user->id;
            $userGIDS = $this->getMembers( 'user', $userID );
            $flags    = $user->flags;
            
            // is superuser?
            $super = $userID == self::UID_ONEDB || $userID == self::UID_ROOT || ( $flags & Umask::AC_SUPERUSER ) || in_array( self::GID_ROOT, $userGIDS );
            
            // superuser accounts can execute EVERYTHING
            if ( $super ) return TRUE;
            
            // is anonymous?
            $anon  = $userID == self::UID_ANONYMOUS || ( $flags & Umask::AC_NOBODY ) || in_array( self::GID_ANONYMOUS, $userGIDS );
            
            // anonymous accounts can NEVER execute something
            if ( $anon ) return ( $uid == $userID && ( $mode & Umask::UW ) ) ? TRUE : FALSE;
            
            // compute who wrote the file: owner, group, or others
            $who = ( $uid == $userID )
                ? 1 // owner
                : (
                    in_array( $gid, $userGIDS )
                        ? 2 // group
                        : 3 // others
                );
            
            switch ( $who ) {
                
                case 1: // file has been created by owner
                    return ( $mode & Umask::UX ) ? TRUE : FALSE;
                    break;
                
                case 2: // file has been created by a person from the group of the owner
                    return ( $mode & Umask::GX ) ? TRUE : FALSE;
                    break;
                
                default: // file has been created by someone else
                    return ( $mode & Umask::AX ) ? TRUE : FALSE;
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
        
        
        public function __mux() {
            return [
                "root"  => $this->_server->__mux(),
                "users" => $this->_users,
                "groups"=> $this->_groups
            ];
        }
        
        // BEGIN STATIC MANAGEMENT FUNCTIONS.
        // NOTE THAT THESE FUNCTIONS ARE WORKING ONLY ON root AND onedb ACCOUNTS.
        
        
        // retrieves the local onedb username password from the etc/onedb.shadow.gen.
        // that file should be created by the onedb installer, and it's content should
        // be randomly generated.
        static protected function getOneDBPassword() {
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

        /* Removes a group from OneDB database
         */
        public static function groupdel( $websiteName, $groupName ) {
            
            try {
                
                if ( !is_string( $websiteName ) || !strlen( $websiteName ) )
                    throw Object( 'Exception.Security', 'invalid website name' );
                
                if ( !is_string( $groupName ) || !strlen( $groupName ) )
                    throw Object( 'Exception.Security', 'invalid group name' );
                
                if ( in_array( $userName, [ 'root', 'anonymous', 'onedb' ] ) )
                    throw Object( 'Exception.Security', 'this is a built-in group and cannot be deleted' );

                $server = OneDB::connect( $websiteName, 'onedb', static::getOneDBPassword() );
                
                $group = $server->sys->group( $groupName );
                
                if ( $group === NULL )
                    throw Object( 'Exception.Security', "Group " . $groupName . " not found!");
                
                if ( in_array( $group->name, [ 'anonymous', 'root' ] ) )
                    throw Object( 'Exception.Security', 'this is a built-in user account and cannot be deleted' );
                
                $conn = $server->get_shadow_collection();
                
                // delete group from shadow
                $conn->remove( [
                    '_id' => $group->id
                ] );
                
                // delete group from ".members" property of the users
                $conn->update( [
                    'type' => 'user'
                ], [
                    '$pull' => [
                        'members' => $group->id
                    ]
                ] );
                
            } catch ( Exception $e ) {
                throw Object( 'Exception.Security', 'failed to delete group ' . $groupName . ' on website ' . $websiteName, 20, $e );
            }
        }

        /* Removes an user from OneDB database
         */
        public static function userdel( $websiteName, $userName ) {
            
            try {
                
                if ( !is_string( $websiteName ) || !strlen( $websiteName ) )
                    throw Object( 'Exception.Security', 'invalid website name' );
                
                if ( !is_string( $userName ) || !strlen( $userName ) )
                    throw Object( 'Exception.Security', 'invalid user name' );
                
                if ( in_array( $userName, [ 'root', 'anonymous', 'onedb' ] ) )
                    throw Object( 'Exception.Security', 'this is a built-in user and cannot be deleted' );
                
                $server = OneDB::connect( $websiteName, 'onedb', static::getOneDBPassword() );
                
                $user = $server->sys->user( $userName );
                
                if ( $user === NULL )
                    throw Object( 'Exception.Security', "User " . $userName . " not found!");
                
                if ( in_array( $user->name, [ 'anonymous', 'root' ] ) )
                    throw Object( 'Exception.Security', 'this is a built-in user account and cannot be deleted' );
                
                $conn = $server->get_shadow_collection();
                
                // delete group from shadow
                $conn->remove( [
                    '_id' => $user->id
                ] );
                
                // delete user from ".members" property of the groups
                $conn->update( [
                    'type' => 'group'
                ], [
                    '$pull' => [
                        'members' => $user->id
                    ]
                ] );
                
            } catch ( Exception $e ) {
                throw Object( 'Exception.Security', 'failed to delete user ' . $userName . ' on website ' . $websiteName, 21, $e );
            }
        }
        
        /* Modify a user using the settings provided in @param <array> $settings
         *
         * All fields from $settings are OPTIONAL
         * @param settings = {
         *      "password": "new password",   // set the password of the user to "new password". blank passwords not allowed!
         *      "groups": [
         *              "+group1",            // make the user a member of group1
         *              "-group2",            // remove membership from group2 of the user
         *              "+group3",            // make the user a member of group3
         *              "-group4",            // remove membership from group4 of the user
         *              ":group5"             // make the user a member of group5, and set this group the default group
         *      ],
         *      "umask":    <umask_value>     // set the user umask to umask_value. umask_value should be of type integer.
         *      "flags":    <account_flag>    // set the user account flag to <account_flag>.
         *                                    // account_flag is a bitmap of flags: 
         *                                    // Umask::AC_NOBODY ( 512 ), Umask::AC_SUPERUSER( 256 ), Umask::AC_REGULAR( 128 )
         *
         * }
         */
        public static function usermod( $websiteName, $userName, $settings ) {
            
            try {
                
                if ( !is_string( $websiteName ) || !strlen( $websiteName ) )
                    throw Object( 'Exception.Security', 'invalid website name' );
                
                if ( !is_string( $userName ) || !strlen( $userName ) )
                    throw Object( 'Exception.Security', 'invalid user name' );
                
                if ( !is_array( $settings ) )
                    throw Object( 'Exception.Security', 'invalid $settings argument. expected array' );
                
                if ( $userName == 'onedb' )
                    throw Object( 'Exception.Security', 'this is a built-in user and cannot be deleted' );
                
                $server = OneDB::connect( $websiteName, 'onedb', static::getOneDBPassword() );
                
                $user = $server->sys->user( $userName );
                
                if ( $user === NULL )
                    throw Object( 'Exception.Security', "User " . $userName . " not found!");
                
                $set = [];
                
                $groupsBatch = [];
                
                foreach ( array_keys( $settings ) as $setting ) {
                    
                    switch ( $setting ) {
                        
                        case 'password':
                            if ( !is_string( $settings[ 'password' ] ) || !strlen( $settings['password']  ) )
                                throw Object( 'Exception.Security', 'password must be a non-empty "string" type' );
                            
                            $set[ 'password' ] = md5( $settings[ 'password' ] );
                            
                            break;
                        
                        case 'umask':
                            
                            if ( !is_int( $settings[ 'umask' ] ) || $settings[ 'umask' ] < 0 )
                                throw Object( 'Exceptoin.Security', 'umask must be a non-negative "integer" type' );
                            
                            $set[ 'umask' ] = $settings[ 'umask' ];
                            
                            break;
                        
                        case 'groups':
                            
                            if ( !is_array( $settings[ 'groups'] ) )
                                throw Object( 'Exception.Security', 'bad value for settings.groups: expected array!' );
                            
                            // fetch existing groups ...
                            $existingGroups = $server->sys->getMembers( 'user', $user->id );
                            
                            $setDefaultGroup = FALSE;
                            
                            if ( !is_array( $existingGroups ) )
                                throw Object( 'Exception.Security', 'internal error, value was expected to be an array!' );
                            
                            foreach ( $settings[ 'groups' ] as $group ) {
                                
                                // group should be in format:
                                // (+?|-)<group_name_or_group_id>
                                
                                if ( !preg_match( '/^([\+\-\:])?(.*)$/', $group, $matches ) )
                                    throw Object( 'Exception.Security', 'bad group operation: ' . $group . ' encountered in settings.groups' );
                                
                                $op = $matches[1] == '-'
                                    ? 'remove'
                                    : 'add';
                                
                                if ( $matches[1] == ':' ) $putFirst = TRUE;
                                else $putFirst = FALSE;
                                
                                $what = $matches[2];
                                
                                if ( preg_match( '/^[\d]+$/', $what ) )
                                    $what = ~~$what;
                                
                                $grp = $server->sys->group( $what );
                                
                                if ( $grp === NULL )
                                    throw Object( 'Exception.Security', 'group ' . $what . ' was not found' );
                                
                                $id = $grp->id;
                                
                                if ( $putFirst )
                                    $setDefaultGroup = $id;
                                
                                switch ( $op ) {
                                    
                                    case 'add':
                                        if ( array_search( $id, $existingGroups ) === FALSE )
                                            $existingGroups[] = $id;

                                        $groupsBatch[] = [
                                            '_id'    => $id,
                                            'op'     => 'add',
                                            'member' => $user->id
                                        ];

                                        break;
                                    
                                    case 'remove':
                                        if ( $key = array_search( $id, $existingGroups ) !== FALSE )
                                            unset( $existingGroups[ $key ] );
                                        
                                        $groupsBatch[] = [
                                            '_id' => $id,
                                            'op'  => 'remove',
                                            'member' => $user->id
                                        ];
                                        
                                        break;
                                    
                                }
                            }
                            
                            if ( $setDefaultGroup !== FALSE ) {
                                usort( $existingGroups, function( $a, $b ) use ( $setDefaultGroup ) {
                                    return $a == $setDefaultGroup ? -1 : 1;
                                } );
                            }
                            
                            $set[ 'members' ] = array_values( $existingGroups );
                            
                            break;
                        
                        case 'flags':
                            
                            if ( !is_int( $settings['flags'] ) || $settings['flags'] < 0 )
                                throw Object( 'Exception.Security', 'user flags is of type integer non-negative' );
                            
                            $set[ 'flags' ] = $settings[ 'flags' ];
                            
                            break;
                        
                        default:
                            throw Object( 'Exception.Security', 'bad key "' . $setting . '" in settings argument' );
                            break;
                    }
                    
                }
                
                // Do update in database ...
                
                $conn = $server->get_shadow_collection();
                
                // update the user...
                $conn->update( [
                    '_id' => $user->id,
                    'type' => 'user'
                ], [
                    '$set' => $set
                ] );
                
                // update the groups...
                if ( count( $groupsBatch ) ) {
                    
                    // _id, op = add, remove, member = 
                    
                    foreach ( $groupsBatch as $batch ) {
                        
                        // fetch the group ...
                        $grp = $conn->findOne( [
                            'type' => 'group',
                            '_id'  => $batch[ '_id' ]
                        ] );
                        
                        if ( !$grp ) {
                            
                            // DO NOT THROW ERROR, BETTER I THINK
                            // throw Object( 'Exception.Security', 'failed to post-modify group #' . $batch['_id'] . ': group not found!' );
                            
                        } else {
                            // do the op
                            switch ( $batch['op'] ) {
                                
                                case 'add':
                                    $grp[ 'members' ][] = $batch[ '_id' ];
                                    break;
                                
                                case 'remove':
                                    if ( ( $key = array_search( $batch[ '_id'], $grp['members'] ) ) !== FALSE )
                                        unset( $grp['members'][ $key ] );
                                    break;
                                
                            }
                            
                            $grp[ 'members' ] = array_values( array_unique( $grp[ 'members' ] ) );
                            
                            // update group
                            $conn->save( $grp );
                        }
                    }
                    
                }
                
                //print_r( $set );
                
            } catch ( Exception $e ) {
                throw Object( 'Exception.Security', 'failed to modify user ' . $userName . ' on website ' . $websiteName, 22, $e );
            }
        }
        
        /* Modify a group using the settings provided in @param <array> $settings
         *
         * All fields from $settings are OPTIONAL
         * @param settings = {
         *      "users": [
         *              "+user1",            // make the user1 a member of this group
         *              "-user2"             // remove membership of user 2 from this group
         *              ":user3"             // make the user3 a member of this group and set this group as it's default group
         *      ],
         *      "flags": <group_flags>        // set the group flags to <group_flags>.
         *                                    // group_flags is a bitmap of flags:
         *                                    // Umask::AC_NOBODY ( 512 ), Umask::AC_SUPERUSER( 256 ), Umask::AC_REGULAR( 128 )
         * }
         */
        public static function groupmod( $websiteName, $groupName, $settings ) {
            
            try {
                
                if ( !is_string( $websiteName ) || !strlen( $websiteName ) )
                    throw Object( 'Exception.Security', 'invalid website name' );
                
                if ( !is_string( $groupName ) || !strlen( $groupName ) )
                    throw Object( 'Exception.Security', 'invalid group name' );
                
                if ( !is_array( $settings ) )
                    throw Object( 'Exception.Security', 'invalid $settings argument. expected array' );
                
                if ( $groupName == 'onedb' )
                    throw Object( 'Exception.Security', 'this is a built-in group and cannot be deleted' );
                
                $server = OneDB::connect( $websiteName, 'onedb', static::getOneDBPassword() );
                
                $group = $server->sys->group( $groupName );
                
                $set   = [];
                $batch = [];
                
                foreach ( array_keys( $settings ) as $setting ) {
                    
                    switch ( $setting ) {
                        
                        case 'flags':
                            
                            if ( !is_int( $settings[ 'flags' ] ) || $settings[ 'flags'] < 0 )
                                throw Object( 'Exception.Security', 'the group flags should be of type non-negative integer' );
                            
                            $set[ 'flags' ] = $settings[ 'flags' ];
                            
                            break;
                        
                        case 'users':
                            // the users setting is modified after mongo db update for the group
                            
                            if ( !is_array( $settings[ 'users' ] ) )
                                throw Object( 'Exception.Security', 'invalid group settings.users argument: expected array!' );
                            
                            // fetch existing groups ...
                            $existingUsers = $server->sys->getMembers( 'group', $group->id );
                            
                            if ( !is_array( $existingUsers ) )
                                throw Object( 'Exception.Security', 'internal error, expected $existingUsers to be an array!' );
                            
                            foreach ( $settings[ 'users' ] as $user ) {
                                
                                if ( !preg_match( '/^([\+\-\:])?(.*)$/', $user, $matches ) )
                                    throw Object( 'Exception.Security', 'bad user operation: ' . $user . ' encountered in settings.groups' );
                                
                                $op = $matches[1] == '-'
                                    ? 'remove'
                                    : 'add';
                                
                                if ( $matches[1] == ':' ) $putFirst = TRUE;
                                else $putFirst = FALSE;
                                
                                $what = $matches[2];
                                
                                if ( preg_match( '/^[\d]+$/', $what ) )
                                    $what = ~~$what;
                                
                                $usr = $server->sys->user( $what );
                                
                                if ( $usr === NULL )
                                    throw Object( 'Exception.Security', 'user ' . $what . ' was not found!' );
                                
                                $id = $usr->id;
                                
                                switch ( $op ) {
                                    
                                    case 'add':
                                        
                                        if ( array_search( $id, $existingUsers ) === FALSE )
                                            $existingUsers[] = $id;
                                        
                                        $batch[] = [
                                            '_id' => $id,
                                            'op'  => 'add' . ( $putFirst ? 'First' : '' ),
                                            'member' => $group->id
                                        ];
                                        
                                        break;
                                    
                                    case 'remove':
                                        
                                        if ( ( $key = array_search( $id, $existingUsers ) ) !== FALSE ) {
                                            unset( $existingUsers[ $key ] );
                                        }
                                        
                                        $batch[] = [
                                            '_id' => $id,
                                            'op' => 'remove',
                                            'member' => $group->id
                                        ];
                                        
                                        break;
                                    
                                }
                                
                            }
                            
                            $set[ 'members' ] = array_values( array_unique( $existingUsers) );
                            
                            break;
                        
                        default:
                            
                            throw Object( 'Exception.Security', 'invalid group setting: ' . $setting );
                            
                            break;
                    }
                    
                }
            
                // Do update in database ...
                
                $conn = $server->get_shadow_collection();
                
                // update the group...
                $conn->update( [
                    '_id' => $group->id,
                    'type' => 'group'
                ], [
                    '$set' => $set
                ] );
                
                // commit batch ...
                
                foreach ( $batch as $op ) {
                    
                    // fetch user ...
                    $user = $conn->findOne( [
                        '_id' => $op['_id'],
                        'type' => 'user'
                    ] );
                    
                    if ( $user === NULL ) {
                        // DO NOT THROW EXCEPTION IF USER IS NOT FOUND I THINK!
                        // throw Object( 'Exception.Security', 'post-update batch error: user #' . $op['_id'] . ' was not found!' );
                    } else {
                        
                        $members = $user[ 'members' ];
                        
                        switch ( $op[ 'op' ] ) {
                            
                            case 'remove':
                                // remove group from user
                                if ( ( $key = array_search( $op['member'], $members ) ) !== FALSE ) {
                                    unset( $members[ $key ] );
                                    $members = array_values( $members );
                                }
                                break;
                            
                            case 'add':
                                if ( $key = array_search( $op['member'], $members ) === FALSE )
                                    $members[] = $op[ 'member' ];
                                break;
                            
                            case 'addFirst':
                                
                                if ( ( $key = array_search( $op[ 'member' ], $members ) ) !== FALSE ) {
                                    unset( $members[ $key ] );
                                    $members = array_values( $members );
                                }
                                
                                array_unshift( $members, $op['member'] );
                                
                                break;
                        }
                        
                        $user[ 'members' ] = $members;
                        
                        // save back user
                        $conn->save( $user );
                        
                    }
                }
                
            } catch ( Exception $e ) {
                
                throw Object( 'Exception.Security', 'failed to modify group settings', 23, $e );
                
            }
        }
        
    }

?>