<?php

    require_once "OneDB_MongoObject.class.php";
    require_once "OneDB_Article.class.php";

    /* We cache the getPath() method results here in format $categoryID => $path */
    $__OneDB_Category_Path_Cache__ = array();
    $__OneDB_Category_UnescapedPath_Cache__ = array();

    class OneDB_Category extends OneDB_MongoObject {
        
        public function __construct( &$collection, $objectID = NULL, $firstLoadDataIfObjectIDWasSet = NULL ) {

            $this->addTrigger("name", "before", function( $newName, $oldName, $self ) {
                if (!preg_match('/^[a-z0-9\-_\s\.]+$/i', $newName))
                    throw new Exception("Invalid category name '$newName'!");
                $newName = str_replace('..', '.', $newName );
                $self->url = OneDB_SeoURL( $newName );
            });
        
            $this->addTrigger("type", "before", function( $newType, $oldType, $self ) {
        
                if ($newType != $oldType && !empty($newType)) {
                    $self->import(
                        dirname(__FILE__) . DIRECTORY_SEPARATOR . 
                        "plugins" . DIRECTORY_SEPARATOR .
                        "core.OneDB_Category.class" . DIRECTORY_SEPARATOR . 
                        "OneDB_Category_plugin_$newType.class.php"
                    );
                }
        
            });
            
            parent::__construct( $collection, $objectID, $firstLoadDataIfObjectIDWasSet );

        }
        
        public function __scanSub( $func ) {
            $children = $this->getChildren();
            for ($i=0, $len=count($children); $i<$len; $i++) {
                $func( $children[$i], $func);
            }
        }
        
        public function getPath( $escape = TRUE ) {
        
            global $__OneDB_Category_Path_Cache__;
            global $__OneDB_Category_UnescapedPath_Cache__;
            
            if ($escape) {
                if (isset($__OneDB_Category_Path_Cache__["$this->_id"]))
                    return $__OneDB_Category_Path_Cache__["$this->_id"];
            } else {
                if (isset($__OneDB_Category_UnescapedPath_Cache__["$this->_id"]))
                    return $__OneDB_Category_UnescapedPath_Cache__["$this->_id"];
            }
        
            if (($myParent = $this->getParent()) === NULL) {
                $path = ( $escape ? urlencode($this->name) : $this->name ) . "/";
            } else
                $path = $myParent->getPath( $escape ) . ( $escape ? urlencode($this->name) : $this->name ). "/";
            
            $path = trim($path, "/") . "/";
            $path = $path == "/" ? "/" : "/$path";
            
            if ($escape)
                $__OneDB_Category_Path_Cache__["$this->_id"] = $path;
            else
                $__OneDB_Category_UnescapedPath_Cache__["$this->_id"] = $path;
            
            return $path;
        }
        
        public function getParent() {
            if ($this->_parent !== NULL) {
                return new OneDB_Category( $this->_collection, $this->_parent );
            } else return NULL;
        }
        
        public function getChildren( $orderBy = NULL ) {
        
            if ($this->isVirtual !== TRUE) {

                $results = $this->_collection->find(array(
                    '_parent' => $this->_id
                ));
            
                if ($orderBy !== NULL && is_array( $orderBy ))
                    $results = $results->sort( $orderBy );
            
                $out = array();
            
                while ($results->hasNext()) {
                    $row = $results->getNext();
            
                    $out[] = new OneDB_Category( $this->_collection, $row['_id'], $row );
                }
                
            } else {
            
                //No children under a virtual Category
                $out = array();
                
            }
            
        
            return $out;
        }
        
        public function createCategory( $type = NULL ) {
        
            /* if ($this->isVirtual === TRUE)
                throw new Exception("This is a virtual category and cannot have physical sub-items");
            */
        
            $category = new OneDB_Category( $this->_collection, NULL, NULL );
        
            global $__OneDB_Default_Category__;
        
            $category->extend(
                $__OneDB_Default_Category__
            );
        
            $category->_parent = $this->_id;
            
            $category->type = $type;
            $category->date = time();
        
            return $category;
        }
        
        public function createSearchCategory( ) {
            global $__OneDB_Default_SearchCategory__;
            
            $newly = $this->createCategory( "SearchCategory" );
            $newly->extend( $__OneDB_Default_SearchCategory__ );
            
            $newly->type = "SearchCategory";
            
            return $newly;
            
        }
        
        public function createJSONWebserviceCategory( ) {
            global $__OneDB_Default_JSONWebserviceCategory__;
            
            $newly = $this->createCategory( "JSONWebserviceCategory" );
            $newly->extend( $__OneDB_Default_JSONWebserviceCategory__ );
            
            $newly->type = "JSONWebserviceCategory";
            
            return $newly;
        }
        
        public function createArticle( $type = NULL ) {
        
            if ($this->isVirtual === TRUE)
                throw new Exception("This is a virtual category and cannot have physical sub-items");
        
            global $__OneDB_Default_Article__;
        
            $article = new OneDB_Article(
                $this->_collection->db->articles,
                NULL,
                NULL
            );
        
            $article->extend(
                $__OneDB_Default_Article__
            );
        
            $article->type = $type;
            $article->date = time();
            $article->_parent = $this->_id;
            
            if ($type != NULL && $type != 'Article' && 
                isset($GLOBALS[$globalName = "__OneDB_Default_" . $type . "__"])
            ) $article->extend( $GLOBALS[$globalName] );
            
            return $article;
            
        }
        
        public function delete() {
            //We must first obtain all sub-categories together with all their
            //sub-articles, and delete them first
            
            if ($this->isVirtual !== TRUE) {
                $children = $this->getChildren();
            
                foreach ($children as $child)
                    $child->delete();
                
                $articles = $this->_collection->db->articles->remove(
                    array(
                        "_parent" => $this->_id
                    ),
                    array(
                        "safe" => TRUE
                    )
                );
            }
            
            parent::delete();
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
        
        public function articles( $filter = array(), $orderBy = array(), $limit = NULL, $fields = NULL ) {
            
            $iVirtual = $this->isVirtual;
            
            $collection = empty( $iVirtual ) ? 
                $this->_collection->db->articles :
                $this->getCollection();
            
            $filter['_parent'] = $this->_id;

            $result = $collection->find(
                $filter,
                $fields === NULL ? array() : $fields
            );
            
            if (count(array_values( $orderBy )))
                $result = $result->sort( $orderBy );
            
            if ($limit !== NULL)
                $result = $result->limit( $limit );
            
            $out = array();
            
            while ($result->hasNext()) {
                $row = $result->getNext();
                $out[] = new OneDB_Article( $collection, $row['_id'], $row );
            }
            
            return new OneDB_ResultsNavigator(
                $out,
                $this->_collection->db,
                "Article"
            );
        }
        
        /* Return a kind-of tag-cloud formed by the properties of the items from
           that category.
           
           For example: $this->_cloud( 'keywords' ) returns the keywords tag cloud
                        $this->_cloud( 'tags' )     returns the tag property tag cloud.
         */
        
        public function _cloud( $key ) {

            $key = (array)$key;
            
            if (!count($key))
                return array();
            
            $just = array();
            $out  = array();
            $out1 = array();
            
            
            foreach ($key as $cloudItem) {
                if (!in_array( $cloudItem, array( 'tags', 'keywords' ) ) )
                    throw new Exception("Invalid _cloud key '$cloudItem'");
                $just[ "$cloudItem" ] = 1;
                $out [ "$cloudItem" ] = array();
                $out1[ "$cloudItem" ] = array();
            }

            /* Collection for storing articles */
            $iVirtual = $this->isVirtual;
            
            $collection = empty( $iVirtual ) ? 
                $this->_collection->db->articles :
                $this->getCollection();
            
            $results = $collection->find(
                array(
                    "_parent" => $this->_id
                ),
                $just
            );
            
            while ($results->hasNext()) {
                $row = $results->getNext();
                
                foreach ($key as $_key)
                    if (array_key_exists( $_key, $row ) && is_array( $row[ $_key ] ))
                        foreach ($row[ $_key ] as $i)
                            $out["$_key"]["$i"] = array_key_exists( $i, $out["$_key"] ) ? $out["$_key"]["$i"] + 1 : 1;
            }
            
            foreach ($key as $_key) {
                foreach (array_keys( $out["$_key"] ) as $i) {
                    $out1["$_key"][] = array(
                        "name" => $_key,
                        "count" => $out["$_key"]["$i"]
                    );
                }
            }
            
            return $out1;
        }
        
    }

?>