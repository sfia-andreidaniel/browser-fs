<?php
    
    class Base_Undefined {
        
        public static $_instance = NULL;
        
        public function __construct() {
            
        }
        
        static public function create() {
            return self::$_instance;
        }
        
        static public function is( $mixed ) {
            return $mixed instanceof Base_Undefined;
        }
    }
    
    Base_Undefined::$_instance = new Base_Undefined();
    
?>