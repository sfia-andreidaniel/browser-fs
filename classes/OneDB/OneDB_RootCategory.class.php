<?php

    require_once "OneDB.class.php";
    require_once "OneDB_Category.class.php";
    
    class OneDB_RootCategory extends OneDB_Category {
    
        function __construct( &$collection ) {
            $this->_collection = $collection;
            $this->_id = NULL;
        }
        
        function load( $bool = NULL ) {}
        function save( ) {}
        
        function __get( $propertyName ) {
            if ($propertyName == '_id')
                return NULL;
            else return parent::__get( $propertyName );
        }
        
        function __set( $propertyName, $propertyValue ) {
            throw new Exception("RootCategory is a virtual category, so no setters are supported");
        }
        
        function isChildOf( $aNode ) {
            return FALSE;
        }
    }
    
?>