<?php
    
    /* The role of this class is to ensure the site frontend skeleton */
    
    require_once __DIR__ . '/../Template/XTemplate.class.php';
    
    class OneDB_Frontend extends Object {
        
        public    $_tpl               = NULL;
        protected $_path              = NULL;
        protected $_name              = NULL;
        protected $_blocks            = NULL;
        
        protected $_begin             = NULL;
        protected $_end               = NULL;
        
        protected $_parsed            = FALSE;
        
        protected $_text              = FALSE;
        protected $_buffer            = '';
        protected $_blocks_singletons = [];
        
        /* @param: $frontendName: <string> => a file which exists in
                   /etc/frontend/<$frontendName>.html
         */
        public function init( $frontendName ) {
            
            $this->_name = $frontendName;
            
            if ( !file_exists( $this->_path = ( __DIR__ . '/../../../etc/frontend/' . $frontendName . '.html' ) ) )
                throw Object( "Exception.Frontend", "Invalid frontend name: $frontendName " );
            else {
                $this->_path = realpath( $this->_path );
                $this->_initialize();
            }
            
            if ( !is_array( $this->_blocks ) )
                throw Object( 'Exception.Frontend', 'Failed to parse frontend blocks (expected comment "#! BLOCKS: ...")' );
            
            $this->_begin = Object( 'OneDB.Frontend.Section', 'main._begin_', $this->_tpl );
            $this->_end   = Object( 'OneDB.Frontend.Section', 'main._end_',   $this->_tpl );
        }
        
        /* The initialize function parses the frontend file, it's sections,
           and initialize the XTemplate engine
         */
        private function _initialize() {
            
            $buffer = @file_get_contents( $this->_path );
            
            if ( !is_string( $buffer ) )
                throw Object( 'Exception.Frontend', 'Failed to read frontend file named ' . $_name . '.html from etc/frontend/' );
            
            $lines = explode( "\n", $buffer );
            
            $out = [];
            
            foreach ( $lines as $line ) {
                
                if ( @( $line[0] === '#' && $line[1] === '!' ) ) {
                    // this is a frontend template commented line
                    $this->_parseComment( $line );
                } else {
                    $out[] = $line;
                }
                
            }
            
            $out = $this->_buffer = implode( "\n", $out );
            
            $this->_tpl = new Template_XTemplate ( $out );
        }
        
        private function _parseComment( $commentLine ) {
            switch ( TRUE ) {
                case preg_match( '/^\#\![\s]+blocks\:[\s]+(.*)$/i', $commentLine, $matches ) && $this->_blocks === NULL:
                    $this->_blocks = preg_split( '/[\s\,]+/', trim( $matches[1] ) );
                    break;
            }
        }
        
        public function assign( $propertyName, $propertyValue ) {
            $this->_begin->assign( $propertyName, $propertyValue );
        }
        
        public function getText() {
            
            if ( !$this->_parsed ) {
                
                $this->_begin->parse();
                $this->_end->parse();
                
                $this->_tpl->parse( 'main' );
                
                $this->_parsed = TRUE;
                
                $this->_text = $this->_tpl->out( 'main' );
            }
            
            return $this->_text;
            
        }
        
        public function __get( $propertyName ) {
            switch ( TRUE ) {
                
                case $propertyName == 'begin':
                    return $this->_begin;
                    break;
                
                case $propertyName == 'end':
                    return $this->_end;
                    break;
                
                case in_array( $propertyName, $this->_blocks ):
                    
                    return isset( $this->_blocks_singletons[ $propertyName ] )
                        ? $this->_blocks_singletons[ $propertyName ]
                        : $this->_blocks_singletons[ $propertyName ] = Object( 'OneDB.Frontend.PageBlock', 'main.' . $propertyName, $this->_tpl );
                    
                    break;
                
                default:
                    return parent::__get( $propertyName );
            }
        }
        
        public function __mux() {
            
            return [
                $this->_name,
                $this->_buffer,
                $this->_blocks
            ];
            
        }
    }
    
?>