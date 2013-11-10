<?php

    require_once "OneDB.class.php";
    require_once "OneDB_RootCategory.class.php";
    
    require_once dirname(__FILE__) . DIRECTORY_SEPARATOR .
                 ".." . DIRECTORY_SEPARATOR . 
                 "core.OneDB_ResultsNavigator.class" . DIRECTORY_SEPARATOR . 
                 "OneDB_ResultsNavigator_plugin_Category.class.php";
    
    class OneDB_plugin_rootCategory extends OneDB {
        
        public function rootCategory( ) {
            return new OneDB_ResultsNavigator_plugin_Category(
                array(
                    new OneDB_RootCategory( $this->db->categories )
                ),
                $this->db
            );
        }
        
    }
    
?>