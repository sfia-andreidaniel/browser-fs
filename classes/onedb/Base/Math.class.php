<?php

    require_once "String.class.php";
    require_once "Number.class.php";

    class Base_Math extends Object {
        const E      = 2.718281828459045;
        const LN2    = 0.6931471805599453;
        const LN10   = 2.302585092994046;
        const LOG2E  = 1.4426950408889634;
        const LOG10E = 0.4342944819032518;
        const PI     = 3.141592653589793;
        const SQRT1_2= 0.7071067811865476;
        const SQRT2  = 1.4142135623730951;
        
        static protected $_rand_max = 0;
        
        static protected function _from($v) {
            return is_object( $v ) && $v instanceof Base_Number ? $v->valueOf() : $v;
        }
        
        static public function abs( $x ) {
            return ( new Base_Number( ) )->init( abs( self::_from( $x ) ) );
        }

        static public function acos( $x ) {
            return ( new Base_Number( ) )->init( acos( self::_from( $x ) ) );
        }

        static public function asin( $x ) {
            return ( new Base_Number( ) )->init( asin( self::_from( $x ) ) );
        }

        static public function atan( $x ) {
            return ( new Base_Number( ) )->init( atan( self::_from( $x ) ) );
        }

        static public function atan2( $x, $y ) {
            return ( new Base_Number( ) )->init( atan2( self::_from( $x ), self::_from( $y ) ) );
        }
        
        static public function ceil( $x ) {
            return ( new Base_Number( ) )->init( ceil( self::_from( $x ) ) );
        }
        
        static public function cos( $x ) {
            return ( new Base_Number( ) )->init( cos( self::_from( $x ) ) );
        }
        
        static public function exp( $x ) {
            return ( new Base_Number( ) )->init( exp( self::_from( $x ) ) );
        }
        
        static public function floor( $x ) {
            return ( new Base_Number( ) )->init( floor( self::_from( $x ) ) );
        }
        
        static public function log( $x ) {
            return ( new Base_Number( ) )->init( log( self::_from( $x ) ) );
        }
        
        static public function max( /* $x, $y, ... $n */ ) {
            $args = func_get_args();

            for ($i=0,$len=count($args);$i<$len;$i++)
                $args[ $i ] = self::_from( $args[$i] );

            return ( new Base_Number( ) )->init( call_user_func_array( 'max', $args ) );
        }
        
        static public function min( /* $x, $y, ... $n */ ) {
            $args = func_get_args();

            for ($i=0,$len=count($args);$i<$len;$i++)
                $args[ $i ] = self::_from( $args[$i] );

            return ( new Base_Number( ) )->init( call_user_func_array( 'min', $args ) );
        }
        
        static public function pow( $x, $y ) {
            return ( new Base_Number( ) )->init( pow( self::_from( $x ), self::_from( $y ) ) );
        }
        
        static public function random( ) {
            return ( new Base_Number( ) )->init( rand() / self::$_rand_max );
        }

        static public function round( ) {
            return ( new Base_Number( ) )->init( round( self::_from( $x ) ) );
        }
        
        static public function sin( ) {
            return ( new Base_Number( ) )->init( sin( self::_from( $x ) ) );
        }
        
        static public function sqrt( ) {
            return ( new Base_Number( ) )->init( sqrt( self::_from( $x ) ) );
        }
        
        static public function tan( ) {
            return ( new Base_Number( ) )->init( tan( self::_from( $x ) ) );
        }
        
        static function init() {
            self::$_rand_max = getrandmax();
        }
        
    }
    
    Base_Math::init();
    
    /* Alias class */
    class Math extends Base_Math {
    }

    Math::init();
    

?>