<?php

    require_once "OneDB.class.php";
    require_once "OneDB_Article.class.php";

    class OneDB_plugin_articles extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        /* Finds articles from database, based on a filter
         */
        
        private function getVirtualCategoriesList( $filter ) {
            if (!isset($filter['_parent']))
                return array();

            $virtualCategoriesList = 
                $this->categories( array(
                    "_parent" => $filter['_parent'],
                    "isVirtual" => TRUE
                ) );
            
            return $virtualCategoriesList;
        }
        
        public function articles( $filter, $orderBy = NULL, $limit = NULL, $fields = NULL ) {
        
            $filter = OneDB_resolveChildOf( $filter, $this, '_parent' );
        
            $virtualCategoriesList = array();
            $out = array();
            $added = 0;
            
            $returnFields = $fields === NULL ? array() : $fields;
            
            if (defined( "ONEDB_SKIP_FIELDS" ) ) {
                $arr = explode(',', ONEDB_SKIP_FIELDS );
                foreach ($arr as $k) {
                    $k = trim( $k );
                    if (strlen( $k )) {
                        $returnFields[ "$k" ] = FALSE;
                    }
                }
            }
            
            /* Fetch the items from phisical categories */
            $result = $this->db->articles->find(
                $filter,
                $returnFields
            );
            
            if ($orderBy !== NULL && is_array( $orderBy ))
                $result = $result->sort( $orderBy );
            
            if ($limit !== NULL && is_integer($limit) && $limit > 0)
                $result = $result->limit( $limit );
            
            while ($result->hasNext()) {
            
                $article = $result->getNext();
                
                $out[] = new OneDB_Article(
                    $this->db->articles,
                    "$article[_id]",
                    $article
                );
                
                $added++;
            }

            /* Fetch the items from virtual categories */
            
            foreach ($virtualCategoriesList as $category) {
                
                if (is_integer( $limit ) && $limit > 0 && $added >= $limit)
                    break;
                
                $virtualCategoryMaxDocuments = $category->maxDocuments;
                $virtualCategoryMaxDocuments =
                    is_integer( $virtualCategoryMaxDocuments ) &&
                    $virtualCategoryMaxDocuments > 0 ? $virtualCategoryMaxDocuments : NULL;
                
                $collection = $category->getCollection();
                $collection = $collection === NULL ? $this->db->articles : $collection;
                
                $result = $collection->find(
                    $category->applyOwnFilter( $filter ),
                    $fields
                );
                
                if ($orderBy !== NULL && is_array( $orderBy ))
                    $result = $result->sort( $orderBy );

                if ($virtualCategoryMaxDocuments === NULL) {
                    if ($limit !== NULL && is_integer($limit) && $limit > 0)
                        $result = $result->limit( $limit - $added );
                } else {
                    $result = $result->limit(
                        ( $limit !== NULL && $limit > 0) ?
                            min( $virtualCategoryMaxDocuments, $limit - $added ) :
                            $virtualCategoryMaxDocuments
                        );
                }

                while ($results->hasNext()) {
                    $article = $result->getNext();
                    $out[] = new OneDB_Article(
                        $this->db->articles,
                        "$article[_id]",
                        $article
                    );
                    $added++;
                }
            
            }
            
            /* End of fetching items from virtual categories */
            
            return new OneDB_ResultsNavigator(
                $out,
                $this->db,
                'Article'
            );
        }
        
    }
    
?>