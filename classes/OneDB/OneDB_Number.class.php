<?php

    require_once dirname(__FILE__ ) . DIRECTORY_SEPARATOR . 'OneDB_i18n.class.php';

    class OneDB_Number {
        
        private $value = 0;

        const   NaN = 'NaN';
        
        public function __construct( $aNumber ) {
            
            if ( !is_numeric( $aNumber ) ) {
                $this->value = self::NaN;
            } else
                $this->value = $aNumber * 1;
            
        }
        
        static public function get( $aNumber ) {
            return new OneDB_Number( $aNumber );
        }
        
        public function isNaN() {
            return $this->value === self::NaN;
        }
        
        public function round() {
            return $this->isNaN() ? new OneDB_Number( self::NaN ) : new OneDB_Number( round( $this->value ) );
        }
        
        public function floor() {
            return $this->isNaN() ? new OneDB_Number( self::NaN ) : new OneDB_Number( floor( $this->value ) );
        }
        
        public function abs() {
            return $this->isNaN() ? new OneDB_Number( self::NaN ) : new OneDB_Number( abs( $this->value ) );
        }
        
        public function toFixed( $decimals, $decimalPoint = '.', $thouthandsSeparator = '' ) {
            return $this->isNaN() ? self::NaN : number_format( $this->value, $decimals, $decimalPoint, $thouthandsSeparator );
        }
        
        public function value() {
            return $this->value;
        }
        
        public function toSize() {

            if ( $this->isNaN() )
                return self::NaN;

            $sizes = array('', ' Kb', ' Mb', ' Gb', ' Tb', ' Pb' );
            $unit  = 0;
            $negative = $this->isNaN() ? FALSE : $this->value < 0;
            $remain = $this->isNaN() ? 0 : $this->value;
            
            while ( $remain >= 1024 && $unit<5 ) {
                $remain /= 1024;
                $unit++;
            }
            
            $remain = new OneDB_Number( $remain );
            $remain = $remain->toFixed( 2 );
            
            
            return ( $negative ? '-' : '' ) . preg_replace('/(\.|\,)00$/', '', $remain ) . $sizes[ $unit ];

        }
        
        /* Sample @intl:
         *  array(
         *      'second' => array( 's', 's' ),
         *      'minute' => array( 'm', 'm' ),
         *      'hour'   => array( ' hour', ' hrs' ),
         *      'day'    => array( ' day',  ' days' ),
         *      'month'  => array( ' month',' months' ),
         *      'year'   => array( ' year', ' years' ),
         *      'past'   => '%time% ago',
         *      'future' => 'in %time%'
         *  );
         */
        
        public function toTime( $intl = array(), $maxUnits = 5 ) {
        
            if ( $this->isNaN() )
                return self::NaN;
        
            $intl = is_array( $intl ) ? $intl : array();
            
            $intl[ 'second' ] = isset( $intl['second'] ) && is_array( $intl['second'] ) ? $intl['second'] : array('s'     , 's'      );
            $intl[ 'minute' ] = isset( $intl['minute'] ) && is_array( $intl['minute'] ) ? $intl['minute'] : array('m'     , 'm'      );
            $intl[ 'hour'   ] = isset( $intl['hour'  ] ) && is_array( $intl['hour'  ] ) ? $intl['hour']   : array(' hour' , ' hrs'   );
            $intl[ 'day'    ] = isset( $intl['day'   ] ) && is_array( $intl['day'   ] ) ? $intl['day']    : array(' day'  , ' days'  );
            $intl[ 'month'  ] = isset( $intl['month' ] ) && is_array( $intl['month' ] ) ? $intl['month']  : array(' month', ' months');
            $intl[ 'year'   ] = isset( $intl['year'  ] ) && is_array( $intl['year'  ] ) ? $intl['year']   : array(' year' , ' years' );
            
            $intl[ 'past' ] = isset( $intl['past'] ) ? $intl['past'] : '%time% ago';
            $intl[ 'future']= isset( $intl['future'] ) ? $intl['future'] : 'in %time%';
            
            $units        = array( $intl['second'], $intl['minute'], $intl['hour'], $intl['day'], $intl['month'], $intl['year'] );
            $divisors     = array(1, 60, 3600, 86400, 2635200, 31622400);
            $me           = $remain = $this->value;
            $remain       = abs( $remain );
            $divisorIndex = 1;
            $stack        = array();
            $tries        = 0;
            $floatVal     = 0;
            $current      = NULL;
            
            while ($remain >= 1 && $tries < min( 5, $maxUnits ) ) {
                
                $divisorIndex = 1;
                $remain = round( $remain );
                
                while ( ( $remain / $divisors[ $divisorIndex ] ) >= 1 && $divisorIndex < 5 )
                    $divisorIndex++;
                
                $floatVal = $remain / $divisors[ $divisorIndex - 1 ];
                $stack[] = ( $current = floor( $floatVal ) ) . $units[ $divisorIndex - 1 ][ $current == 1 ? 0 : 1 ];
                $remain =  ( ( ( $floatVal - floor( $floatVal ) ) * $divisors[ $divisorIndex - 1 ] ) );
                
                $tries++;
            }
            
            $time = implode(' ', $stack );
            
            if ( !count($stack) )
                return str_replace( '%time%', '0 ' . $intl['second'][1], $intl[ 'past' ] );
            else
                return str_replace( '%time%', $time, $intl[ $me < 0 ? 'past' : 'future'  ] );
        }
    }
    
    /*
    $n = new OneDB_Number( 60 );
    echo $n->toSize(), "\n";
    echo $n->toTime( OneDB_i18n::get('ro', 'time') ), "\n";
    */

?>