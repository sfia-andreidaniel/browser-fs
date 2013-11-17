<?php

    require_once __DIR__ . '/Client.class.php';

    // In v2, all the objects are stored in a single collection
    // called objects.

    class OneDB_Object extends Object implements IDemuxable {
        
        const F_NOFLAG       = 0;    // no flags
        const F_READONLY     = 2;    // if the object is not modifiable ( is read only )
        const F_CONTAINER    = 4;    // if the object can holds another objects ( is a folder )
        const F_UNLINKED     = 8;    // If the object has been unlinked ...
        const F_ROOT         = 16;   // Object is root if is instanceof OneDB_Object_Root
        const F_UNSTABLE     = 32;   // Object is in an unstable state if it was created and not saved.
        const F_LIVE         = 64;   // Live object. If this object is child of a Webservice category usual
        const F_FLUSH        = 128;  // RPC client-side flag only - Save object after set a property
        const F_READABLE     = 256;  // weather the object is readable or not
        const F_WRITABLE     = 512;  // weather the object is writable or not
        const F_EXECUTABLE   = 1024; // weather the object can be executed or not
        
        
        protected $_server      = NULL;
        
        // properties that are mixed inside object
        protected $_id          = NULL;
        protected $_name        = NULL;

        protected $_ctime       = NULL;
        protected $_mtime       = NULL;

        protected $_uid         = NULL; // OWNER USER ID
        protected $_gid         = NULL; // OWNER GROUP ID
        protected $_muid        = NULL; // LAST USER ID THAT MODIFIED THIS OBJECT
        protected $_mode        = NULL; // OBJECT FILE MODE

        protected $_description = NULL;
        protected $_icon        = NULL;
        protected $_keywords    = [];
        protected $_tags        = [];
        
        protected $_online      = FALSE;
        
        protected $_parent      = NULL;
        
        // properties that are not mixed inside object
        protected $_autoCommit  = TRUE;
        protected $_changed     = FALSE;
        protected $_type        = NULL;
        protected $_unlinked    = FALSE;

        // Weather or not this object is a container.
        // If the object is a container, it can hold childrens inside.
        protected static $_isContainer = FALSE;
        
        // Weather or not the object is readOnly.
        protected static $_isReadOnly  = FALSE;
        
        // The native properties of a OneDB_Object
        public static $_nativeProperties = [
            '_id',
            '_parent',
            '_type',
            
            'name',
            
            'ctime',
            'mtime',
            
            'gid',
            'uid',
            'muid',
            
            'mode',
            
            'description',
            'icon',
            'keywords',
            'tags',
            'online'
        ];
        
        public static $_muxer = NULL;
        
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
        
        public function isContainer() {
            if ( $this->_type !== NULL )
                return $this->_type->isContainer();
            else
                return static::$_isContainer;
        }
        
        public function isReadOnly() {
            if ( $this->_type !== NULL )
                return $this->_type->isReadOnly();
            else
                return static::$_isReadOnly;
        }

        public function isReadable() {
            return $this->_server->sys->canRead( $this->uid, $this->gid, $this->mode, $this->_server->user );
        }

        public function isWritable() {
            return $this->_server->sys->canWrite( $this->uid, $this->gid, $this->mode, $this->_server->user );
        }

        public function isExecutable() {
            return $this->_server->sys->canExecute( $this->uid, $this->gid, $this->mode, $this->_server->user );
        }

        /* Saves the object in database
         */
        public function save() {
            
            if ( $this->_unlinked )
                return;
            
            //echo "saving...\n";
            
            // if ( $this->_owner === NULL )
            //    $this->_owner = $this->_server->runAs;
            
            if ( $this->_uid === NULL )
                $this->_uid = $this->_server->user->uid;
            
            if ( $this->_gid === NULL )
                $this->_gid = $this->_server->user->gid;
            
            if ( $this->_muid === NULL )
                $this->_muid = $this->_server->user->uid;
            
            if ( $this->_mode === NULL )
                $this->_mode = $this->_server->user->umask;
            
            // $this->_modifier = $this->_server->runAs;
            
            if ( $this->_ctime === NULL )
                $this->_ctime = time();
            
            $this->_mtime = time();
            
            if ( $this->_name === NULL )
                throw Object( 'Exception.OneDB', "Cannot save object, because it don't have a name set!" );
            
            $saveProperties = [];
            
            if ( $this->_id != NULL )
                $saveProperties[ '_id' ]     = $this->_id;

            $saveProperties[ '_type' ] = $this->_type === NULL 
                ? NULL
                : $this->_type->name;

            $saveProperties[ '_parent' ]     = $this->_parent->_id;

            $saveProperties[ 'uid' ]         = $this->_uid;
            $saveProperties[ 'gid' ]         = $this->_gid;
            $saveProperties[ 'muid' ]        = $this->_muid;
            $saveProperties[ 'mode' ]        = $this->_mode;
            $saveProperties[ 'ctime' ]       = $this->_ctime;
            $saveProperties[ 'mtime' ]       = $this->_mtime;

            $saveProperties[ 'name' ]        = $this->_name;
            $saveProperties[ 'description' ] = $this->_description;
            $saveProperties[ 'icon' ]        = $this->_icon;
            $saveProperties[ 'keywords' ]    = $this->_keywords;
            $saveProperties[ 'tags' ]        = $this->_tags;
            $saveProperties[ 'online' ]      = $this->_online;
            $saveProperties[ 'url' ]         = $this->url;
            
            if ( $this->_type !== NULL )
                $this->_type->exportOwnProperties( $saveProperties );

            try {
            
                $this->_server->objects->save(
                    $saveProperties,
                    [
                        'fsync' => TRUE
                    ]
                );
                
                if ( $this->_id === NULL )
                    $this->_id = $saveProperties[ '_id' ];
                
                $this->_changed = FALSE;
                
            } catch ( Exception $e ) {
                
                $errorMessage = "Failed to save object" . ( $this->_id ? "( _id = $this->_id )" : "" ) . ": " . $e->getMessage();
                
                if ( $e instanceof MongoCursorException ) {
                    
                    switch ( $e->getCode() ) {
                        
                        case 11000:
                            $errorMessage = "Another item allready exists with that name!";
                            break;
                        
                    }
                    
                }
                
                throw Object( 'Exception.OneDB', $errorMessage, 0, $e );
                
            }
        }
        
        /* Returns an array with all object fields */
        public function toObject() {
            
            if ( $this->_unlinked )
                return NULL;
            
            $out = [];
            
            $out[ '_id' ] = $this->_id === NULL
                ? NULL
                : $this->_id . '';

            $out[ '_container' ]  = $this->isContainer();
            
            if ( $this->_parent )
                $out['_parent'] = $this->_parent->_id . '';

            if ( $this->_type != NULL )
                $out['_type'] = $this->_type->name;

            $out[ 'name' ]        = $this->_name;

            $out[ 'uid' ]         = $this->_uid;
            $out[ 'gid' ]         = $this->_gid;
            $out[ 'muid' ]        = $this->_muid;
            $out[ 'ctime' ]       = $this->_ctime;
            $out[ 'mtime' ]       = $this->_mtime;
            $out[ 'mode' ]        = $this->_mode;
            
            $out[ 'description' ] = $this->_description;
            $out[ 'icon' ]        = $this->_icon;
            $out[ 'keywords' ]    = $this->_keywords;
            $out[ 'tags' ]        = $this->_tags;
            $out[ 'online' ]      = $this->_online;
            $out[ 'url' ]         = $this->url;
            
            if ( $this->_type !== NULL )
                $this->_type->exportOwnProperties( $out );
            
            return $out;
        }
        
        public function _change( $propertyName, $propertyValue ) {
            $this->_changed = TRUE;
        }
        
        /* Loads the object from database.

           If parameter $fromData is present and is of type array, instead of loading the object
           from the database, the information from the $fromData will be used
         */
        public function load( $fromData = NULL ) {
            
            if ( $fromData === NULL || !is_array( $fromData ) ) {
                
                if ( $this->_id === NULL )
                    throw Object( 'Exception.OneDB', "Failed to load object, no _id was specified!" );
                
                $fromData = $this->_server->objects->findOne( [
                    '_id' => $this->_id
                ] );
                
                if ( $fromData === NULL )
                    throw Object( 'Exception.OneDB', "Failed to load object, the object does not exists!" );
            }
            
            if ( !isset( $fromData[ '_id' ] ) )
                throw Object( 'Exception.OneDB', "The loaded object doesn't contain an _id" );
            
            $this->_id          = $fromData[ '_id' ];
            $this->_name        = urldecode( $fromData[ 'name' ] );

            $this->_ctime       = $fromData[ 'ctime' ];
            $this->_mtime       = $fromData[ 'mtime' ];
            $this->_uid         = $fromData[ 'uid' ];
            $this->_gid         = $fromData[ 'gid' ];
            $this->_muid        = $fromData[ 'muid' ];
            $this->_mode        = $fromData[ 'mode' ];

            $this->_description = $fromData[ 'description' ];
            $this->_icon        = $fromData[ 'icon' ];
            $this->_keywords    = $fromData[ 'keywords' ];
            $this->_tags        = $fromData[ 'tags' ];
            $this->_online      = $fromData[ 'online' ];
            
            $this->_parent      = $fromData[ '_parent' ] === NULL
                ? Object( 'OneDB.Object.Root', $this->_server, NULL )
                : Object( 'OneDB.Object', $this->_server, $fromData[ '_parent' ] );

            $_type = $fromData[ '_type' ];
            
            if ( $_type !== NULL ) {
                
                $this->_type = Object( 'OneDB.Type.' . $_type, $this );
                
                $this->_type->importOwnProperties( $fromData );
                
            } else
                
                $this->_type    = NULL;
            
            $this->_changed     = FALSE;
        }
        
        public function create( $objectType, $objectName = NULL ) {
            
            if ( $this->_changed )
                throw Object( 'Exception.OneDB', "Object is in an unsaved state, save it first before creating something inside it!" );
            
            if ( !$this->isWritable() )
                throw Object( 'Exception.Security', 'Not enough rights to create object ( onedb filesystem rejected your request )!' );
            
            if ( !$this->isContainer() )
                throw Object( 'Exception.OneDB', "Object is not a container, and it cannot hold stuff inside!" );
            
            if ( $this->isReadOnly() )
                throw Object( 'Exception.OneDB', "Object is ReadOnly!" );
            
            try {
                
                $item = Object( 'OneDB.Object', $this->_server );

                $item->parent = $this;
                
                $item->type = $objectType;
                
                if ( $objectName ) {
                    $item->name = $objectName;
                }
                
                return $item;
                
            } catch ( Exception $e ) {
                
                throw Object( 'Exception.OneDB', "Failed to create object", 0, $e );
                
            }
        }
        
        protected function getChildNodes() {
            return Object( 'OneDB.Iterator', [], $this->_server );
        }
        
        public function find( array $query, $limit = NULL, $orderBy = NULL ) {

            if ( $this->isContainer() ) {
                
                // If I am a root object, there's no need to filter results
                if ( $this->_id !== NULL )
                    $query[ '$childOf' ] = $this->url;

                return $this->_server->find( $query, $limit, $orderBy );

            } else return Object( 'OneDB.Iterator', [], $this->_server );
        
        }
        
        // Removes the object from collection.
        public function delete() {
            
            if ( $this->isContainer() ) {
                $this->find([])->each( function( $item ) {
                    $item->___unlink();
                });
            }
            
            $this->___unlink();
            
        }
        
        // DO NOT USE THIS FUNCTION DIRECTLY, EVEN IF IT IS DECLARED AS PUBLIC.
        public function ___unlink() {
            
            if ( $this->_unlinked )
                return;
            
            $this->_unlinked = TRUE;
            
            if ( $this->_id ) {
                
                $this->_server->objects->remove([
                    '_id' => $this->_id
                ], [
                    'justOne' => TRUE
                ]);
                
            }
            
        }
        
        public function isLive() {
            
            if ( $this->_type ) {
                
                $typeName = "OneDB_Type_" . $this->_type->name;
                
                if ( isset( $typeName::$_isLive ) )
                    return $typeName::$_isLive;
                else
                    return FALSE;
                
            } else return FALSE;
            
        }
        
        public function refresh() {
            
            if ( $this->_type && method_exists( $this->_type, 'refresh' ) )
                $this->_type->refresh();
        }
        
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
                        case in_array( $prop['name'], [ 'uid', 'gid', 'muid', 'ctime', 'mtime', 'mode' ] ):
                            break;
                        
                        case strpos( $prop['name'], '.' ) === FALSE:
                            $this->{$prop['name']} = $prop['value'];
                            break;
                        case substr( $prop['name'], 0, 5 ) == 'data.':
                            $this->data->{substr( $prop['name'], 5 )} = $prop['value'];
                            break;
                    }
                }

                // Save the object after modifications
                $this->save();
            }
            
            
            if ( $this->_type ) {
                $props = $this->_type->__mux();
            } else $props = [];
            
            $props[ 'id' ]          = $this->_id;
            $props[ 'parent']       = $this->_parent === NULL ? NULL : $this->_parent->id;
            $props[ 'type' ]        = $this->type;
            
            $props[ 'uid' ]         = $this->_uid;
            $props[ 'gid' ]         = $this->_gid;
            $props[ 'muid' ]        = $this->_muid;
            $props[ 'ctime' ]       = $this->_ctime;
            $props[ 'mtime' ]       = $this->_mtime;
            $props[ 'mode' ]        = $this->_mode;
            
            $props[ 'name' ]        = $this->_name;
            $props[ 'description' ] = $this->_description;
            $props[ 'icon' ]        = $this->_icon;
            $props[ 'keywords' ]    = $this->_keywords;
            $props[ 'tags' ]        = $this->_tags;
            $props[ 'online' ]      = $this->_online;
            $props[ 'url' ]         = $this->url;
            $props[ '_flags' ]      = $this->getObjectFlags();
        
            return $props;
        }
        
        public function __mux() {
            
            if ( $this->_type ) {
                $props = $this->_type->__mux();
            } else $props = [];
            
            $props[ 'id' ]          = $this->_id;
            $props[ 'parent']       = $this->_parent === NULL ? NULL : $this->_parent->id;
            $props[ 'type' ]        = $this->type;
            $props[ 'name' ]        = $this->_name;
            
            $props[ 'uid' ]         = $this->_uid;
            $props[ 'gid' ]         = $this->_gid;
            $props[ 'muid' ]        = $this->_muid;
            $props[ 'ctime' ]       = $this->_ctime;
            $props[ 'mtime' ]       = $this->_mtime;
            $props[ 'mode' ]        = $this->_mode;
            
            $props[ 'description' ] = $this->_description;
            $props[ 'icon' ]        = $this->_icon;
            $props[ 'keywords' ]    = $this->_keywords;
            $props[ 'tags' ]        = $this->_tags;
            $props[ 'online' ]      = $this->_online;
            $props[ 'url' ]         = $this->url;
            $props[ '_flags' ]      = $this->getObjectFlags();
            
            return self::$_muxer->mux( [ $this->_server, $props ] );
            
        }
        
        public static function __demux( $data ) {
            
            return OneDB_Client::__demux( $data[1] )->getElementById( $data[0] === NULL ? NULL : new MongoId( $data[0] ) );

        }
        
        /* Returns an integer value with all the flags of the object
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
                return '/' . $this->_name;
            else
                return preg_replace( '/[\/]+/', '/', $this->_parent->url . '/' . urlencode( $this->_name ) );
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
    
    OneDB_Object::$_muxer = Object('RPC.Muxer');

?>