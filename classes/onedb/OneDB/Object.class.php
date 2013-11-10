<?php

    // In v2, all the objects are stored in a single collection
    // called objects.

    class OneDB_Object extends Object {
        
        protected $_server      = NULL;
        
        // properties that are mixed inside object
        protected $_id          = NULL;
        protected $_name        = NULL;

        protected $_created     = NULL;
        protected $_modified    = NULL;

        protected $_owner       = NULL;
        protected $_modifier    = NULL;

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

        // Weather or not this object is a container.
        // If the object is a container, it can hold childrens inside.
        protected static $_isContainer = FALSE;

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
        
        public function __isContainer() {
            return static::$_isContainer;
        }
        
        /* Saves the object in database
         */
        public function save() {
            
            //echo "saving...\n";
            
            if ( $this->_owner === NULL )
                $this->_owner = $this->_server->runAs;
            
            $this->_modifier = $this->_server->runAs;
            
            if ( $this->_created === NULL )
                $this->_created = time();
            
            $this->_modified = time();
            
            if ( $this->_name === NULL )
                throw Object( 'Exception.OneDB', "Cannot save object, because it don't have a name set!" );
            
            $saveProperties = [];
            
            if ( $this->_id != NULL )
                $saveProperties[ '_id' ]     = $this->_id;

            $saveProperties[ '_type' ]       = $this->_type === NULL 
                ? NULL
                : $this->_type->name;

            $saveProperties[ '_parent' ]     = $this->_parent->_id;

            $saveProperties[ 'name' ]        = $this->_name;
            $saveProperties[ 'created' ]     = $this->_created;
            $saveProperties[ 'modified' ]    = $this->_modified;
            $saveProperties[ 'owner' ]       = $this->_owner;
            $saveProperties[ 'modifier' ]    = $this->_modifier;
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
                
                throw Object( 'Exception.OneDB', "Failed to save object" . ( $this->_id ? "( _id = $this->_id )" : "" ), 0, $e );
                
            }
        }
        
        /* Returns an array with all object fields */
        public function toObject() {
            
            $out = [];
            
            if ( $this->_id != NULL )
                $out[ '_id' ] = "$this->_id";

            $out[ '_container' ]  = static::$_isContainer;
            
            $out[ '_parent' ] = $this->_parent->_id . '';

            if ( $this->_type != NULL )
                $out['_type'] = $this->_type->name;

            $out[ 'name' ] = $this->_name;
            $out[ 'created' ]     = $this->_created;
            $out[ 'modified' ]    = $this->_modified;
            $out[ 'owner' ]       = $this->_owner;
            $out[ 'modifier' ]    = $this->_modifier;
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
            $this->_created     = $fromData[ 'created' ];
            $this->_modified    = $fromData[ 'modified' ];
            $this->_owner       = $fromData[ 'owner' ];
            $this->_modifier    = $fromData[ 'modifier' ];
            $this->_description = $fromData[ 'description' ];
            $this->_icon        = $fromData[ 'icon' ];
            $this->_keywords    = $fromData[ 'keywords' ];
            $this->_tags        = $fromData[ 'tags' ];
            $this->_online      = $fromData[ 'online' ];
            
            $this->_parent      = $fromData[ '_parent' ] === NULL
                ? Object( 'OneDB.Object.Root', $this->_server, NULL )
                : Object( 'OneDB.Object', $this->_server, $fromData[ '_parent' ] );

            $_type              = $fromData[ '_type' ];
            
            if ( $_type !== NULL ) {
                
                $this->_type    = Object( 'OneDB.Type.' . $_type, $this );
                
                $this->_type->importOwnProperties( $fromData );
                
            } else
                
                $this->_type    = NULL;
            
            $this->_changed     = FALSE;
        }
        
    }
    
    OneDB_Object::prototype()->defineProperty( '_id', [
        
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
            
            $this->_name = $newName;
            
            $this->_changed = TRUE;
            
        }
    
    ]);
    
    OneDB_Object::prototype()->defineProperty( '_type', [
        
        "get" => function() {
            return $this->_type === NULL
                ? NULL
                : $this->_type->name;
        },
        
        "set" => function( $newType ) {
            
            if ( $newType === NULL )
            
                $this->_type = NULL;
            
            else {
                
                if ( !preg_match( '/^[a-z\d\_]+$/i', $newType ) )
                    throw Object( 'Exception.OneDB', "Invalid object type name $newType" );
                
                $this->_type = Object( 'OneDB.Type.' . $newType, $this );
                
            }
            
            $this->_changed = TRUE;
        }
        
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'data', [
        
        "get" => function() {
            return $this->_type === NULL
                ? Object( 'OneDB.Type' )
                : $this->_type;
        }
        
    ] );
    
    OneDB_Object::prototype()->defineProperty( 'created', [

        "get" => function() {
            return $this->_created;
        }

    ]);
    
    OneDB_Object::prototype()->defineProperty( 'modified', [
        
        "get" => function() {
            return $this->_modified;
        }
    
    ]);

    OneDB_Object::prototype()->defineProperty( 'owner', [
        "get" => function() {
            return $this->_owner;
        },
        
        "set" => function( $newOwner ) {
            $this->_owner = $newOwner . '';
            
            $this->_changed = TRUE;
        }
    ]);
    
    OneDB_Object::prototype()->defineProperty( 'modifier', [
        
        "get" => function() {
            return $this->_modifier;
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
    
    OneDB_Object::prototype()->defineProperty( '_parent', [
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
    
    OneDB_Object::prototype()->defineProperty( 'url', [
        
        "get" => function() {
            if ( $this->_parent === NULL )
                return '/' . $this->_name;
            else
                return preg_replace( '/[\/]+/', '/', $this->_parent->url . '/' . urlencode( $this->_name ) );
        }
        
    ] );

?>