<?php

    require_once "OneDB_Mongo.inc.php";

    class OneDB_DatabaseExtender {
        
        protected $_db = NULL;
        
        public function __construct( &$db ) {
            $this->_db = $db;
        }

        public function nameSpaces() {
            return Mongo_NameSpaces( $this->_db );
        }
        
        public function dataSize() {
            return Mongo_GetStorage( $this->_db );
        }
        
        public function repair() {
            return Mongo_RepairDatabase( $this->_db );
        }
        
        public function export() {
            
        }
        
        public function exec( $code ) {
            return Mongo_Exec( $this->_db, $code );
        }
    }

?>