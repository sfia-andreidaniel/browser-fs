<?php

    require_once "OneDB.class.php";
    require_once "OneDB_Category.class.php";
    require_once "OneDB_Tree.class.php";
    require_once "OneDB_ResultsNavigator.class.php";

    class OneDB_CategoryConnector {
        
        private $_svr = NULL;
        
        public function __construct( &$OneDB_Svr ) {
            $this->_svr = $OneDB_Svr;
        }
        
        public function getCategoryByPath( $path, $pathComponent = 'name', $pathSeparator = '/' ) {
            $parentID = NULL;
            
            $path = trim( $path, "$pathSeparator \t\r\n" );
            
            if (!strlen($path))
                return NULL;
            
            $path = explode($pathSeparator, $path);
            
            $sofar = array();
            
            foreach ($path as $part) {
                $category = $this->_svr->db->categories->findOne(
                    array(
                        $pathComponent => $part,
                        "_parent"      => $parentID
                    ),
                    array(
                        "_id"          => 1,
                        "_parent"      => 1,
                        $pathComponent => 1
                    )
                );
                
                if (NULL === $category) {
                    throw new Exception("OneDB::getCategoryByPath: `$part` not found: " . implode('', $sofar) . $part . " in parent '$parentID'");
                }
                
                $parentID = $category['_id'];
                
                $sofar[] = $category[ $pathComponent ] . $pathSeparator;
            }
            
            return $parentID;
        }
        
        private function getInnerCategories( OneDB_Tree &$category ) {
        
            $results = $this->_svr->db->categories->find(
                array(
                    "_parent" => $category->_id
                )
            );
        
            while ($results->hasNext()) {
                
                $result = $results->getNext();
                
                $item = new OneDB_Tree(
                    new OneDB_Category(
                        $this->_svr->db->categories,
                        $result['_id'],
                        $result
                    )
                );
                
                $this->getInnerCategories( $item );
                
                $category->push( $item );
            }
            
        }
        
        public function getCategoriesBySelector( $selector ) {
        
            $selectorComponent = '/^(\/|[a-z\d\-_\s\/\.]+\/){0,100}([\s]+(>)?([\s]+)?(\*))?(([\s]+)?,)?/i';

            $selector = trim( $selector );
            
            $selectorIndex = 0;
            
            $selectors = array();
            
            do {
            
                $selectorIndex++;
            
                if (preg_match( $selectorComponent, $selector, $matches )) {
                    
                    $path = @$matches[1] ? $matches[1] : "/";
                    
                    $multiple = empty( $matches[2] ) ? FALSE : TRUE;
                    
                    $deep = $multiple ? ( $matches[3] == '>' ? FALSE : TRUE ) : FALSE;
                    
                    $next = @$matches[6] !== NULL && @trim($matches[6]) == ',' ? TRUE : FALSE;
                    
                    $selectors[] = array(
                        'path' => $path,
                        'multiple' => $multiple,
                        'deep' => $deep
                    );
                    
                    $selector = trim( substr( $selector, strlen( $matches[0] )) );
                }
                else throw new Exception("Invalid category selector '$selector', index: $selectorIndex");
            
            } while ($next);
            
            $out = array();
            
            foreach ($selectors as $selector) {
            
                if (!($selector['path'] == '/' && $selector['multiple'] === FALSE)) {
            
                    $rootCategoryID = $this->getCategoryByPath( $selector['path'] );
                
                    if ($selector['multiple'] === FALSE) {
                    
                        if ($rootCategoryID !== NULL) {
                            $out[] = new OneDB_Tree(
                                new OneDB_Category(
                                    $this->_svr->db->categories,
                                    $rootCategoryID
                                )
                            );
                        }
                    
                    } else {
                
                        $categories = $this->_svr->db->categories->find(
                            array(
                                "_parent" => $rootCategoryID === NULL ? NULL : MongoIdentifier( $rootCategoryID )
                            )
                        );
                    
                        while ($categories->hasNext()) {
                        
                            $category = $categories->getNext();
                        
                            $item = new OneDB_Tree(
                                new OneDB_Category(
                                    $this->_svr->db->categories,
                                    $category['_id'],
                                    $category
                                )
                            );
                        
                            if ($selector['deep'] == TRUE) {
                                $this->getInnerCategories(
                                    $item,
                                    TRUE
                                );
                            }
                        
                            $out[] = $item;
                        
                        }
                    
                    }
            
                } else {
                    $out[] = $this->_svr->rootCategory()->get(0);
                }
            }

            return new OneDB_ResultsNavigator( $out, $this->_svr, 'Category' );
        }
        
        public function categories( $filters = array(), $orderBy = NULL ) {
            if (!count($filters))
                throw new Exception("No filters were specified!");

            $filters = OneDB_resolveChildOf( $filters, $this->_svr, '_id' );

            if (isset($filters['selector'])) {
                if (count($filters) > 1)
                    throw new Exception("If you specify a selector, you cannot specifiy aditional filters ant vice-versa");
                else 
                    return $this->getCategoriesBySelector( $filters['selector'] );
            }
            
            $categories = $this->_svr->db->categories->find(
                $filters
            );
            
            if ($orderBy !== NULL && is_array( $orderBy ))
                $categories = $categories->sort( $orderBy );
            
            $out = array();
            
            while ($categories->hasNext()) {
                        
                $category = $categories->getNext();
                
                $item = new OneDB_Category(
                    $this->_svr->db->categories,
                    $category['_id'],
                    $category
                );
                
                $out[] = $item;
                    
            }
            
            return new OneDB_ResultsNavigator( $out, $this->_svr, 'Category' );
        }
    }

?>