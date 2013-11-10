<?php

    function MongoIdentifier( &$something ) {
        return is_object( $something ) ? 
            $something : ($something === NULL ? NULL : new MongoId( $something ));
    }

    class OneDB_MongoObject implements ArrayAccess {
        
        private   $_modified           = FALSE;
        private   $_properties         = array();
        private   $_deleted            = FALSE;
        protected $_id                 = NULL;
        protected $_collection         = NULL;
        protected $_autoCommit         = TRUE;
        private   $_importedClasses    = array();
        private   $_readOnlyProperties = array( '_id' );
        private   $_getters            = array();
        private   $_events             = array();
        
        private   $_auditModifications = FALSE;
        protected $_autoUpdate         = TRUE;
        
        private   $_triggers   = array(
            'before' => array(
                /*
                    "property" => function( $newValue, $oldValue, $this ) {
                                  }
                 */
            ),
            'after'  => array(
                /*
                    "property" => function( $newValue, $oldValue, $this ) {
                        
                     }
                 */
            )
        );
        
        public function __construct( &$collection, $objectID = NULL, $firstLoadDataIfObjectIDWasSet = NULL ) {
            $this->_collection = $collection;
            
            $this->_id = MongoIdentifier( $objectID );
            
            if ($objectID !== NULL) {
                $this->load( $firstLoadDataIfObjectIDWasSet );
            }
        }
        
        public function addTrigger( $property, $timing, $trigger ) {
            if (empty($property))
                throw new Exception("Could not add trigger because first argument is empty!");
            if ($timing != 'before' && $timing != 'after')
                throw new Exception("Unsupported timing (2nd argument) '$timing', allowed only 'before' or 'after'");
            $this->_triggers[ $timing ][ $property ] = $trigger;
        }
        
        public function setReadOnly( $propertyName ) {
            $this->_readOnlyProperties[] = $propertyName;
        }
        
        public function _addGetter( $propertyName, $func ) {
            $this->_getters[$propertyName] = $func;
        }
        
        public function __get( $propertyName ) {
            
            if (isset( $this->_getters[$propertyName] ))
                return $this->_getters[$propertyName]( $this );
            
            switch ($propertyName) {
                case '_id':
                    return $this->_id === NULL ? MongoIdentifier( $this->_properties['_id'] ) : $this->_id;
                    break;
                case '_autoCommit':
                    return $this->_autoCommit;
                    break;
                case '_collection':
                    return $this->_collection;
                    break;
                case 'icon':
                    return isset( $this->_properties['cloudIcon'] ) ? 
                        "/onedb:picture:" . $this->_properties['_id'] : ( isset( $this->_properties['icon'] ) ? $this->_properties['icon'] : NULL );
                    break;
            }
        
            $parts = explode( '.', $propertyName );
            $cursor  = &$this->_properties;
            
            $sofar = array();
            
            if (count($parts) > 1) {
            
                foreach ($parts as $part) {
                
                    if ($cursor === NULL)
                        throw new Exception("Undefined property `$part` in `\$this" . (count($sofar) ? ".".implode('.', $sofar) : "` object"));
                
                    if (is_array( $cursor )) {
                        if ( in_array( $part, array_keys( $cursor ))) {
                            $cursor = &$cursor[ $part ];
                        } else
                            $cursor = NULL;
                    } else $cursor = NULL;
                    
                    $sofar[] = $part;
                }
            } else {
                return isset( $this->_properties[$propertyName] ) ? $this->_properties[$propertyName] : NULL;
            }
            return $cursor;
        }
        
        public function __set( $propertyName, $propertyValue ) {
        
            switch ($propertyName) {
                case '_autoUpdate':
                    $this->_autoUpdate = $propertyValue ? TRUE : FALSE;
                    break;
                case '_autoCommit':
                    $this->_autoCommit = $propertyValue ? TRUE : FALSE;
                    break;
            }
        
            if (in_array( $propertyName, $this->_readOnlyProperties ))
                throw new Exception("Property $propertyName is readOnly");
            
            //echo "MongoObject::__set( $propertyName, $propertyValue )\n";
            
            $oldValue = $this->{"$propertyName"};
            
            if (in_array( $propertyName, array_keys( $this->_triggers['before'] )))
                $this->_triggers['before'][$propertyName](
                    $propertyValue,
                    $this->{"$propertyName"},
                    $this
                );
        
            $parts = explode('.', $propertyName );
            $cursor= &$this->_properties;
            $sofar = array();
            foreach (array_slice($parts, 0, count($parts)-1) as $part) {
                if ($cursor === NULL || !is_array( $cursor[ $part ] ))
                    throw new Exception("Undefined or Illegal property `$part` in " . implode('.', $sofar));
                $cursor = &$cursor[ $part ];
                $sofar[] = $part;
            }
            
            $cursor[ $topPart = end( $parts ) ] = &$propertyValue;
            
            try {
                
                if (in_array( $propertyName, array_keys( $this->_triggers['after'] ))) {
                    $this->_triggers['after'][$propertyName](
                        $propertyValue,
                        $this->{"$propertyName"},
                        $this
                    );
                }
                
                $this->_modified = TRUE;
    
                if ($propertyName != '_autoCommit' && ( $this->_auditModifications || (!$this->_auditModifications && $propertyName != 'type') ) ) {

                    if ($this->_autoUpdate)
                        $this->_properties['modified'] = time();

                }
                
            } catch (Exception $e) {
                $cursor[ $topPart ] = &$oldValue;
                throw $e;
            }
            
        }
        
        public function save() {

            if ($this->_deleted)
                return;
            
            if ( !isset( $this->_properties['name'] ) ||
                 !$this->_properties['name'] ||
                 !strlen( $this->_properties['name'] )
            ) throw new Exception("Cannot save item, no name was provided!");
            
            if (defined('ONEDB_BACKEND')) {
                
                $security = $this->_collection->db->onedb->security( $_SESSION['UNAME'] );
                
                switch (TRUE) {
                    case preg_match( '/^category(\s|$)/i', $this->type ) || $this->type === NULL:
                        $token = $security->{ "$this->_id" };
                        break;
                    default:
                        $token = $security->{ "$this->_parent" };
                        break;
                }
                
                if (!$token->canWrite())
                    throw new Exception("Access denied!\nYou don't have permissions to modify this object\n\n");
                
            }
            
            $this->on('save');
            
            /* We setup an order for save */
            if (@empty( $this->_properties['_order'] ) )
                $this->_properties['_order'] = OneDB_OrderID();

            if ($this->_id !== NULL) {
            
                $this->_properties['_id'] = MongoIdentifier( $this->_id );
                
                //print_r($this->_properties);
                
                $this->_collection->update( 
                    array(
                        '_id' =>  $this->_id
                    ),
                    $this->_properties,
                    array(
                        'multiple' => FALSE,
                        'safe'     => TRUE
                    )
                );
                
                $this->on('update');
                
                //echo "saved $this->_id, {$this->_properties['_id']}\n";
                
            } else {
            
                $this->_collection->insert(
                    $this->_properties,
                    array(
                        'safe' => TRUE
                    )
                );
                
                $this->_id = $this->_properties['_id'];
                
                $this->on('create');
            
            }
            
            $this->_modified = FALSE;
            
            return TRUE;
        }
        
        public function load( $data = NULL ) {
            
            if (!$this->_id)
                throw new Exception("Could not load item that is not saved before!");
            
            if ($data === NULL) {
            
                $loadData = $this->_collection->findOne(
                    array(
                        "_id" => MongoIdentifier($this->_id)
                    )
                );
                
                if ($loadData === NULL)
                    throw new Exception("Record _id=" . $this->_id . " not found in collection $this->_collection");
            
                $nType = isset($loadData['type']) ? $loadData['type'] : NULL;
                
                unset( $loadData['type'] );
            
                $this->_properties = &$loadData;
                
                $this->type = $nType;
                $this->_auditModifications = TRUE;
                
            } else {
                if (!is_array( $data ) )
                    throw new Exception("Invalid load data (should be an array)");
                
                if (!isset($data['_id']))
                    throw new Exception("Invalid mongo object!");
                
                $nType = isset( $data['type'] ) ? $data['type'] : NULL;
                unset( $data['type'] );
                
                $this->_id = $data['_id'];
                $this->_properties = &$data;
                
                $this->type = $nType;
                $this->_auditModifications = TRUE;
            }
            
            $this->_modified   = FALSE;
        }
        
        public function deleteProperty( $propertyName ) {
            if ( isset( $this->_properties[ "$propertyName" ] ) ) {
                unset( $this->_properties[ "$propertyName" ] );
                $this->_properties['modified'] = time();
                $this->_modified = TRUE;
            }
        }
        
        public function deleteDependencies() {
            /* Delete dependencies */
            if (isset($this->_properties["_unlink"]) && is_array( $this->_properties["_unlink"])) {
            
                foreach ($this->_properties["_unlink"] as $dependency) {
                    $dependencyTable = $dependency['collection'];
                    $dependencyID    = "$dependency[id]";
                    
                    if ($dependencyTable != 'articles') {

                        $table = $dependencyTable == '@files' ? 
                            $this->_collection->db->getGridFS() : 
                            $this->_collection->db->{"$dependencyTable"};

                        try {
                            $table->remove(
                                array(
                                    "_id" => MongoIdentifier( $dependencyID )
                                )
                            );
                        } catch (Exception $e) {
                            trigger_error( "OneDB: MongoObject.delete(): Could not delete dependency: $dependencyTable.$dependencyID", E_USER_WARNING );
                        }
                    
                    } else {
                        
                        try {
                        
                            $this->_collection->db->onedb->articles(
                                array(
                                    "_id" => MongoIdentifier( $dependencyID )
                                )
                            )->get(0)->delete();
                            
                        } catch (Exception $e) {
                            //trigger_error("OneDB: MongoObject.delete() (from articles): Could not delete article id: $dependencyID ==> " . $e->getMessage(), E_USER_WARNING );
                        }
                        
                    }
                }
                
                unset( $this->_properties["_unlink"] );
            }
        }
        
        public function delete() {
            if ($this->_deleted)
                return;
            
            $this->on( 'delete' );
            
            $this->deleteDependencies();
            
            $this->_collection->remove(
                array(
                    '_id' => $this->_id
                ),
                array(
                    'justOne' => TRUE,
                    'safe'    => TRUE
                )
            );
            $this->_deleted = TRUE;
            
            // $this->on('delete');
        }
        
        public function isChildOf( $categoryID ) {
        
            $parent = "$this->_parent";
            
            $categoryID = $categoryID === NULL ? "" : "$categoryID";
            
            if ($categoryID == "")
                return TRUE;
            
            do {

                $parent = !empty( $parent ) ?
                    new OneDB_Category( 
                        $this->_collection->db->categories,
                        MongoIdentifier( $parent )
                    ) : NULL;
                
                if ($parent === NULL)
                    return false;

                if ("$parent->_id" == $categoryID)
                    return true;
                    
                $parent = "$parent->_parent";
                
            } while ($parent);
            
            return false;
        }
        
        public function __destruct() {
            if ( !$this->_modified || !$this->_autoCommit )
                return;
            $this->save();
        }
        
        public function __toString() {
            return json_encode( $this->_properties );
        }
        
        public function toArray() {
            $out = $this->_properties;
            return $out;
        }
        
        public function extend( array $object ) {
            foreach (array_keys( $object ) as $key)
                $this->_properties[$key] = $object[$key];
                
            $this->_modified = count($object) ? TRUE : FALSE;
        }
        
        public function import( $requiredClassPath ) {

            $className = @preg_replace('/.class.php$/', '', end( explode( DIRECTORY_SEPARATOR, $requiredClassPath ) ) );
            
            if (!class_exists( $className )) {
                if ( !file_exists( $requiredClassPath ) ) {
                    
                    throw new Exception("Cannot import class $className - required file ($requiredClassPath) not found");
                }
                
                require_once $requiredClassPath;
                
                if (!class_exists( $className ))
                    throw new Exception("Cannot import class $className - class not found (although a require was made for $requiredClassPath)!");
            }
            $this->_importedClasses["$className"] = new $className( $this );
        }
        
        public function addEventListener( $eventName, $func ) {
            if (!isset( $this->_events[ $eventName ] ))
                $this->_events[ $eventName ] = array();
            
            $this->_events[ $eventName ][] = $func;
        }
        
        public function on( $eventName ) {
            if (!isset( $this->_events[ $eventName ] ))
                return;
            foreach ($this->_events[ $eventName ] as $eventFunction)
                $eventFunction( $this );
        }
        
        public function __call( $methodName, $arguments ) {
        
            foreach (array_values( $this->_importedClasses ) as $iClass ) {
                if (method_exists( $iClass, $methodName ))
                    return call_user_func_array(
                        array( $iClass, $methodName ),
                        $arguments
                    );
            }
            
            throw new Exception("Invalid class method `$methodName`");
        }
        
        /* Array access implementation */
        
        public function offsetExists( $offset ) {
            foreach ( array_values( $this->_importedClasses ) as $iClass )
                if ( in_array( 'ArrayAccess', class_implements( $iClass ) ) )
                    return $iClass->offsetExists( $offset );
            throw new Exception("No classes in imported stack are implementing the ArrayAccess interface!");
        }
        
        public function offsetGet( $offset ) {
            foreach ( array_values( $this->_importedClasses ) as $iClass )
                if ( in_array( 'ArrayAccess', class_implements( $iClass ) ) )
                    return $iClass->offsetGet( $offset );
            throw new Exception("No classes in imported stack are implementing the ArrayAccess interface!");
        }
        
        public function offsetSet( $offset, $value ) {
            foreach ( array_values( $this->_importedClasses ) as $iClass )
                if ( in_array( 'ArrayAccess', class_implements( $iClass ) ) )
                    return $iClass->offsetSet( $offset, $value );
            throw new Exception("No classes in imported stack are implementing the ArrayAccess interface!");
        }
        
        public function offsetUnset( $offset ) {
            foreach ( array_values( $this->_importedClasses ) as $iClass )
                if ( in_array( 'ArrayAccess', class_implements( $iClass ) ) )
                    return $iClass->offsetUnset( $offset );
            throw new Exception("No classes in imported stack are implementing the ArrayAccess interface!");
        }
        
        /* End of array access implementation */
        
        public function __has_method( $methodName ) {
            if (method_exists( $this, $methodName ))
                return true;
            else
                foreach (array_values( $this->_importedClasses ) as $iClass ) {
                    if (method_exists( $iClass, $methodName ))
                        return TRUE;
                }
            
            return FALSE;
        }
        
        public function views() {
            require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "OneDB_ObjectView.class.php";
            return new OneDB_ObjectView( $this );
        }
        
        public function getServer() {
            return $this->_collection->db->onedb;
        }
        
        public function frontendSettings() {
            if (!class_exists( 'OneDB_FrontendSettings' ) )
                require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "OneDB_FrontendSettings.class.php";
            return new OneDB_FrontendSettings( $this );
        }
    }
    
?>