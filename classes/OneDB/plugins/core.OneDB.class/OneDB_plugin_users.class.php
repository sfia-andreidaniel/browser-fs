<?php

    require_once "OneDB.class.php";
    require_once "OneDB_User.class.php";

    class OneDB_plugin_users extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function users( $filter, $orderBy = NULL ) {
        
            $out = array();
            
            $result = $this->db->users->find(
                $filter
            );
            
            if ($orderBy !== NULL && is_array( $orderBy ))
                $result = $result->sort( $orderBy );
            
            while ($result->hasNext()) {
            
                $user = $result->getNext();
                
                $out[] = new OneDB_User(
                    $this->db->users,
                    "$user[_id]",
                    $user
                );
                
            }
        
            return new OneDB_ResultsNavigator(
                $out,
                $this->db,
                'User'
            );
        }
        
    }
    
?>