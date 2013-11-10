<?php

    require_once "Prototype.class.php";

    class Proto  {
        
        private static $instances = array();
        
        public function __construct( ) {
            throw new Exception("This class is ment to be use only as static");
        }
        
        public static function create( $prototypeClassName ) {
            
            if ( isset( self::$instances[ $prototypeClassName ] ) )
                throw new Exception("A prototype of $prototypeClassName has allready been declared!");
            
            self::$instances[ $prototypeClassName ] = new Prototype( $prototypeClassName );
        }
        
        public static function get( $prototypeClassName ) {
            $prototypeClassName = str_replace('.', '_', $prototypeClassName );
            if ( !isset( self::$instances[ $prototypeClassName ] ) )
                self::create( $prototypeClassName );
            return self::$instances[ $prototypeClassName ];
        }
        
        public static function dump() {
            return array_keys( self::$instances );
        }
    }
    
    function Prototype( $prototypeClassName ) {
        return Proto::get( str_replace( '_', '.', $prototypeClassName ) );
    }

?>