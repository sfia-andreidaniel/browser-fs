<?php

    require_once "OneDB_ResultsNavigator.class.php";
    require_once "OneDB_Article.class.php";
    
    class OneDB_ResultsNavigator_plugin_Category extends OneDB_ResultsNavigator {
        
        public function __construct( $items, &$server, $navigatorType = 'Category' ) {
            parent::__construct( $items, $server, $navigatorType );
        }
        
        /* Returns all articles that are part of categories 
         * from this collection 
         */
        public function articles( $filters = array(), $orderBy = NULL, $limit = NULL, $fields = NULL ) {
            if (!count($this->_items))
                return new OneDB_ResultsNavigator( array(), $this->_svr, 'Article' );

            $idList = array();
            
            $virtualCategories = array();

            foreach ($this->_items as $item) {
                if ($item->isVirtual !== TRUE)
                    $idList[] = @MongoIdentifier($item->_id);
                else 
                    $virtualCategories[] = $item;
            }
            
            /* Obtain articles from physical categories ( $category->isVirtual !== TRUE) */
                
            $filters["_parent"] = array(
                '$in' => $idList
            );
            
            
            $results = $this->_svr->db->articles->find($filters, $fields === NULL ? array() : $fields );
            
            if ($orderBy !== NULL && is_array( $orderBy ))
                $results = $results->sort( $orderBy );
                
            $added = 0;
            
            //Apply limit to results set
            if ($limit !== NULL && $limit > 0)
                $results = $results->limit( $limit );
            
            $out = array();
            
            while ($results->hasNext()) {
                
                $result = $results->getNext();
                
                $out[] = new OneDB_Article(
                    $this->_svr->db->articles,
                    $result['_id'],
                    $result
                );
                
                $added++;
            }
            
            /* Obtain articles from virtual categories */
            
            foreach ($virtualCategories as $item) {
            
                if ($limit !== NULL && $limit > 0 && $added >= $limit)
                    continue;
                
                $collection = $item->getCollection();
                
                if ($collection === NULL)
                    $collection = $this->_svr->db->articles;
                
                $ownFilter = $item->applyOwnFilter( $filters );
                $ownFilter = OneDB_ResolveChildOf( $ownFilter, $collection->db->onedb, '_parent' );
                
                $results = $collection->find(
                    $ownFilter,
                    $fields === NULL ? array() : $fields
                );
                
                if (!isset( $ownFilter['_parent'] ) ) {
                    $ownFilter['_parent'] = $item->_id;
                }
                
                $virtualCategoryMaxDocuments = $item->maxDocuments;
                $virtualCategoryMaxDocuments = 
                    is_integer( $virtualCategoryMaxDocuments ) && 
                    $virtualCategoryMaxDocuments > 0 ? $virtualCategoryMaxDocuments : NULL;
                
                if ($orderBy !== NULL && is_array( $orderBy))
                    $results = $results->sort( $orderBy );
                
                if ($virtualCategoryMaxDocuments === NULL) {
                    if ($limit !== NULL && $limit > 0)
                        $results = $results->limit( $limit - $added );
                } else {
                    $results = $results->limit( 
                        ( $limit !== NULL && $limit > 0) ? 
                            min( $virtualCategoryMaxDocuments, $limit - $added ) : 
                            $virtualCategoryMaxDocuments 
                    );
                }
                
                while ($results->hasNext()) {
                    $result = $results->getNext();
                    $out[] = new OneDB_Article(
                        $collection,
                        $result['_id'],
                        $result
                    );
                    $added++;
                }
            }
            
            /* End of obtaining articles from virtual categories */
            
            return new OneDB_ResultsNavigator( $out, $this->_svr, 'Article' );
            
        }
        
        /* Returns the parents of categories that
         * are part of this collection if they
         * are not equal to NULLs (null results are excluded)
         */
        
        public function getParent() {
            if (!count( $this->_items )) {
                return new OneDB_ResultsNavigator( array(), $this->_svr, 'Category' );
            }
            
            $out = array();
            
            foreach ($this->_items as $item) {
                $p = $item->getParent();
                if ($p !== NULL) 
                    $out[] = $p;
            }
            
            return new OneDB_ResultsNavigator( $out, $this->_svr, 'Category' );
        }
        
        /* Returns all direct category children for the
         * items from this collection
         */
        
        public function getChildren( $orderBy = NULL ) {
            $out = array();
            
            for ($i=0, $len=count($this->_items); $i<count($this->_items); $i++) {
                $out = array_merge( $out, $this->_items[$i]->getChildren( $orderBy ) );
            }
            
            return new OneDB_ResultsNavigator( $out, $this->_svr, 'Category' );
        }
    }

?>