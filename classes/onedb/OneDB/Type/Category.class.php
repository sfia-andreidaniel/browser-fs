<?php

    require_once __DIR__ . '/../Type.class.php';

    class OneDB_Type_Category extends OneDB_Type {
        
        static $_isReadOnly  = FALSE;
        static $_isContainer = TRUE;
        
        public function exportOwnProperties( array &$properties ) {
        }

        public function importOwnProperties( array $properties ) {
        }
        
        public function getChildNodes() {
            
            // return Object( 'OneDB.Iterator', [] );
            
            $out = [];
            
            $result = $this->_root->server->objects->find([
                '_parent' => $this->_root->id
            ]);
            
            foreach ( $result as $item ) {
                $out[] = Object( 'OneDB.Object', $this->_root->server, $item['_id'], $item );
            }
            
            return Object( 'OneDB.Iterator', $out );
        }
    }
    
?>