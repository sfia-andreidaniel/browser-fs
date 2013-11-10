<?php

    class OneDB_Article_plugin_Layout {
    
        protected $_ = NULL;
        
        public function __construct( &$that ) {
        
            global $__OneDB_Default_Layout__;
        
            $this->_ = $that;
            
        }
        
        public function __get( $propertyName ) {
            return $this->_->{$propertyName};
        }
        
        public function __set( $propertyName, $propertyValue ) {
            $this->_->{$propertyName} = $propertyValue;
        }
        
        public function items() {
            $items = $this->_->items;
            
            if (!is_array( $items ))
                return array();
            
            $maxItems = (int)$this->_->maxItems;
            
            $maxItems = $maxItems < 0 ? 0 : $maxItems;
            
            if (!$maxItems)
                return array();
            
            $idList = array_slice( $items, 0, $maxItems );
            
            $orderTable      = array();
            $articlesTable   = array();
            $categoriesTable = array();
            
            $out = array_fill( 0, $maxItems, NULL );
            
            
            /* Compute an order matrix + determine item types */
            for ($i=0, $len=count($idList); $i<$len; $i++) {
                
                $orderTable[ $idList[$i]['id'] ] = array(
                    'order' => $i,
                    'type'  => $tmpType = $idList[$i]['type']
                );
                
                if (preg_match('/^category(\/|$)/', $tmpType))
                    $categoriesTable[] = MongoIdentifier( $idList[$i]['id'] );
                else
                    $articlesTable[] = MongoIdentifier( $idList[$i]['id'] );
            }
            
            /* Search through database... */
            
            $db = $this->_->_collection->db;
            
            if ( count( $articlesTable ) ) {
            
                $cursor = $db->articles->find(
                    array(
                        '_id' => array(
                            '$in' => $articlesTable
                        )
                    )
                );
            
                while ($cursor->hasNext()) {
                    
                    $row = $cursor->getNext();
                    $row_id = "$row[_id]";
                    
                    $out[ $orderTable[ $row_id ]['order'] ] = new OneDB_Article(
                        $db->articles,
                        $row['_id'],
                        $row
                    );
                    
                }
            
            }
            
            if ( count( $categoriesTable ) ) {
                
                $cursor = $db->categories->find(
                    array(
                        '_id' => array(
                            '$in' => $categoriesTable
                        )
                    )
                );
            
                while ($cursor->hasNext()) {
                    $row = $cursor->getNext();
                    $row_id = "$row[_id]";
            
                    $out[ $orderTable[ $row_id ]['order'] ] = new OneDB_Category(
                        $db->categories,
                        $row['_id'],
                        $row
                    );
                }
            }
            
            $out2 = array();
            
            foreach ($out as $value)
                if (NULL !== $value)
                    $out2[] = $value;
            
            if (!isset($db->onedb))
                throw new Exception("No OneDB link found in passed database handle!");
            
            return new OneDB_ResultsNavigator(
                $out2,
                $db->onedb,
                'Generic'
            );
        }
        
        public function __call( $methodName, $args ) {
            if (!method_exists( $this, $methodName ))
                return call_user_func_array( array( $this->_, $methodName ), $args );
            else 
                return call_user_func_array( array( $this, $methodName ), $args );
        }
        
    }

?>