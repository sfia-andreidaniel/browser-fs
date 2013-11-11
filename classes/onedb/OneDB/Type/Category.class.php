<?php

    require_once __DIR__ . '/../Type.class.php';

    class OneDB_Type_Category extends OneDB_Type {
        
        public static $_isReadOnly  = FALSE;
        public static $_isContainer = TRUE;
        public static $_isLive      = FALSE;
        
        public function exportOwnProperties( array &$properties ) {
        }

        public function importOwnProperties( array $properties ) {
        }
        
        public function refresh() { }
        
        public function getChildNodes() {
            
            $out = [];
            
            $result = $this->_root->server->objects->find([
                '_parent' => $this->_root->id
            ]);
            
            foreach ( $result as $item ) {
                $out[] = Object( 'OneDB.Object', $this->_root->server, $item['_id'], $item );
            }
            
            return Object( 'OneDB.Iterator', $out, $this->_root->server );
        }
    }
    
?>