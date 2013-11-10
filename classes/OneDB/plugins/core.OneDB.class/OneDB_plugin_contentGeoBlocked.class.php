<?php

    require_once "OneDB.class.php";

    if (!isset( $_SESSION ))
        session_start();

    function OneDB_Ip2CountryCode( $strIP ) {
    
        if (isset( $_SESSION['ONEDB_GEO_COUNTRY'] )) {

            if ( !defined('ONEDB_GEO_COUNTRY') ) {
                header("X-OneDB-GeoLocation: $_SESSION[ONEDB_GEO_COUNTRY]");
                define('ONEDB_GEO_COUNTRY', $_SESSION['ONEDB_GEO_COUNTRY'] );
            }

            return $_SESSION['ONEDB_GEO_COUNTRY'];
        }
    
        if (defined('ONEDB_GEO_COUNTRY'))
            return ONEDB_GEO_COUNTRY;
    
        $request = @file_get_contents( 'http://www.geoplugin.net/php.gp?ip=' . $strIP );
        
        if ( strlen ( $request ) ) {

            $data = @unserialize( $request );

            if (is_array( $data ) && isset( $data['geoplugin_countryCode' ] )) {
                $cc = strtolower( $data['geoplugin_countryCode'] );
                $cc = strlen( $cc ) ? $cc : '-';
                
                if ($cc != '-')
                    $_SESSION['ONEDB_GEO_COUNTRY'] = $cc;
                
                define('ONEDB_GEO_COUNTRY', $cc);
                header("X-OneDB-GeoLocation: " . ONEDB_GEO_COUNTRY);
                
                return $cc;
            } else {
                define('ONEDB_GEO_COUNTRY', '-');
                header("X-OneDB-GeoLocation: " . ONEDB_GEO_COUNTRY);
                return '-';
            }
        
        } else {
            define('ONEDB_GEO_COUNTRY', '-');
            header("X-OneDB-GeoLocation: " . ONEDB_GEO_COUNTRY);
            return '-';
        }
    }

    class OneDB_plugin_contentGeoBlocked extends OneDB {
        
        public function __construct( $config = array() ) {
            parent::__construct( $config );
        }
        
        public function contentGeoBlocked( $OneDBMongoObject, $remoteAddr = NULL ) {
        
            $geoBlocking = $OneDBMongoObject->geoBlocking;
            
            if (!is_array( $geoBlocking ) || !count( $geoBlocking ))
                return FALSE;
            
            $geoBlockingFunctionName = $this->registry()->{"OneDB.GeoBlocking.FunctionName"};
            
            if ( !$geoBlockingFunctionName )
                $geoBlockingFunctionName = "OneDB_Ip2CountryCode";
            
            //determine the user IP
            if ($remoteAddr === NULL)
                $remoteAddr = OneDB_RemoteAddr();
            
            if (substr( $remoteAddr, 0, 4 ) == '127.' )
                return FALSE;
            
            $countryName = strtolower( $geoBlockingFunctionName( $remoteAddr ) );
            
            // echo "DBG: $countryName IN ", implode( ',', $geoBlocking ), "\n";
            
            return in_array( $countryName, $geoBlocking ) ? 0 : 1;
        }
        
    }
    
?>