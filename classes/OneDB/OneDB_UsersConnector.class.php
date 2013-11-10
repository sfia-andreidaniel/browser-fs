<?php

    require_once "OneDB.class.php";
    require_once "OneDB_User.class.php";
    require_once "OneDB_ResultsNavigator.class.php";

    class OneDB_UsersConnector {
        
        private $_svr = NULL;
        
        public function __construct( &$OneDB_Svr ) {
            $this->_svr = $OneDB_Svr;
        }
        
        /* Creates a new user in onedb users collection
         */
        public function create( array $properties = array() ) {
            global $__OneDB_Default_User__;
            
            $newOne = new OneDB_User(
                $this->_svr->db->users,
                NULL,
                NULL
            );
            
            $newOne->extend(
                $__OneDB_Default_User__
            );
            
            $newOne->extend(
                $properties
            );
            
            $newOne->type = 'User';
            $newOne->createdOn = @time();
            
            $newOne->on('create');
            
            return $newOne;
        }
    }

?>