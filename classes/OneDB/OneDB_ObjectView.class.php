<?php

    require_once dirname(__FILE__ ) . DIRECTORY_SEPARATOR . "OneDB_Category.class.php";
    require_once dirname(__FILE__ ) . DIRECTORY_SEPARATOR . "OneDB_Article.class.php";

    class OneDB_ObjectView {
        
        protected $_object = NULL;
        protected $_id     = NULL;
        protected $_parent = NULL;
        
        public function __construct( $OneDB_MongoObject ) {
            $this->_object = $OneDB_MongoObject;

            $this->_id = $this->_object->_id;
            
            if (empty( $this->_id ))
                throw new Exception("Cannot access object view: Object _id is empty. Please save object first");
            
            $this->_parent = $this->_object->_parent;
        }
        
        private function __createMongo( &$collection, $mongoObject ) {
            if (empty( $mongoObject ) || !is_array( $mongoObject ))
                return NULL;
            return new OneDB_Category( $collection, $mongoObject['_id'], $mongoObject );
        }
        
        public function __get( $propertyName ) {
            /* We address the views as (category|item).(viewName) */
            if (empty( $propertyName ))
                throw new Exception("Cannot access an empty viewName");
            
            $parts = explode( '.', $propertyName );
            
            if (count($parts) < 2)
                throw new Exception("Illegal view name. Please use notation viewType.viewName (e.g: category.myView)");
            
            $viewType = reset( $parts );
            $viewName = implode('.', array_slice( $parts, 1 ) );
            
            $_db = $this->_object->_collection->db;
            
            $_collection = $this->_object->_collection;
            
            //Guess ... :)
            $_isCategory  = substr( strrev( "$_collection" ), 0, 11 ) == "seirogetac.";
            
            $node   = $_isCategory ? $this->_object : $this->__createMongo( $_db->categories, $_db->categories->findOne( array( "_id" => $this->_parent ) ) );
            
            if ($node === NULL)
                throw new Exception("Could not find object view rootNode search");
            
            while( $node ) {
                
                $views = $node->views;
                
                if (is_array( $views )) {
                    if (isset( $views[ $viewType ] ) && is_array( $views[ $viewType ] ) ) {
                        if (isset( $views[ $viewType ][ $viewName ] ) && is_array( $views[ $viewType ][ $viewName ] ) ) {
                            /* We found the view widget element */
                            
                            $view = new OneDB_Article( $_db->articles, $views[ $viewType ][ $viewName ][ "widgetId" ] );
                            return $view->setEnv( array(
                                "argument" => $this->_object
                            ) );
                            
                        }
                    }
                }
                
                $node = $this->__createMongo( $_db->categories, $_db->categories->findOne( array( "_id" => $node->_parent ) ) );
            }
            
            throw new Exception("View ($viewType).`$viewName` not found");
        }

    }

?>