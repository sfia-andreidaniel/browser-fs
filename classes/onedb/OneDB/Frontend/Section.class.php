<?php
    
    /* The OneDB.Frontend.Section class represents either a frontend template
       begin either a template end section
     */
     
    class OneDB_Frontend_Section extends Object {
        
        protected $_branch   = NULL;
        protected $_tpl      = NULL;
        
        protected $_needParse = FALSE;
        
        public function init( $branch, $tpl ) {
            $this->_branch = $branch;
            $this->_tpl    = $tpl;
        }
        
        public function parse() {
            if ( $this->_needParse )
                $this->_tpl->parse( $this->_branch );
        }
        
        /* @param type can be:
            script
            css
         */
        public function add( $type, $value, $inline = FALSE ) {
            
            switch ( $type ) {
                
                case 'script':
                    
                    $block = '_' . ( $inline ? 'inline' : '' ) . 'script_';
                    $this->_tpl->assign( 'src', $value );
                    $this->_tpl->parse( $this->_branch . '.' . $block );
                    
                    $this->_needParse = TRUE;
                    
                    break;
                    
                case 'css':

                    $block = '_' . ( $inline ? 'inline' : '' ) . 'css_';
                    $this->_tpl->assign( 'src', $value );
                    $this->_tpl->parse( $this->_branch . '.' . $block );
                    
                    $this->_needParse = TRUE;
                    
                    break;
                    
                case 'code':
                    
                    $block = '_code_';
                    $this->_tpl->assign( 'src', $value );
                    $this->_tpl->parse( $this->_branch . '.' . $block );
                    
                    $this->_needParse = TRUE;
                    
                    break;
                    
                default:
                    throw Object( 'Exception.Frontend', 'Don\'t know what type of resource ' . $type . ' is!' );
                    break;
            }
            
        }
    }
    
?>