<?php

    require_once "OneDB_ResultsNavigator.class.php";
    require_once "OneDB_Article.class.php";
    require_once "OneDB_Category.class.php";
    
    class OneDB_ResultsNavigator_plugin_Article extends OneDB_ResultsNavigator {
        
        public function __construct( $items, &$server, $navigatorType = 'Article' ) {
            parent::__construct( $items, $server, $navigatorType );
        }
        
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
    }

?>