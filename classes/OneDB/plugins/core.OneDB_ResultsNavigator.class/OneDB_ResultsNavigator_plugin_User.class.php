<?php

    require_once "OneDB_ResultsNavigator.class.php";
    
    /* OneDB Users plugin */
    
    class OneDB_ResultsNavigator_plugin_User extends OneDB_ResultsNavigator {
        
        public function __construct( $items, &$server, $navigatorType = 'User' ) {
            parent::__construct( $items, $server, $navigatorType );
        }
        
        /* Add here mass methods */
        
        public function login( $password ) {
            if ($this->length != 1)
                throw new Exception("Invalid username!");
            if ($this->_items[0]->password != md5( $password ))
                throw new Exception("Invalid password!");
            return $this;
        }
        
        public function memberOf( $groupName ) {
            $out = array();
            
            foreach ($this->_items as $user ) {
                if ($user->memberOf( $groupName ))
                    $out[] = $user;
            }
            
            return new OneDB_ResultsNavigator_plugin_User(
                $out,
                $this->_svr->db->users,
                'User'
            );
        }
    }

?>