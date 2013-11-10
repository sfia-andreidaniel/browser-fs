<?php

    class OneDB_Article_plugin_JSONWebserviceCategory {

        protected $_ = NULL;

        public function __construct( &$that ) {
            $thisParent = $that->_parent;
            
            $thisParent = MongoIdentifier( $thisParent );
            
            $thatCollection = $that->_collection->db;
            
            /* Search the local object */
            
            $mongoObject = $that->_collection->db->categories->findOne(
                array(
                    'name' => $that->name,
                    '_parent' => $thisParent
                )
            );
            
            if ( NULL === $mongoObject ) {
                /* If the virtual webservice is not found locally, we try to create a new one */
                
                $thisData = array(
                    'type' => 'JSONWebserviceCategory',
                    'name' => $that->name,
                    '_parent' => $thisParent,
                    'maxDocuments' => -1,
                    'webserviceURL' => $that->webserviceURL,
                    'webserviceTTL' => $that->webserviceTTL,
                    'webserviceCFG' => $that->webserviceCFG,
                    'httpUsername'  => $that->httpUsername,
                    'httpPassword'  => $that->httpPassword,
                    'keywords' => array(),
                    'tags' => array(),
                    'online' => TRUE,
                    'date' => time(),
                    'isVirtual' => TRUE,
                    'views' => array(
                        'category' => array(
                            
                        ),
                        'item' => array(
                            
                        )
                    ),
                    'filter' => array(),
                    '_items' => array(),
                    '_JSONObject' => $that->toArray()
                );
                
                if (!is_numeric( $thisData['maxDocuments'] ) )
                    $thisData['maxDocuments'] = -1;
                
                if (empty( $thisData['webserviceURL'] ) )
                    throw new Exception("Bad OneDB_Article_plugin_JSONWebserviceCategory.webserviceURL!");
                
                try {
                    $result = $that->_collection->db->categories->insert(
                        $thisData,
                        array(
                            'safe' => TRUE,
                            'fsync'=> TRUE
                        )
                    );
                    
                    if (!defined('ONEDB_BACKEND_FORCE_REFRESH'))
                        define('ONEDB_BACKEND_FORCE_REFRESH', 1);
                    
                } catch (Exception $e) {
                    throw new Exception("Could not create dynamically object into database!\n" . $e->getMessage());
                }
                
                $mongoObject = $that->_collection->db->categories->findOne(
                    array(
                        'name' => $that->name,
                        '_parent' => $thisParent
                    )
                );
                
                if (NULL === $mongoObject) {
                    throw new Exception("Could not fetch back the webservice category after insert!");
                }
            }
            
            $this->_ = new OneDB_Category(
                $that->_collection->db->categories,
                $mongoObject['_id'],
                $mongoObject
            );
        }
        
        public function save() {
            die("Cannot save!");
        }
        
        public function __get( $propertyName ) {
            return $this->_->{"$propertyName"};
        }
        
        public function __set( $propertyName, $propertyValue ) {
            $this->_->{"$propertyName"} = $propertyValue;
        }
        
        public function __call( $methodName, $args ){
            return $this->_->__call( $methodName, $args );
        }
        
        public function toArray() {
            return $this->_->toArray();
        }
    }

?>