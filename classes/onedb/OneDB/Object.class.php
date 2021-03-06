<?php

    require_once __DIR__ . '/Client.class.php';

    // In v2, all the objects are stored in a single collection
    // called objects.

    class OneDB_Object extends Object implements IDemuxable {
        
        // OBJECT FLAGS (needed for .getObjectFlags() method packer and RPC)
        const F_NOFLAG     =    0; // no flags
        const F_READONLY   =    2; // if the object is not modifiable ( is read only )
        const F_CONTAINER  =    4; // if the object can holds another objects ( is a folder )
        const F_UNLINKED   =    8; // If the object has been unlinked ...
        const F_ROOT       =   16; // Object is root if is instanceof OneDB_Object_Root
        const F_UNSTABLE   =   32; // Object is in an unstable state if it was created and not saved.
        const F_LIVE       =   64; // Live object. If this object is child of a Webservice category usual
        const F_FLUSH      =  128; // RPC client-side flag only - Save object after set a property
        const F_READABLE   =  256; // weather the object is readable or not
        const F_WRITABLE   =  512; // weather the object is writable or not
        const F_EXECUTABLE = 1024; // weather the object can be executed or not
        
        // object creation modes. referring to object name.
        const F_CREATE_NORMAL             = 0; // do not check if another object exists in db in the parent with the same name
        const F_CREATE_NOCONFLICT         = 2; // check if another object exists in db in the parent with the same name
        const F_CREATE_PRESERVE_EXTENSION = 4; // check if another object exists in db in the parent with the same name + preserve object extension in the name
        
        protected $_server      = NULL; // link to OneDB_Client
        
        // properties that are mixed inside object
        protected $_id          = NULL; // id of the object in mongo database
        protected $_name        = NULL; // name of the object in mongo database

        protected $_ctime       = NULL; // created time
        protected $_mtime       = NULL; // modification time

        protected $_uid         = NULL; // owner user uid
        protected $_gid         = NULL; // owner group gid
        protected $_muid        = NULL; // last owner uid that modified this object
        protected $_mode        = NULL; // object filesystem mode ( related to chmod )
        
        protected $_views       = NULL; // link to object views ( for category, article )
        
        protected $_description = NULL; // description of this object
        protected $_icon        = NULL; // icon of this object if any
        protected $_keywords    = [];   // keywords of this object
        protected $_tags        = [];   // tags of this object
        
        protected $_online      = FALSE;// is object online? (related to website content)
        
        protected $_parent      = NULL; // parent of the object. when not null is of type OneDB_Object
        
        // VERY internal flags of the object
        protected $_autoCommit     = TRUE; // weather to automatically save the object on destructor
        protected $_changed        = FALSE;// weather the object has been changed
        protected $_type           = NULL; // the type that implements this object. when not null it should be <OneDB_Type_*>
        protected $_unlinked       = FALSE;// is this object unlinked?
        protected $_viewsSingleton = NULL; // a singleton to object views.

        // Weather or not this object is a container.
        // If the object is a container, it can hold childrens inside.
        protected static $_isContainer = FALSE;
        
        // Weather or not the object is readOnly.
        protected static $_isReadOnly  = FALSE;
        
        // The native properties of a OneDB_Object.
        // An Object Type implementation should NEVER implement these
        // properties.
        public static $_nativeProperties = [
            '_id', '_parent', '_type', 'name', 'ctime', 'mtime',
            'gid', 'uid', 'muid', 'mode', 'description', 'icon',
            'keywords', 'tags', 'online', 'views'
        ];
        
        // a singleton of the muxer, to do fast object muxing
        public static $_muxer_ = NULL;
        // a singleton of a <Utils.Parsers.Path> object do do fast path conversion
        public static $_path_ = NULL;
        
        /* Initializes the object. This is the constructor of the object 
         */
        public function init( OneDB_Client $client, $objectId = NULL, $loadFromProperties = NULL ) {
            $this->_server = $client;
            $this->_id = $objectId;
            
            if ( $this->_id !== NULL )
                $this->load( $loadFromProperties );
            else
                $this->_parent = Object( 'OneDB.Object.Root', $this->_server, NULL );
        }
        
        /* When the object destructs, it attempts to save the object
           if the object was modified
         */
        public function __destruct() {
            
            if ( $this->_autoCommit && $this->_changed ) {
                $this->save();
            }
            
        }
        
        /* Returns true if the object can hold other objects ( is a dir )
         */
        public function isContainer() {
            if ( $this->_type !== NULL )
                return $this->_type->isContainer();
            else
                return static::$_isContainer;
        }
        
        /* Returns true if the object, as it's nature implementation is a
           readonly object.
           
           This is not having anything to do with the isWritable()
         */
        public function isReadOnly() {
            if ( $this->_type !== NULL )
                return $this->_type->isReadOnly();
            else
                return static::$_isReadOnly;
        }
        
        /* Returns true, if the current user can read the object.
         */
        public function isReadable() {
            return $this->_server->sys->canRead( $this->uid, $this->gid, $this->mode, $this->_server->user );
        }
        
        /* Returns true if the current user can write the object.
           This is not having anything to do with the isReadOnly() method
         */
        public function isWritable() {
            return $this->_server->sys->canWrite( $this->uid, $this->gid, $this->mode, $this->_server->user );
        }
        
        /* Returns true if the current user can execute the object.
           
           Some objects implement code execution via server side, like
           widgets.
         */
        public function isExecutable() {
            return $this->_server->sys->canExecute( $this->uid, $this->gid, $this->mode, $this->_server->user );
        }

        /* Saves the object in database.
         * 
         * Returns: nothing. On error, exception is thrown
         */
        public function save( ) {
            
            if ( $this->_unlinked )
                return;
            
            // update fields with default values if they are not allready setup.
            // typically this happens to new objects not saved before
            if ( $this->_uid   === NULL ) $this->_uid = $this->_server->user->uid;
            if ( $this->_gid   === NULL ) $this->_gid = $this->_server->user->gid;
            if ( $this->_muid  === NULL ) $this->_muid = $this->_server->user->uid;
            if ( $this->_mode  === NULL ) $this->_mode = $this->_server->user->umask;
            if ( $this->_ctime === NULL ) $this->_ctime = time();
            
            // update modification time to current time
            $this->_mtime = time();
            
            // objects without a name cannot be saved in database
            if ( $this->_name === NULL )
                throw Object( 'Exception.OneDB', "Cannot save object, because it don't have a name set!" );
            
            // the structure (properties) of the object that will be saved in database
            $props = [];
            
            // if the object allready have an ID set, we place it here in the $props
            // to do an update instead of insert in db.
            if ( $this->_id !== NULL ) $props[ '_id' ] = $this->_id;

            // the type of the object is saved in database as string
            $props[ '_type' ] = $this->_type === NULL 
                ? NULL
                : $this->_type->name;
            
            // save parent
            $props[ '_parent' ] = $this->_parent->_id;

            // set-up user id, group id, modify user id, object mode, created time,
            // modification time
            $props[ 'uid'   ] = $this->_uid;
            $props[ 'gid'   ] = $this->_gid;
            $props[ 'muid'  ] = $this->_muid;
            $props[ 'mode'  ] = $this->_mode;
            $props[ 'ctime' ] = $this->_ctime;
            $props[ 'mtime' ] = $this->_mtime;
            
            $props[ 'views' ] = $this->_views;
            
            // set-up other object properties.
            $props[ 'name'        ] = $this->_name;
            $props[ 'description' ] = $this->_description;
            $props[ 'icon'        ] = $this->_icon;
            $props[ 'keywords'    ] = $this->_keywords;
            $props[ 'tags'        ] = $this->_tags;
            $props[ 'online'      ] = $this->_online;
            $props[ 'url'         ] = $this->url;
            
            // if the object implements an object type, call it's type hook
            // to populate the properties
            if ( $this->_type !== NULL ) $this->_type->exportOwnProperties( $props );
            
            // Test if the object is writable. If not, abort saving...
            if ( !$this->isWritable() )
                throw Object( 'Exception.IO', 'Not enough permissions to save object ' . $this->url );
            
            // try to do a save in the database.
            try {
                
                $this->_server->objects->save(
                    $props,
                    [ 'fsync' => TRUE ]
                );
                
                // if the object didn't had an associated _id, we associate it
                // with an id returned by the mongo db server.
                if ( $this->_id === NULL ) $this->_id = $props[ '_id' ];
                
                // set the changed flag of the object to FALSE
                $this->_changed = FALSE;
                
            } catch ( Exception $e ) {
                
                // catch exception. if mongo duplicate exception found, we modify the
                // exception message to a more user friendly
                $errorMessage = "Failed to save object" . ( $this->_id ? "( _id = $this->_id )" : "" ) . ": " . $e->getMessage();
                
                if ( $e instanceof MongoCursorException ) {
                    
                    switch ( $e->getCode() ) {
                        case 11000:
                            $errorMessage = "Another item allready exists with that name!";
                            break;
                    }
                    
                }
                
                // throw exception further
                throw Object( 'Exception.OneDB', $errorMessage, 0, $e );
                
            }
        }
        
        /* Returns an array with all object fields */
        public function toObject() {
            
            // unlinked objects cannot be objectified
            if ( $this->_unlinked ) return NULL;
            
            $out = [];
            
            // id of the object is returned as string
            $out[ '_id' ] = $this->_id === NULL ? NULL : $this->_id . '';

            //$out[ '_container' ]  = $this->isContainer();
            
            // id of the parent is returned as string
            if ( $this->_parent       ) $out['_parent'] = $this->_parent->_id . '';

            // type of object is returned as string
            if ( $this->_type != NULL ) $out['_type'] = $this->_type->name;

            $out[ 'name' ]        = $this->_name;

            $out[ 'uid'   ] = $this->_uid;
            $out[ 'gid'   ] = $this->_gid;
            $out[ 'muid'  ] = $this->_muid;
            $out[ 'ctime' ] = $this->_ctime;
            $out[ 'mtime' ] = $this->_mtime;
            $out[ 'mode'  ] = $this->_mode;

            $out[ 'views' ] = $this->_views;
            
            $out[ 'description' ] = $this->_description;
            $out[ 'icon'     ] = $this->_icon;
            $out[ 'keywords' ] = $this->_keywords;
            $out[ 'tags'     ] = $this->_tags;
            $out[ 'online'   ] = $this->_online;
            $out[ 'url'      ] = $this->url;
            
            // if the object implements a type, call it's type export hook
            if ( $this->_type !== NULL ) $this->_type->exportOwnProperties( $out );
            
            return $out;
        }
        
        // callback that is called by the object type implementation typically
        public function _change( $propertyName, $propertyValue ) {
            
            switch ( $propertyName ) {
                case '_views_':
                    $this->_views = $propertyValue;
                    break;
            }
            
            $this->_changed = TRUE;
        }
        
        /* Loads the object from database.

           If parameter $fromData is present and is of type array, instead of loading the object
           from the database, the information from the $fromData will be used
           
         */
        public function load( $fromData = NULL ) {
            
            if ( $fromData === NULL || !is_array( $fromData ) ) {
                
                if ( $this->_id === NULL ) throw Object( 'Exception.OneDB', "Failed to load object, no _id was specified!" );
                
                $fromData = $this->_server->objects->findOne( [ '_id' => $this->_id ] );
                
                if ( $fromData === NULL ) throw Object( 'Exception.OneDB', "Failed to load object, the object does not exists!" );
            }
            
            if ( !isset( $fromData[ '_id' ] ) )
                throw Object( 'Exception.OneDB', "The loaded object doesn't contain an _id" );
            
            // populate fields of the object
            $this->_id    = $fromData[ '_id' ];
            $this->_name  = urldecode( $fromData[ 'name' ] );
            $this->_ctime = $fromData[ 'ctime' ];
            $this->_mtime = $fromData[ 'mtime' ];
            $this->_uid   = $fromData[ 'uid' ];
            $this->_gid   = $fromData[ 'gid' ];
            $this->_muid  = $fromData[ 'muid' ];
            $this->_mode  = $fromData[ 'mode' ];
            
            $this->_views = $fromData[ 'views' ];

            $this->_description = $fromData[ 'description' ];
            $this->_icon     = $fromData[ 'icon' ];
            $this->_keywords = $fromData[ 'keywords' ];
            $this->_tags     = $fromData[ 'tags' ];
            $this->_online   = $fromData[ 'online' ];
            
            // if the object has a parent, initialize it's parent as a OneDB_Object
            $this->_parent   = $fromData[ '_parent' ] === NULL
                ? Object( 'OneDB.Object.Root', $this->_server, NULL )
                : Object( 'OneDB.Object', $this->_server, $fromData[ '_parent' ] );

            $_type = $fromData[ '_type' ];
            
            // if the object implements a type, initialize it's type implementation
            if ( $_type !== NULL ) {
                $this->_type = Object( 'OneDB.Type.' . $_type, $this );
                $this->_type->importOwnProperties( $fromData );
            } else $this->_type = NULL;
            
            // set the changed object flag to FALSE
            $this->_changed = FALSE;
        }
        
        /* Creates a child object inside this object.
         *
         * Note that this works only if the object is a container.
         *
         * @param $objectType: <string> - a valid OneDB.Type.<$objectType> class
         *    e.g. for creating a "Foo" object, a class OneDB.Type.Foo is assumed to be
         *    implemented
         *
         * @param $objectName: <nullable string>
         *    if not null, then a name will be assigned to the object on creation.
         *
         * @param $flags: <integer>, can be *one* of the flags:
         *    self::F_CREATE_NORMAL, self::F_CREATE_NOCONFLICT, self::F_CREATE_PRESERVE_EXTENSION
         *
         */
         
        public function create( $objectType, $objectName = NULL, $flags = 0 ) {
            
            if ( $this->_changed )
                throw Object( 'Exception.OneDB', "Object is in an unsaved state, save it first before creating something inside it!" );
            
            if ( !$this->isWritable() )
                throw Object( 'Exception.Security', 'Not enough rights to create object ( onedb filesystem rejected your request )!' );
            
            if ( !$this->isContainer() )
                throw Object( 'Exception.OneDB', "Object is not a container, and it cannot hold stuff inside!" );
            
            if ( $this->isReadOnly() )
                throw Object( 'Exception.OneDB', "Object is ReadOnly!" );
            
            if ( $objectName !== NULL && !in_array( $flags, [ self::F_CREATE_NORMAL, self::F_CREATE_NOCONFLICT, self::F_CREATE_PRESERVE_EXTENSION ] ) )
                throw Object( 'Exception.IO', 'Bad creation flag used at method "create", 3rd argument!' );
            
            try {
                
                $item = Object( 'OneDB.Object', $this->_server );
                $item->parent = $this;
                $item->type = $objectType;
                
                if ( $objectName ) {

                    if ( !is_string( $objectName ) )
                        throw Object( 'Exception.FS', 'Object name should be either null either string (first arg of create method)' );

                    $item->name = $objectName;
                    
                    switch ( $flags ) {
                        
                        // no check name, fastest but unsafest, because when saving object
                        // an exception can be thrown if another object with the same name exists
                        // as a $child of this parent
                        case self::F_CREATE_NORMAL:
                            $item->name = $objectName; 
                            break;
                        
                        case self::F_CREATE_NOCONFLICT:
                        case self::F_CREATE_PRESERVE_EXTENSION:
                        
                            $item->autoCommit = FALSE;
                            
                            $myNames = [];
                            
                            // fetch all the names of the items.
                            $this->childNodes->each( function( $item ) use ( &$myNames ) {
                                $myNames[] = $item->name;
                            } );
                        
                            if ( $flags == self::F_CREATE_NOCONFLICT ) {
                                $namePart       = $objectName;
                                $extensionPart  = '';
                            } else {
                            
                                $extensionPart  = explode( '.', $objectName );
                                $extensionPart  = count( $extensionPart ) > 1
                                    ? ( '.' . end( $extensionPart ) )
                                    : '';
                                $namePart = substr( $objectName, 0, ( $len = strlen( $extensionPart ) ) ? ( strlen( $objectName ) - $len ) : strlen( $objectName ) );
                            }
                            
                            $incrementPart = 0;
                        
                            do {
                                
                                $suggestedName = $incrementPart == 0
                                    ? $namePart . $extensionPart
                                    : $namePart . ' (' . $incrementPart . ')' . $extensionPart;
                                
                                $incrementPart ++;
                                
                            } while ( in_array( $suggestedName, $myNames ) );
                        
                            $item->name = $suggestedName;
                        
                            //throw Object( 'Exception.FS', 'These flags are not supported yet ("' . $namePart . '", "' . $extensionPart . '")' );
                        
                            break;
                        
                    }
                    
                    $item->save();
                    
                }
                
                return $item;
                
            } catch ( Exception $e ) {
                
                throw Object( 'Exception.OneDB', "Failed to create object", 0, $e );
                
            }
        }
        
        /* If the object is a container, returns all it's direct child nodes.
           Otherwise, return an empty iterator
         */
        protected function getChildNodes() {
            return Object( 'OneDB.Iterator', [], $this->_server );
        }
        
        /* If the object is a container, performs a search in the items that
           are either direct or indirect childrens of this object.
         */
        public function find( array $query, $limit = NULL, $orderBy = NULL ) {
            
            // heavy stuff happens only if the object is a container()
            if ( $this->isContainer() ) {
                // If I am a root object, there's no need to filter results
                if ( $this->_id !== NULL ) $query[ '$childOf' ] = $this->url;
                
                // redirect the query to OneDB_Client
                return $this->_server->find( $query, $limit, $orderBy );

            } else return Object( 'OneDB.Iterator', [], $this->_server );
        }
        
        // Removes the object from collection.
        public function delete() {
            
            // unlink childrens ...
            if ( $this->isContainer() )
                $this->find([])->each( function( $item ) { $item->__unlink__(); });
            
            // unlink myself ...
            $this->__unlink__();
            
        }
        
        // A live object is an object that is generated in database.
        // Example of live objects are the items that are automatically created
        // by webservice categories. they are "Live".
        public function isLive() {
            if ( $this->_type ) {
                $typeName = "OneDB_Type_" . $this->_type->name;

                return isset( $typeName::$_isLive )
                    ? $typeName::$_isLive
                    : FALSE;
                
            } else return FALSE;
        }
        
        // If the object isContainer() with live objects ( a webservice category for
        // example, or if the object isContainer() and implements a "refresh" method,
        // than this is the method you should call.
        public function refresh() {
            if ( $this->_type && method_exists( $this->_type, 'refresh' ) )
                $this->_type->refresh();
        }
        
        // The RPC is calling the __commit method of the object in order to do a
        // batch properties update, do a save(), and update it's properties on the browser
        // side.
        
        // @param: $anotherObjectData: <array>, and represents the batch of properties
        // to be updated on this object.
        public function __commit( $anotherObjectData ) {
        
            if ( !is_array( $anotherObjectData ) )
                throw Object( 'Exception.OneDB', "Expected array argument!" );
            
            if ( count( $anotherObjectData ) ) {
                // First update the type if any
                for ( $i=0, $len = count( $anotherObjectData ); $i<$len; $i++ ) {
                    if ( $anotherObjectData[ $i ][ 'name' ] == 'type' ) {
                        $this->type = $anotherObjectData[$i]['type'];
                        array_splice( $anotherObjectData, $i, 1 );
                        break;
                    }
                }
                
                // Update the rest of the properties.
                foreach ( $anotherObjectData as $prop ) {
                
                    switch ( TRUE ) {
                        // FROM RPC SIDE WE DON'T UPDATE THE UID, GID, MUID, CTIME, MTIME, MODE OF THE OBJECT
                        case in_array( $prop['name'], [ 'uid', 'gid', 'muid', 'ctime', 'mtime', 'mode', 'views' ] ):
                            break;
                        // is the property a direct property of this object?
                        case strpos( $prop['name'], '.' ) === FALSE:
                            $this->{$prop['name']} = $prop['value'];
                            break;
                        // is the property a property of a sub-object of this object?
                        // NOTE: at this point we allow only $this->data subproperties batch udate.
                        case substr( $prop['name'], 0, 5 ) == 'data.':
                            $this->data->{substr( $prop['name'], 5 )} = $prop['value'];
                            break;
                    }
                }

                // Save the object after modifications
                $this->save();
            }
            
            // first call the Type of this object to write it's properties.
            // afterwards we write our own properties, to avoid accidental property overriding
            // by the type of the object
            $props = $this->_type ? $this->_type->__mux() : [];
            
            $props[ 'id'    ] = $this->_id;
            $props[ 'parent'] = $this->_parent === NULL ? NULL : $this->_parent->id;
            $props[ 'type'  ] = $this->type;
            
            $props[ 'uid'   ] = $this->_uid;
            $props[ 'gid'   ] = $this->_gid;
            $props[ 'muid'  ] = $this->_muid;
            $props[ 'ctime' ] = $this->_ctime;
            $props[ 'mtime' ] = $this->_mtime;
            $props[ 'mode'  ] = $this->_mode;

            $props[ 'views' ] = $this->_views;

            $props[ 'name'  ] = $this->_name;
            $props[ 'description' ] = $this->_description;
            $props[ 'icon'  ] = $this->_icon;

            $props[ 'keywords' ] = $this->_keywords;
            $props[ 'tags'     ] = $this->_tags;
            $props[ 'online'   ] = $this->_online;
            $props[ 'url'      ] = $this->url;
            
            // the _flags property that is returned to the RPC is a bitmask
            $props[ '_flags'   ] = $this->getObjectFlags();
        
            return $props;
        }
        
        // performs a "snapshot" of the object, in order to transfer it to
        // the RPC to instantiate there the same object
        public function __mux() {
            
            $props = $this->_type ? $this->_type->__mux() : [];
            
            $props[ 'id'     ] = $this->_id;
            $props[ 'parent' ] = $this->_parent === NULL ? NULL : $this->_parent->id;
            $props[ 'type'   ] = $this->type;
            $props[ 'name'   ] = $this->_name;
            
            $props[ 'uid'    ] = $this->_uid;
            $props[ 'gid'    ] = $this->_gid;
            $props[ 'muid'   ] = $this->_muid;
            $props[ 'ctime'  ] = $this->_ctime;
            $props[ 'mtime'  ] = $this->_mtime;
            $props[ 'mode'   ] = $this->_mode;
            
            // there's no need to mux the _views property as all it's methods are running on server-side
            // $props[ 'views'  ] = $this->_views;
            
            $props[ 'description' ] = $this->_description;
            $props[ 'icon'     ] = $this->_icon;
            $props[ 'keywords' ] = $this->_keywords;
            $props[ 'tags'   ] = $this->_tags;
            $props[ 'online' ] = $this->_online;
            $props[ 'url'    ] = $this->url;
            $props[ '_flags' ] = $this->getObjectFlags();
            
            return self::$_muxer_->mux( [ $this->_server, $props ] );
            
        }
        
        // creates a local OneDB_Object based on muxed data sent by
        // the RPC.
        public static function __demux( $data ) {
            return OneDB_Client::__demux( $data[1] )->getElementById( $data[0] === NULL ? NULL : new MongoId( $data[0] ) );
        }
        
        /* Returns an int bitmask value with all the flags of the object
         */
        protected function getObjectFlags() {
            return   ( $this->isReadOnly()  ? self::F_READONLY  : 0 )
                   + ( $this->isContainer() ? self::F_CONTAINER : 0 )
                   + ( $this->_unlinked     ? self::F_UNLINKED : 0 )
                   + ( $this->_changed      ? self::F_UNSTABLE : 0 )
                   + ( $this->isLive()      ? self::F_LIVE : 0 )
                   + ( $this->isReadable()  ? self::F_READABLE : 0 )
                   + ( $this->isWritable()  ? self::F_WRITABLE : 0 )
                   + ( $this->isExecutable()? self::F_EXECUTABLE : 0 );
        }
        
        /* Changes the object file mode
         * @param $mode: can be:
         *     <string> octal mode representation
         *     <string> verbose mask representation
         *     <int>    mask representation as a combination of UMask flags
         */
        public function chmod( $mode, $recursive = FALSE ) {
            
            try {
            
                // test if object is writable by current user
                if ( !$this->isWritable() )
                    throw Object( 'Exception.IO', 'Not enough permissions to chmod object' );
            
                switch ( TRUE ) {
                    case is_string( $mode ):
                        $mode = Umask::str_to_mode( $mode );
                        break;
                    
                    case is_int( $mode ):
                        if ( $mode < 0 || $mode > Umask::MAX_UMASK )
                            throw Object('Exception.IO', 'Invalid file mode!' );
                        break;
                    
                    default:
                        throw Object( 'Exception.IO', 'Invalid chmod $mode argument. Expected string or integer!' );
                        break;
                }
                
                $this->_mode = $mode;
                $this->_changed = TRUE;
                
                // If the $recursive flag is set and the object is a container
                // we set the same mode to all the sub-objects of this object

                if ( $recursive && $this->isContainer() )
                    $this->find([])->each( function( $item ) use ( $mode ) {
                        $item->chmod( $mode, FALSE );
                    });
            
                return $mode;
            
            } catch ( Exception $e ) {
                
                throw Object( 'Exception.IO', 'Failed to set object file mode ' . $mode . ' ( object path: "' . $this->url . '" )', 0, $e );
                
            }
            
        }
        
        /* @param: $userGroup
               type int, representing a valid uid / gid
               type <string> in format "user:group" name
               type <string> in format "user:" name
               type <string> in format ":group" name
           @recursive: weather or not to change the owner to child nodes or not
           @return: <array> [ newUID, newGID ]
         */
        public function chown( $userGroup, $recursive = FALSE ) {
            
            try {
            
                if ( !$this->isWritable() )
                    throw Object( 'Exception.IO', 'Not enough permissions to change the owner of this object' );
                
                $setUid = FALSE;
                $setGid = FALSE;
                
                switch ( TRUE ) {
                    
                    case is_int( $userGroup ):
                        
                        $tmp = $this->_server->sys->user( $userGroup );
                        
                        if ( $tmp === NULL ) {
                            
                            $tmp = $this->_server->sys->group( $userGroup );
                            
                            if ( $tmp === NULL )
                                throw Object( 'Exception.IO', 'Argument (' . $userGroup . ') is not a valid uid or gid from this website' );
                            
                            $setGid = $tmp->gid;
                            
                        } else {
                            
                            $setUid = $tmp->uid;
                            
                        }
                        
                        break;
                    
                    case is_string( $userGroup ):
                        
                        if ( !preg_match( '/^([\d]+|[a-z\d]+((\.[a-z\d]+)+)?)?\:([\d]+|[a-z\d]+((\.[a-z\d]+)+)?)?$/i', $userGroup, $matches ) )
                            throw Object( 'Exception.IO', 'Argument (' . $userGroup . ') is not respecting the format user:group, userID:groupID, user:, or :group' );
                        
                        $unameOrUid = isset( $matches[1] ) && strlen( $matches[1] )
                            ? $matches[1]
                            : NULL;
                        
                        $gnameOrGid = isset( $matches[4] ) && strlen( $matches[4] )
                            ? $matches[4]
                            : NULL;
                        
                        if ( $unameOrUid !== NULL && preg_match( '/^[\d]+$/', $unameOrUid ) )
                            $unameOrUid = (int)$unameOrUid;
                        
                        if ( $gnameOrGid !== NULL && preg_match( '/^[\d]+$/', $gnameOrGid ) )
                            $gnameOrGid = (int)$gnameOrGid;
                        
                        if ( $unameOrUid !== NULL ) {
                            
                            $u = $this->_server->sys->user( $unameOrUid );
                            
                            if ( $u === NULL )
                                throw Object( 'Exception.IO', 'user ' . $unameOrUid . ' was not found!' );
                            
                            $setUid = $u->uid;
                            
                        }
                        
                        if ( $gnameOrGid !== NULL ) {
                            
                            $g = $this->_server->sys->group( $gnameOrGid );
                            
                            if ( $g === NULL )
                                throw Object( 'Exception.IO', 'group ' . $gnameOrGid . ' was not found!' );
                            
                            $setGid = $g->gid;
                            
                        }
                        
                        break;
                    
                    default:
                        
                        throw Object( 'Exception.IO', 'chown: invalid argument (1st argument)!' );
                        
                        break;
                    
                }
                
                $setUid = $setUid === FALSE
                    ? $this->_uid
                    : $setUid;
                
                $setGid = $setGid === FALSE
                    ? $this->_gid
                    : $setGid;
                
                $this->__chown__( $setUid, $setGid );
                
                if ( $recursive && $this->isContainer() )
                
                    $this->find( [] )->each( function( $item ) use ( $setUid, $setGid ) {
                        
                        $item->__chown__( $setUid, $setGid );
                        
                    } );
            
                return [ $setUid, $setGid ];
                
            } catch ( Exception $e ) {
                
                throw Object( 'Exception.IO', 'failed to change the owner of the object (' . $this->url . ')', 104, $e );
                
            }
            
        }
        
        /* The appendChild method is used to make an object a child of
           current object
         */
        public function appendChild( OneDB_Object $anotherObject ) {
            
            try {
                
                if ( $this->isLive() )
                    throw Object( 'Exception.IO', 'Operation not supported' );
                
                //echo "moving $anotherObject->url into $this->url\r";
                
                if ( $this->url == $anotherObject->url )
                    throw Object( 'Exception.IO', 'the source and the destination are the same!' );
            
                if ( $anotherObject->url == '/' )
                    throw Object( 'Exception.IO', 'the root object cannot be moved!' );
            
                if ( !$this->isWritable() )
                    throw Object( 'Exception.IO', 'not enough permissions on destination!' );
                
                if ( !$anotherObject->isWritable() )
                    throw Object( 'Exception.IO', 'not enough permissions on source!' );
                
                if ( !$this->isContainer() )
                    throw Object( 'Exception.IO', 'the destination is not a directory' );

                $oldUrl = $anotherObject->url;
            
                // test if this is child of $anotherObject
                if ( ( $this->url != '/' ) && strpos( $oldUrl . '/', $this->url ) === 0 )
                    throw Object( 'Exception.IO', 'the destination is a child of the source' );
            
                $anotherObject->parent = $this;
                
                $anotherObject->save();
            
                if ( $anotherObject->isContainer() && !$anotherObject->isLive() ) {
                    
                    // we fetch all objects from the database where their url starts with the $oldUrl
                    
                    $expr = new MongoRegex(
                        '/^' . 
                        addcslashes( $oldUrl . '/', '*/\\.#{}+?%()^:' ) . // ' > mc bug
                        '([^*]+)/'
                    );
                    
                    $objects = $this->_server->objects;
                    
                    $cursor = $objects->find([
                        'url' => $expr
                    ], [
                        '_id' => TRUE,
                        'url' => TRUE
                    ] );
                    
                    $items = [];
                    
                    foreach ( $cursor as $row ) {
                        //echo "debug: found: $row[url]\r";
                        $items[] = [
                            '_id' => $row['_id'],
                            'url' => $row['url']
                        ];
                    }
                    
                    $ulen = strlen( $oldUrl ) + 1;
                    $frag = $anotherObject->url;
                    
                    foreach ( $items as $item ) {
                        
                        //echo "debug: rename $item[url] into ";
                        
                        $item[ 'url' ] = $frag . '/' . substr( $item['url'], $ulen );
                        
                        //echo "$item[url]\r";
                        
                        $objects->update([
                            '_id' => $item['_id'],
                        ],[
                            '$set' => [
                                'url' => $item['url']
                            ]
                        ],[
                            'multiple' => FALSE
                        ]);
                        
                    }
                    
                }
                
                return $anotherObject;
                
            } catch ( Exception $e ) {
                throw Object('Exception.IO', 'failed to move object!', 100, $e );
            }
        }
        
        /* Copies $anotherObject ( together with it's sub-structure, recursively ) to this object.
         *
         * WARNING: BECAUSE THIS FUNCTION IS USING RECURSION, YOU MUST ENSURE THAT YOU HAVE ENOUGH
         *          RAM MEMORY FOR THE PROCESS TO FINISH.
         */
        public function copyChild( OneDB_Object $anotherObject ) {
            try {

                if ( $this->isLive() )
                    throw Object( 'Exception.IO', 'Operation not supported' );
            
                if ( $this->url == $anotherObject->url )
                    throw Object( 'Exception.IO', 'the source and the destination are the same!' );
                
                if ( $anotherObject->url == '/' )
                    throw Object( 'Exception.IO', 'the root element cannot be cloned!' );
                
                if ( !$this->isWritable() )
                    throw Object( 'Exception.IO', 'not enough permissions (on destination)!' );
                
                if ( !$anotherObject->isReadable() )
                    throw Object( 'Exception.IO', 'not enough permissions (on source)!' );
                
                if ( !$this->isContainer() )
                    throw Object( 'Exception.IO', 'destination is not a directory!' );
                
                $objectData = $anotherObject->__clone__();
                
                if ( $objectData === NULL )
                    throw Object( 'Exception.IO', 'source object is not clonable or not saved in database!' );
                
                // modify the object _parent to $this->_id, and
                // the object url to $this->url, etc
                
                $objectData['_parent'] = $this->_id;
                
                $objectData['url'] = self::$_path_->resolve(
                    $this->url . '/' . str_replace( '"', '%22', str_replace( '/', '%2F', urldecode( str_replace( '+', ' ', $objectData['name'] ) ) ) )
                );
                
                $objectData['uid']   = $this->_server->user->uid;
                $objectData['gid']   = $this->_server->user->gid;
                $objectData['muid']  = $this->_server->user->uid;
                $objectData['mode']  = $this->_server->user->umask;
                $objectData['ctime'] = $objectData['mtime'] = time();
                
                //print_r( $objectData );
                
                // insert the object in database
                
                try {
            
                    $this->_server->objects->save( $objectData, [
                        'fsync' => TRUE
                    ]);
                
                } catch ( MongoException $e ) {
                    
                    throw $e->getCode() != 11000 ? $e : Object( 'Exception.IO', 'another item with the same name allready exists on destination', 0, $e );
                    
                }
                
                $newObject = $this->_server->getElementById( $objectData[ '_id' ] );
                
                if ( $newObject === NULL )
                    throw Object( 'Exception.IO', 'failed to fetch the object after insertion!' );
                
                if ( $anotherObject->isContainer() && !$anotherObject->isLive() ) {
                    
                    $anotherObject->childNodes->each( function( $node ) use ( &$newObject ) {
                        $newObject->copyChild( $node );
                    } );
                    
                }
                
                return $newObject;
            
            } catch ( Exception $e ) {
                throw Object( 'Exception.IO', 'failed to copy object!', 101, $e );
            }
        }
            
        // WARNING: DO NOT USE THIS FUNCTION DIRECTLY, EVEN IF IT IS DECLARED AS PUBLIC.
        // THIS PUBLIC IS DECLARED AS PUBLIC WITH ANOTHER PURPOSE THAN YOU THINK.
        // USE AND ANALYZE THE chown() method instead!
        
        // @notes: the __chown__ method does not change the owner of the sub-childs of the object.
        // this is why we call cohwn() method instead, which does that
        
        public function __chown__( $uid, $gid ) {
            
            if ( is_int( $uid ) && is_int( $gid ) ) {
            
                $this->_uid = $uid;
                $this->_gid = $gid;
            
            }
            
            $this->_changed = TRUE;
            
        }

        // WARNING: DO NOT USE THIS FUNCTION DIRECTLY, EVEN IF IT IS DECLARED AS PUBLIC.
        // THIS PUBLIC IS DECLARED AS PUBLIC WITH ANOTHER PURPOSE THAN YOU THINK.
        // USE AND ANALYZE THE delete() method instead!
        
        // @notes: the __unlink__ method does not unlink the sub-childs of the object.
        // this is why we call the delete() method instead, which does that.
        public function __unlink__() {
            
            // has been unlinked before?
            if ( $this->_unlinked ) return;
            
            // test if object is writable by current user
            if ( !$this->isWritable() )
                throw Object( 'Exception.IO', 'Not enough permissions to delete object: ' . $this->url );
            
            // set the unlinked object flag 
            $this->_unlinked = TRUE;
            
            // if the object has an _id, it implies that the object has been saved
            // before in the database. so we remove it.
            if ( $this->_id ) {
                // make sure we delete only a single object from database.
                $this->_server->objects->remove([ '_id' => $this->_id ], [ 'justOne' => TRUE ]);
            }
            
        }
        
        /* Returns a physical representation of the object in the database.
           This method is not intended to be used directly by the programmer,
           even if it is a public method.
         */
        public function __clone__() {
            $result = $this->_server->objects->findOne( [ '_id' => $this->_id ] );
            
            if ( is_array( $result ) ) {
                unset( $result[ '_id' ] );
            }
            
            return $result;
        }
        
    }
    
    OneDB_Object::prototype()->defineProperty( 'server', [
        "get" => function() {
            return $this->_server;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'id', [
        
        "get" => function() {
            return $this->_id;
        },
        "set" => function( $newId ) {
            
            if ( empty( $newId ) )
                throw Object('Exception.OneDB', "This property cannot be empty!" );
            
            if ( $this->_id !== NULL )
                throw Object('Exception.OneDB', "This object is allready binded to an _id" );
            
            $this->_id = $newId;
            
            $this->_changed = TRUE;
        }
        
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'name', [
        
        "get" => function() {
            return $this->_name;
        },
        
        "set" => function( $newName ) {
            
            if ( empty( $newName ) )
                throw Object('Exception.OneDB', "The name cannot be empty!" );
            
            $newName = str_replace( '"', '%22', str_replace( '/', '%2F', urldecode( str_replace( '+', ' ', $newName ) ) ) );

            //echo "set name: $newName\n";
            
            $this->_name = $newName;
            
            $this->_changed = TRUE;
            
        }
    
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'type', [
        
        "get" => function() {
            return $this->_type === NULL
                ? NULL
                : $this->_type->name;
        },
        
        "set" => function( $newType ) {
            
            if ( $newType === NULL )
            
                $this->_type = NULL;
            
            else {
                
                if ( !preg_match( '/^[a-z\d]+((\.[a-z\d]+)+)?$/i', $newType ) )
                    throw Object( 'Exception.OneDB', "Invalid object type name $newType" );
                
                $this->_type = Object( 'OneDB.Type.' . $newType, $this );
                
            }
            
            $this->_changed = TRUE;
        }
        
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'uid', [
        "get" => function() {
            return $this->_uid;
        }
    ] );
    
    OneDB_Object::prototype()->defineProperty( 'gid', [
        "get" => function() {
            return $this->_gid;
        }
    ] );
    
    OneDB_Object::prototype()->defineProperty( 'muid', [
        "get" => function() {
            return $this->_muid;
        }
    ] );
    
    OneDB_Object::prototype()->defineProperty( 'mode', [
        "get" => function() {
            return $this->_mode;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'data', [
        
        "get" => function() {
            return $this->_type === NULL
                ? Object( 'OneDB.Type' )
                : $this->_type;
        }
        
    ] );
    
    OneDB_Object::prototype()->defineProperty( 'ctime', [

        "get" => function() {
            return $this->_ctime;
        }

    ]);
    
    OneDB_Object::prototype()->defineProperty( 'mtime', [
        
        "get" => function() {
            return $this->_mtime;
        }
    
    ]);

    OneDB_Object::prototype()->defineProperty( 'description', [
    
        "get" => function( ) {
            return $this->_description;
        },
        
        "set" => function( $newDescription ) {
            
            $this->_description = empty( $newDescription )
                ? NULL
                : $newDescription . '';
            
            $this->_changed = TRUE;
        }
    ]);

    OneDB_Object::prototype()->defineProperty( 'icon', [
        
        "get" => function() {
            return $this->_icon;
        },
        
        "set" => function( $newIcon ) {
            
            $this->_icon = empty( $newIcon )
                ? NULL
                : $newIcon . '';
            
            $this->_changed = TRUE;
            
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'keywords', [
        "get" => function() {
            return $this->_keywords;
        },
        "set" => function( $keywordsList ) {
            if ( empty( $keywordsList ) )
                $this->_keywords = NULL;
            else {
                if ( is_array( $keywordsList ) )
                    $this->_keywords = $keywordsList;
                else
                    throw Object( 'Exception.OneDB', "Property keywords should be either NULL either array!" );
            }
            
            $this->_changed = TRUE;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'tags', [
        "get" => function() {
            return $this->_tags;
        },
        
        "set" => function( $tagsList ) {
            if ( empty( $tagsList ) )
                $this->_tags = NULL;
            else {
                if ( is_array( $tagsList ) )
                    $this->_tags = $tagsList;
                else
                    throw Object( "Exception.OneDB", "Property tags should be either NULL either array!" );
            }
            
            $this->_changed = TRUE;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'parent', [
        "get" => function() {
            return $this->_parent;
        },
        
        "set" => function( $newParent ) {
        
            if ( ! ( $newParent instanceof OneDB_Object ) )
                throw Object( 'Exception.OneDB', "Failed to set parent: The parent property should be an instance of OneDB_Object" );
            else
                $this->_parent = $newParent;
            
            $this->_changed = TRUE;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'online', [
        "get" => function() {
            return $this->_online;
        },
        
        "set" => function( $bool ) {
            $this->_online = $bool ? TRUE : FALSE;

            $this->_changed = TRUE;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'autoCommit', [
        "get" => function() {
            return $this->_autoCommit;
        },
        "set" => function( $bool ) {
            $this->_autoCommit = $bool ? TRUE : FALSE;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'changed', [
        "get" => function() {
            return $this->_changed;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'url', [
        
        "get" => function() {
            if ( $this->_parent === NULL )
                return OneDB_Object::$_path_->decode( '/' . $this->_name );
            else
                return OneDB_Object::$_path_->decode( $this->_parent->url . '/' . str_replace( '/', '%2F', $this->_name ) );
        }
        
    ] );
    
    OneDB_Object::prototype()->defineProperty( 'childNodes', [
        
        "get" => function() {
            
            return $this->_type
                ? $this->_type->getChildNodes()
                : $this->getChildNodes();
        }
        
    ] );
    
    OneDB_Object::prototype()->defineProperty( 'owner', [
        "get" => function() {
            return $this->_uid === NULL
                ? NULL
                : $this->_server->sys->user( $this->_uid );
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'group', [
        "get" => function() {
            return $this->_gid === NULL
                ? NULL
                : $this->_server->sys->group( $this->_gid );
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'views', [
        "get" => function() {
            return $this->_viewsSingleton === NULL
                ? ( $this->_viewsSingleton = Object( 'OneDB.Object.Views', $this, $this->_views ) )
                : $this->_viewsSingleton;
        }
    ] );
    
    OneDB_Object::$_muxer_ = Object('RPC.Muxer');
    OneDB_Object::$_path_  = Object('Utils.Parsers.Path');

?>