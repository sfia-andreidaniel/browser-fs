<?php

    class OneDB_i18n {
        static public function get( $countryCode, $settingName ) {
            $ccode = "OneDB_i18n_$countryCode";
            return $ccode::${"$settingName"};
        }
    }

    class OneDB_i18n_en {
        
        public static $time = array(
           'second' => array( 's', 's' ),
           'minute' => array( 'm', 'm' ),
           'hour'   => array( ' hour', ' hrs' ),
           'day'    => array( ' day',  ' days' ),
           'month'  => array( ' month',' months' ),
           'year'   => array( ' year', ' years' ),
           'past'   => '%time% ago',
           'future' => 'in %time%'
         );

        public static $time_short = array(
           'second' => array( 's', 's' ),
           'minute' => array( 'm', 'm' ),
           'hour'   => array( 'h', 'h' ),
           'day'    => array( 'D', 'D' ),
           'month'  => array( 'Mt','Mt' ),
           'year'   => array( 'Y', 'Y' ),
           'past'   => '%time%',
           'future' => 'in %time%'
         );

    }
    
    class OneDB_i18n_ro {
        
        public static $time = array(
           'second' => array( 's', 's' ),
           'minute' => array( 'm', 'm' ),
           'hour'   => array( ' oră', ' ore' ),
           'day'    => array( ' zi',  ' zile' ),
           'month'  => array( ' lună',' luni' ),
           'year'   => array( ' an', ' ani' ),
           'past'   => 'acum %time%',
           'future' => 'peste %time%'
         );

        public static $time_short = array(
           'second' => array( 's', 's' ),
           'minute' => array( 'm', 'm' ),
           'hour'   => array( 'h', 'h' ),
           'day'    => array( 'Z', 'Z' ),
           'month'  => array( 'L', 'L' ),
           'year'   => array( 'A', 'A' ),
           
           'past'   => '%time%',
           'future' => 'peste %time%'
         );
    }

?>