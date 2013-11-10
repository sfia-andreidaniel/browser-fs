<?php

    require_once "OneDB.class.php";
    require_once "OneDB_TextSearch_Server.class.php";

    class OneDB_plugin_sphinxSearch extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function sphinxSearch() {
            $data = $this->db->config->findOne( array(
                'name' => 'sphinxSearch'
            ));

            if (
                $data === NULL || 
                !isset( $data['value'] ) || 
                empty( $data['value'] ) 
            ) return new OneDB_TextSearch_Server( NULL, $this );
            
            else
                
                return new OneDB_TextSearch_Server(
                    $data['value'],
                    $this
                );
        }
        
    }
    
?>