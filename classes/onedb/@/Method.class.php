<?php

    class Method {
        
        public $callable = NULL;
        public $name     = NULL;
        
        function __construct( &$callable, $funcName = 'anonymous' ) {
            if (!is_callable( $callable ) )
                throw new Exception("Argument not callable!");
            $this->callable = $callable;
            $this->name = $funcName;
        }
        
        function morph( $thisArg ) {
            return new Method( $this->callable, $this->name );
            return $out;
        }
        
        function __invoke( ) {
            return call_user_func_array( $this->callable->bind($this, $this), func_get_args() );
        }
        
        function __toString() {
            return 'function(){ <PHP_Closure> }';
        }
    }
    
?>