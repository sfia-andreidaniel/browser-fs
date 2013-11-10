<?php

    require_once "OneDB_MongoObject.class.php";
    require_once "OneDB_Category.class.php";

    class OneDB_Article extends OneDB_MongoObject {

        public function __construct( &$collection, $objectID = NULL, $firstLoadDataIfObjectIDWasSet = NULL ) {

            $this->addTrigger('name', 'before', function( $newName, $oldName, $self ) {
                if (!preg_match('/^[a-z0-9\-_\s\.]+$/i', $newName))
                    throw new Exception("Invalid article name '$newName'!");
            });
            
            $this->addTrigger('type', 'before', function( $newType, $oldType, $self ) {
                if ( $newType != $oldType && !empty($newType) )
                    $self->import(
                        dirname(__FILE__) . DIRECTORY_SEPARATOR .
                        "plugins" . DIRECTORY_SEPARATOR .
                        "core.OneDB_Article.class" . DIRECTORY_SEPARATOR . 
                        "OneDB_Article_plugin_$newType.class.php"
                    );
            });
            
            $this->addTrigger('icon', 'after', function( $iconURL, $oldIconURL, $self ) {
                
                if ( strlen( "$iconURL" ) && 
                     preg_match( '/onedb(\([a-f0-9]+\))?(:|\/)picture(\(([a-z0-9=,]+)?\))?:([^*]+)$/', $iconURL, $matches ) 
                ) {
                    $fileID = $matches[5];
                    $theFile = $self->_collection->db->articles->findOne(
                        array(
                            "_id" => MongoIdentifier( $fileID )
                        )
                    );
                    if ($theFile) {
                        if ( isset( $theFile['mime'] ) && !empty( $theFile['mime'] ) ) {
                            if (preg_match('/^video(\/|$)/', $theFile['mime'] ))
                                $self->iconIsVideo = TRUE;
                            else
                                $self->iconIsVideo = NULL;
                        } else
                            $self->iconIsVideo = NULL;
                    } else
                        $self->iconIsVideo = NULL;
                } else
                    $self->iconIsVideo = NULL;
                
                return TRUE;
            });
            
            parent::__construct( $collection, $objectID, $firstLoadDataIfObjectIDWasSet );
            
            /* Sphinx Search Engine hooks */
            $this->addEventListener('create', function( $self ) {
                $sphinx = $self->_collection->db->onedb->sphinxSearch();
                if ($sphinx)
                    $sphinx->create( $self->_id );
            } );
            
            $this->addEventListener('update', function( $self ) {
                $sphinx = $self->_collection->db->onedb->sphinxSearch();
                if ($sphinx)
                    $sphinx->update( $self->_id );
            } );
            
            $this->addEventListener('delete', function( $self ) {
                $sphinx = $self->_collection->db->onedb->sphinxSearch();
                if ($sphinx) {
                
                    $_sphinxID = $self->_sphinxID;
                    
                    if ($_sphinxID) {
                        $sphinx->delete( $_sphinxID );
                    }
                }
                
                /* If we have a order defined in parent category, we unlink ourselves from the order of the parent */
                try {
                
                    $sortOrders = array('sortOrder');
                    
                    $otherOrders = $self->_collection->db->onedb->registry()->{"Plugin.Order.ItemsList"};
                    
                    if ( !empty( $otherOrders ) ) {
                        $otherOrders = @json_decode( $otherOrders, TRUE );
                        if ( is_array( $otherOrders ) && count($otherOrders) ) {
                            foreach ($otherOrders as $orderName ) {
                                if ( is_array( $orderName ) && isset( $orderName['id'] ) ) {
                                    $sortOrders[] = $orderName['id'];
                                }
                            }
                        }
                    }
                
                    /* Find all items from database who have an item in their list with the id of the current document */
                    
                    $documentID = "$self->_id";
                    
                    foreach ($sortOrders as $orderPropertyName ) {
                        
                        $self->_collection->db->onedb->categories(array(
                            "$orderPropertyName"=> array(
                                '$elemMatch' => array(
                                    'id' => $documentID
                                )
                            )
                        ))->each( function( &$category ) use( $orderPropertyName, $documentID ) {
                            
                            $obj = $category->{"$orderPropertyName"};
                            
                            if ( is_array( $obj ) ) {
                                for ( $i=0, $n = count($obj); $i<$n; $i++ ) {
                                    if ( is_array( $obj[$i] ) && isset($obj[$i]['id']) && $obj[$i]['id'] == $documentID ) {
                                        array_splice( $obj, $i, 1 );
                                        $category->{"$orderPropertyName"} = $obj;
                                        $category->save();
                                        break;
                                    }
                                }
                            }
                            
                        } );
                    
                    }
                } catch (Exception $e) {
                    // Error deleting myself from parent sortOrder;
                }
            } );

        }
        
        public function getParent() {
            if ($this->_parent !== NULL) {
                $db = $this->_collection->db;
                return new OneDB_Category( $db->categories, $this->_parent );
            } else return NULL;
        }
        
        public function getPath( $escape = TRUE ) {
            $parentCategory = $this->getParent();
            return $parentCategory === NULL ? 
                "/" . ( $escape ? urlencode( $this->name ) : $this->name ) : 
                $parentCategory->getPath( $escape ) . ( $escape ? urlencode( $this->name ) : $this->name );
        }
        
        public function paste( &$destinationCategory, $operation ) {
            if ($destinationCategory->isChildOf( $this->_id ))
                throw new Exception("Cannot paste: Destination is a child of source!");

            switch ($operation) {
                case 'cut':
                    $this->_parent = $destinationCategory->_id;
                    $this->save();
                    break;
                default:
                    throw new Exception("Invalid paste operation: $operation");
                    break;
            }
        }
    }

?>