<?php
    
    class OneDB_Object_View extends Object {
        
        private $_widget = NULL;
        private $_object = NULL;
        
        public function init( $viewId, OneDB_Object $object ) {
            
            $this->_object = $object;
            $this->_widget = $object->server->getElementById( $viewId );
            
            if ( $this->_widget === NULL )
                throw Object( 'Exception.OneDB', 'The referenced widget does not exists anymore!' );
            
            if ( $this->_widget->type != 'Widget' )
                throw Object( 'Exception.OneDB', 'The referenced object is not of type Widget anymore!' );
        }
        
        public function run() {
            
            return $this->_widget->data->run( [ 'argument' => $this->_object ] );
            
        }
        
        public function __mux() {
            return $this->_widget->__mux();
        }
        
    }
    
?>