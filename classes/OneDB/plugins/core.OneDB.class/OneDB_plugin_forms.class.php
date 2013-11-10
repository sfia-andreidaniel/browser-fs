<?php

    require_once "OneDB.class.php";
    require_once "OneDB_Form.class.php";
    
    class OneDB_Form_Connector {
        protected $_db = false;
        
        public function __construct( &$mongoDatabase ) {
            $this->_db = $mongoDatabase;
        }
        
        public function __invoke( array $filter ) {

            $result = $this->_db->forms->find( $filter );
            
            $out = array();
            
            while ($result->hasNext()) {
                $row = $result->getNext();
                $out[] = new OneDB_Form(
                    $this->_db->forms,
                    "$row[_id]",
                    $row
                );
            }
            
            return new OneDB_ResultsNavigator(
                $out,
                $this->db,
                'Form'
            );

        }
        
        public function create( $formName, $formMethod ) {
            $form = new OneDB_Form( $this->_db->forms, NULL, NULL );

            global $__OneDB_Default_Form__;
            
            $form->extend( $__OneDB_Default_Form__ );

            $form->type = 'Form';
            $form->date = time();
            
            $form->name = $formName;
            $form->method = $formMethod;
            
            $form->save();
            
            return $form;
        }
    }
    
    class OneDB_plugin_forms extends OneDB {
        
        public function forms( $filter = NULL ) {
        
            $connector = new OneDB_Form_Connector( $this->db );
            
            if ($filter !== NULL) {
                if (is_array( $filter )) {
                    $connector = $connector->__invoke( $filter );
                } else
                    throw new Exception("OneDB_plugin_Forms:: first parameter should be an array or NULL!");
            }
            
            return $connector;
        }
        
    }

?>