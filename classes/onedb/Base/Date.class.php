<?php

    require_once "String.class.php";
    require_once "Number.class.php";

    class Base_Date extends Object {
    
        use ListenerInterface;
    
        protected $_format= '%a %b %e %Y %H:%M:%S UTC%z (%Z)';
        protected $_value = 0;
        
        public function __construct( ) {
            $this->_value = time();
            parent::__construct();
        }
        
        static private function _( $x ) {
            return ( is_object( $x ) && $x instanceof Object ) ? (int)$x->valueOf() : (int)$x;
        }
        
        /* Initialize the primitive value of a string */
        public function init( $value ) {
            $this->_value = strtotime( $value );
            return $this;
        }
        
        public function __toString() {
            return strftime( $this->_format, $this->_value );
        }
        
        public function toString( $format = NULL ) {
            return ( new Base_String() )->init( $format === NULL ? $this->_format : $format, $this->_value );
        }
        
        // Returns the day of the month (from 1-31)
        public function getDate() {
            return (int)strftime( '%e', $this->_value );
        }
        
        public function getDay() {
            return (int)strftime( '%w', $this->_value );
        }
        
        public function getFullYear() {
            return (int)strftime( '%Y', $this->_value );
        }
        
        public function getHours() {
            return (int)strftime( '%k', $this->_value );
        }
        
        public function getMilliseconds() {
            return 0; //always 0 in php
        }
        
        public function getMinutes() {
            return (int)strftime( '%M', $this->_value );
        }
        
        public function getMonth() {
            return (int)strftime( '%m', $this->_value );
        }
        
        public function getSeconds() {
            return (int)strftime( '%S', $this->_value );
        }
        
        public function getTime() {
            return $this->_value * 1000;
        }
        
        public function getTimezoneOffset() {
            $diff = (int)strftime( '%z', $this->_value );
            $abs  = abs( $diff ) . '';
            $abs = str_pad( $abs, 4, '0', STR_PAD_LEFT);
            
            $h = (int)substr( $abs, 0, 2 );
            $m = (int)substr( $abs, 2, 2 );
            
            return ( new Base_Number() )->init( ( ( $h * 60 ) + $m ) * ($diff < 0 ? -1 : 1) );
        }
        
        public function getYear() {
            return $this->getFullYear();
        }
        
        public function parse( $input ) {
            return strtotime( $input ) * 1000;
        }
        
        public function setDate( $x ) {
            $o = [];
            
            list( $o['hours'], $o['minutes'], $o['seconds'], $o['month'], $o['day'], $o['year'] )
                = explode(',', date('G,i,s,n,j,Y', $this->_value) );
            
            $this->_value = mktime( self::_( $o['hours'] ), self::_( $o['minutes'] ), self::_( $o['seconds'] ),
                                    self::_( $o['month'] ), self::_( $o['day'] = $x ), self::_( $o['year'] ) );
        }
        
        public function setFullYear( $x ) {
            $o = [];
            
            list( $o['hours'], $o['minutes'], $o['seconds'], $o['month'], $o['day'], $o['year'] )
                = explode(',', date('G,i,s,n,j,Y', $this->_value) );
            
            $this->_value = mktime( self::_( $o['hours'] ), self::_( $o['minutes'] ), self::_( $o['seconds'] ),
                                    self::_( $o['month'] ), self::_( $o['day'] ), self::_( $o['year'] = $x ) );
        }
        
        public function setHours( $x ) {
            $o = [];
            
            list( $o['hours'], $o['minutes'], $o['seconds'], $o['month'], $o['day'], $o['year'] )
                = explode(',', date('G,i,s,n,j,Y', $this->_value) );
            
            $this->_value = mktime( self::_( $o['hours'] = $x ), self::_( $o['minutes'] ), self::_( $o['seconds'] ),
                                    self::_( $o['month'] ), self::_( $o['day'] ), self::_( $o['year'] ) );
        }
        
        public function setMilliseconds( $x ) {
            // Nothing
        }
        
        public function setMinutes( $x ) {
            $o = [];
            
            list( $o['hours'], $o['minutes'], $o['seconds'], $o['month'], $o['day'], $o['year'] )
                = explode(',', date('G,i,s,n,j,Y', $this->_value) );
            
            $this->_value = mktime( self::_( $o['hours'] ), self::_( $o['minutes'] = $x ), self::_( $o['seconds'] ),
                                    self::_( $o['month'] ), self::_( $o['day'] ), self::_( $o['year'] ) );
        }
        
        public function setMonth( $x ) {
            $o = [];
            
            list( $o['hours'], $o['minutes'], $o['seconds'], $o['month'], $o['day'], $o['year'] )
                = explode(',', date('G,i,s,n,j,Y', $this->_value) );
            
            $this->_value = mktime( self::_( $o['hours'] ), self::_( $o['minutes'] ), self::_( $o['seconds'] ),
                                    self::_( $o['month'] = $x ), self::_( $o['day'] ), self::_( $o['year'] ) );
        }
        
        public function setSeconds( $x ) {
            $o = [];
            
            list( $o['hours'], $o['minutes'], $o['seconds'], $o['month'], $o['day'], $o['year'] )
                = explode(',', date('G,i,s,n,j,Y', $this->_value) );
            
            $this->_value = mktime( self::_( $o['hours'] ), self::_( $o['minutes'] ), self::_( $o['seconds'] = $x ),
                                    self::_( $o['month'] ), self::_( $o['day'] ), self::_( $o['year'] ) );
        
        }
        
        public function setTime( $x ) {
            $this->_value = floor( self::_( $x ) / 1000 );
        }
        
        /* Returns the primitive value of a String */
        public function valueOf() {
            return $this->_value;
        }
    }
    
    Base_Date::prototype()->defineProperty('dateFormat', [
        "get" => function() {
            return $this->_format;
        },
        "set" => function( $str ) {
            $this->_format = $str . '';
        }
    ]);
    
    Base_Date::prototype()->defineProperty('day', [
        "get" => function() {
            return $this->getDate();
        },
        "set" => function( $x ) {
            $this->setDate( $x );
        }
    ]);

    Base_Date::prototype()->defineProperty('month', [
        "get" => function() {
            return $this->getMonth();
        },
        "set" => function($x) {
            echo "set!\n";
            $this->setMonth($x);
        }
    ] );

    Base_Date::prototype()->defineProperty('year', [
        "get" => function() {
            return $this->getFullYear();
        },
        "set" => function($x) {
            $this->setFullYear($x);
        }
    ] );

    Base_Date::prototype()->defineProperty('hours', [
        "get" => function() {
            return $this->getHours();
        },
        "set" => function($x) {
            $this->setHours($x);
        }
    ] );

    Base_Date::prototype()->defineProperty('minutes', [
        "get" => function() {
            return $this->getMinutes();
        },
        "set" => function($x) {
            $this->setMinutes($x);
        }
    ] );

    Base_Date::prototype()->defineProperty('seconds', [
        "get" => function() {
            return $this->getSeconds();
        },
        "set" => function($x) {
            $this->setSeconds($x);
        }
    ] );
    
    Base_Date::prototype()->defineProperty( 'timestamp', [
        "get" => function() {
            return $this->_value;
        },
        "set" => function( $x ){
            $this->_value = (int)$x;
        }
    ] );
    
    Base_Date::prototype()->defineProperty( 'time', [
        "get" => function() {
            return $this->_value * 1000;
        },
        "set" => function( $x ){
            $this->_value = floor( $x / 1000 );
        }
    ] );
    
?>