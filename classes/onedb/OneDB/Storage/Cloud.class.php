<?php
    
    require_once __DIR__ . '/../Storage.class.php';
    
    class OneDB_Storage_Cloud extends OneDB_Storage {
        
        public function unlinkFile( $fileId ) {
            return "file $fileId was unlinked";
        }
        
        
    }
    
    OneDB_Storage_Cloud::prototype()->defineProperty( 'name', [
        
        "get" => function() {
            return $this->_name;
        }
        
    ]);
    
?>