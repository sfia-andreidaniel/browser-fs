<?php

    class Utils_Parsers_OneDBCfg_Site extends Object {
        
        private $_siteConfig = NULL;
        private $_connection= [];
        
        public function init( $siteConfig, $connections ) {
            
            $this->_siteConfig = $siteConfig;
            
            foreach ( $connections as $connection )
                if ( $connection['name'] == $siteConfig[ 'connection' ] )
                    $this->_connection = $connection;
            
        }
        
        public function __get( $propertyName ) {
            
            switch ( $propertyName ) {
                
                case 'connection':
                    return Object( 'Utils.Parsers.OneDBCfg.Connection', $this->_connection );
                    break;
                
                default:
                    if ( !isset( $this->_siteConfig[ $propertyName ] ) )
                        return NULL;
                    else
                        return $this->_siteConfig[ $propertyName ];
                    break;
                
            }
            
        }
        
    }

?>