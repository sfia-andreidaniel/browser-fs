<?php

    class OneDB_Article_plugin_Match {
    
        protected $_ = NULL;

        public function __construct( &$that ) {
            $this->_ = $that;
        }
        
        public function __get( $propertyName ) {
            return $this->_->{$propertyName};
        }
        
        public function __set( $propertyName, $propertyValue ) {
            $this->_->{$propertyName} = $propertyValue;
        }
        
        public function __call( $methodName, $args ) {
            if (!method_exists( $this, $methodName ))
                return call_user_func_array( array( $this->_, $methodName ), $args );
            else 
                return call_user_func_array( array( $this, $methodName ), $args );
        }
        
        public function getEmbedCode( $width = 640, $height = 480 ) {
            $code = "<iframe style=\"width: $width px; height: $height px; border: none; padding: 0; margin: 0\" src=\"http://www.digi24.ro/balancer/stream/" .
                $this->_->streamerScope . "/?width=$width&heigh=$height&token=" . 
                    @file_get_contents('http://wwwdirect.digi24.ro/balancer/streamer/make_key.php') . "\"></iframe>";
            
            return $code;
        }
        
    }

?>