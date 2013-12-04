<?php

    class OneDB_Frontend_PageBlock extends Object {
        
        protected $_branch = NULL;
        protected $_tpl    = NULL;
        
        public function init( $branch, $tpl ) {
            
            $this->_branch = $branch;
            $this->_tpl    = $tpl;
            
        }
        
        public function add( $code ) {
            
            $this->_tpl->assign( 'value', $code );
            $this->_tpl->parse( $this->_branch );
            
        }
        
    }

?>