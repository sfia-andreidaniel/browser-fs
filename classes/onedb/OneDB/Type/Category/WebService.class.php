<?php

    require_once __DIR__ . '/../Category.class.php';
    require_once __DIR__ . '/../../Object.class.php';

    class OneDB_Type_Category_WebService extends OneDB_Type_Category {
        
        static public $_isReadOnly  = FALSE;
        static public $_isContainer = TRUE;
        static public $_isLive      = TRUE;
        
        protected $_webserviceMaxObjects = -1; // -1 unlimited
        protected $_webserviceUrl        = ''; // the url of the webservice
        protected $_webserviceTtl        =  0; // the time to live of the webservice objects
        protected $_webserviceConf       = []; // the configuration of the webservice
        protected $_webserviceUsername   = ''; // if we need a username to require items from webservice
        protected $_webservicePassword   = ''; // if we need a password to require items from webservice
        protected $_webserviceLastHit    = -1; // last unix timestamp when the webservice has been hitted
        protected $_webserviceObjectPath = ''; // if the webservice is returning it's array of objects in a sub-path
        protected $_webserviceTimeout    = 10; // number of seconds before the webservice will expire
        
        public function exportOwnProperties( array &$properties ) {
            
            $properties[ 'webserviceMaxObjects' ] = $this->_webserviceMaxObjects;
            $properties[ 'webserviceUrl' ]        = $this->_webserviceUrl;
            $properties[ 'webserviceTtl' ]        = $this->_webserviceTtl;
            $properties[ 'webserviceConf']        = $this->_webserviceConf;
            $properties[ 'webserviceUsername' ]   = $this->_webserviceUsername;
            $properties[ 'webservicePassword' ]   = $this->_webservicePassword;
            $properties[ 'webserviceLastHit' ]    = $this->_webserviceLastHit;
            $properties[ 'webserviceObjectPath' ] = $this->_webserviceObjectPath;
            $properties[ 'webserviceTimeout' ]    = $this->_webserviceTimeout;
            
        }

        public function importOwnProperties( array $properties ) {
            
            $this->_webserviceMaxObjects = isset( $properties[ 'webserviceMaxObjects' ] ) ? $properties[ 'webserviceMaxObjects' ] : -1;
            $this->_webserviceUrl        = isset( $properties[ 'webserviceUrl' ]        ) ? $properties[ 'webserviceUrl' ]        : '';
            $this->_webserviceTtl        = isset( $properties[ 'webserviceTtl' ]        ) ? $properties[ 'webserviceTtl' ]        : 0;
            $this->_webserviceConf       = isset( $properties[ 'webserviceConf' ]       ) ? $properties[ 'webserviceConf' ]       : [];
            $this->_webserviceUsername   = isset( $properties[ 'webserviceUsername' ]   ) ? $properties[ 'webserviceUsername' ]   : '';
            $this->_webservicePassword   = isset( $properties[ 'webservicePassword' ]   ) ? $properties[ 'webservicePassword' ]   : '';
            $this->_webserviceLastHit    = isset( $properties[ 'webserviceLastHit' ]    ) ? $properties[ 'webserviceLastHit' ]    : -1;
            $this->_webserviceObjectPath = isset( $properties[ 'webserviceObjectPath' ] ) ? $properties[ 'webserviceObjectPath' ] : '';
            $this->_webserviceTimeout    = isset( $properties[ 'webserviceTimeout']     ) ? $properties[ 'webserviceTimeout']     : 10;
            
        }
        
        public function refresh() {
            
            //test if configured
            if ( $this->_webserviceUrl == '' ) {
                //echo "skip refresh: not set\n";
                return TRUE;
            }
            
            // test if expired
            if ( $this->_webserviceTtl > 0 && $this->_webserviceLastHit > 0 && ( time() - $this->_webserviceLastHit < $this->_webserviceTtl ) ) {
                //echo "skip refresh: 1\n";
                return TRUE;
            }
            
            $url = $this->_webserviceUrl;
            
            if ( isset( $this->_webserviceConf['get'] ) && is_array( $this->_webserviceConf['get'] ) )
                $url .= Object( 'OneDB.Query.URLParser', $this->_webserviceConf['get'] )->encodeAsGet;
            
            // echo "url: $url\n";
            
            $ch = curl_init( $url );
            
            if ( isset( $this->_webserviceConf['post'] ) && is_array( $this->_webserviceConf['post'] ) ) {
                curl_setopt( $ch, CURLOPT_POST, 1 );
                curl_setopt( $ch, CURLOPT_POSTFIELDS, Object( 'OneDB.Query.URLParser', $this->_webserviceConf['post'] )->encodeAsPost );
            }
            
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            
            if ( $this->_webserviceTimeout > 0 )
                curl_setopt( $ch, CURLOPT_TIMEOUT, $this->_webserviceTimeout );
            
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
            curl_setopt( $ch, CURLOPT_FORBID_REUSE, TRUE );
            
            if ( $this->_webserviceUsername != '' ) {
                
                curl_setopt( $ch, CURLOPT_USERPWD, $this->_webservicePassword == ''
                    ? urlencode( $this->_webserviceUsername )
                    : urlencode( $this->_webserviceUsername ) . ':' . urlencode( $this->_webservicePassword )
                );
                
            }
            
            $data = curl_exec( $ch );
            
            if ( empty( $data ) )
                throw Object('Exception.OneDB.Webservice', "the webservice category " . $this->_root->url . " failed to refresh!" );
            
            $data = @json_decode( $data, TRUE );
            
            if ( !is_array( $data ) )
                throw Object('Exception.OneDB.Webservice', "the webservice category " . $this->_root->url . " did not returned a valid JSON!" );
            
            /* Loop through data if a webserviceObjectPath is defined */
            
            if ( $this->_webserviceObjectPath != '' ) {
                
                $pathParts = explode( '.', $this->_webserviceObjectPath );
                
                for ( $i=0, $len = count( $pathParts ); $i<$len; $i++ ) {
                    
                    if ( !isset( $data[ $pathParts[$i] ] ) || !is_array( $data[ $pathParts[$i] ] ) )
                        throw Object('Exception.OneDB.Webservice', 'failed to position in object path @ webservice ' . $this->_root->url . ', path = ' . $this->_webserviceObjectPath . ', segment = ' . $pathParts[$i] );
                    else
                        $data = $data[ $pathParts[$i] ];
                
                }
                
            }
            
            // Set names if necesarry
            
            for ( $i=0, $len = count( $data ); $i<$len; $i++ ) {
                
                if ( !array_key_exists( 'name', $data[$i] ) )
                    $data[$i]['name'] = 'Item #' . ( $i + 1 );
                else
                    $data[$i]['name'] = trim( $data[$i]['name'] );
                
                // if the item has an _id property, rename that property
                // to -> __id
                
                if ( array_key_exists( '_id', $data[$i] ) ) {
                    $data[$i]['_id_'] = $data[$i]['_id'];
                    unset( $data[$i]['_id'] );
                }
                
                if ( array_key_exists( 'id', $data[$i] ) ) {
                    
                    $data[$i]['id_'] = $data[$i]['id'];
                    unset( $data[$i]['id'] );
                    
                }
            }
            
            /* Remove current objects from this category ... */
            
            $seenNames = [];
            
            parent::getChildNodes()->each( function( $item ) use (&$seenNames) {
                if ( $item->type == 'Json' )
                    $item->delete();
                else
                    $seenNames[ $item->name ] = 0;
            } );
            
            // insert objects ...
            $this->_root->save();
            
            foreach ( $data as $item ) {
                
                if ( !isset( $seenNames[ $item['name'] ] ) )
                    $seenNames[ $item['name'] ] = 0;
                else {
                    $seenNames[ $item[ 'name' ] ] ++;
                    
                    while ( array_key_exists( $item[ 'name' ] . " (" . $seenNames[ $item['name'] ] . ")", $seenNames ) )
                        $seenNames[ $item['name'] ] ++;
                    
                    $item['name'] .= ' (' . $seenNames[ $item['name'] ] . ')';
                }
                
                $dbitem = $this->_root->create( 'Json' );
                
                // setup properties
                
                foreach ( array_keys( $item ) as $key ) {
                    
                    if ( in_array( $key, OneDB_Object::$_nativeProperties ) ) {
                        
                        if ( !in_array( $key, [ 'type', 'id' ] ) )
                            $dbitem->{$key} = $item[ $key ];
                        
                    } else $dbitem->data->{$key} = $item[ $key ];
                    
                }
                
                $dbitem->save();
            }
            
            // set _webserviceLastHit
            
            $this->webserviceLastHit = time();
            
        }
        
        public function getChildNodes() {
            $this->refresh();
            return parent::getChildNodes();
        }
        
        public function __mux() {
            return [
                'webserviceMaxObjects' => $this->_webserviceMaxObjects,
                'webserviceUrl'        => $this->_webserviceUrl,
                'webserviceTtl'        => $this->_webserviceTtl,
                'webserviceConf'       => $this->_webserviceConf,
                'webserviceUsername'   => $this->_webserviceUsername,
                'webservicePassword'   => $this->_webservicePassword,
                'webserviceLastHit'    => $this->_webserviceLastHit,
                'webserviceObjectPath' => $this->_webserviceObjectPath,
                'webserviceTimeout'    => $this->_webserviceTimeout
            ];
        }
        
    }
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webserviceMaxObjects", [
        "get" => function() {
            return $this->_webserviceMaxObjects;
        },
        "set" => function( $maxObjects ) {
            
            if ( !is_int( $maxObjects ) )
                throw Object( 'Exception.OneDB', "The value must be an integer!" );
            
            if ( $maxObjects < -1 )
                $maxObjects = -1;
            
            $this->_webserviceMaxObjects = $maxObjects;
            
            $this->_root->_change( 'webserviceMaxObjects', $maxObjects );
        }
    ]);
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webserviceUrl", [
        "get" => function() {
            return $this->_webserviceUrl;
        },
        "set" => function( $url ) {
            
            $url = empty( $url ) ? '' : $url;
            
            if ( !is_string( $url ) )
                throw Object( 'Exception.OneDB', "The url property should be of type string" );
            
            $url = trim( $url );
            
            if ( $url != '' ) {
                
                $info = parse_url( $url );
                
                if ( !isset( $info['scheme'] ) || !in_array( strtolower( $info['scheme'] ), [ 'http', 'https' ] ) )
                    throw Object( 'Exception.OneDB', "The scheme of the webservice url should be either 'http', either 'https'" );
                
                if ( !isset( $info['host'] ) )
                    throw Object( 'Exception.OneDB', "Could not determine the hostname from your url" );
                
                if ( strpos( $url, '?' ) !== FALSE )
                    throw Object( 'Exception.OneDB', "The query string of the url is automatically formed, and it should be passed via the config.get param!" );
            
            }
            
            $this->_webserviceUrl = $url;
            
            $this->_root->_change( 'webserviceUrl', $url );
        }
    ]);
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webserviceTtl", [
        "get" => function() {
            return $this->_webserviceTtl;
        },
        "set" => function( $ttl ) {
            
            if ( !is_int( $ttl ) )
                throw Object( 'Exception.OneDB', "The time to live property should be an integer!" );
            
            $ttl = $ttl < 0 ? 0 : $ttl;
            
            $this->_webserviceTtl = $ttl;
            
            $this->_root->_change( 'webserviceTtl', $ttl );
        }
    ]);
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webserviceConf", [
        "get" => function() {
            return $this->_webserviceConf;
        },
        "set" => function( $conf ) {
        
            if ( $conf == NULL || empty( $conf ) )
                $conf = [];
        
            if ( is_string( $conf ) )
                $conf = @json_decode( $conf, TRUE );
            
            if ( !is_array( $conf ) )
                throw Object( 'Exception.OneDB', "The config parameter should be either null, empty, or an object!" );
            
            $this->_webserviceConf = $conf;
            
            $this->_root->_change( 'webserviceConf', $conf );
        }
    ]);
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webserviceUsername", [
        "get" => function() {
            return $this->_webserviceUsername;
        },
        "set" => function( $userName ) {
            
            $userName = empty( $userName ) ? '' : $userName;
            
            if ( !is_string( $userName ) )
                throw Object( 'Exception.OneDB', "The username of a webservice category should be of type string" );
            
            $userName = trim( $userName );
            
            $this->_webserviceUsername = $userName;
            
            $this->_root->_change( 'webserviceUsername', $userName );
            
        }
    ]);
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webservicePassword", [
        "get" => function() {
            return $this->_webservicePassword;
        },
        "set" => function( $password ) {
            
            $password = empty( $password ) ? '' : $password;
            
            if ( !is_string( $password ) )
                throw Object( 'Exception.OneDB', "The password of a webservice category should be of type string" );
                
            $this->_webservicePassword = $password;
            
            $this->_root->_change( 'webservicePassword', $password );
        }
    ]);
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webserviceLastHit", [
        "get" => function() {
            return $this->_webserviceLastHit;
        },
        "set" => function( $timestamp ) {
            
            if ( !is_int( $timestamp ) )
                $timestamp = (int)$timestamp;
            
            if ( $timestamp < -1 )
                $timestamp = -1;
            
            $this->_webserviceLastHit = $timestamp;
            
            $this->_root->_change( 'webserviceLastHit', $timestamp );
            
        }
    ]);
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webserviceObjectPath", [
        "get" => function() {
            return $this->_webserviceObjectPath;
        },
        "set" => function( $path ) {
        
            $path = empty( $path ) ? '' : $path;
            
            if ( !is_string( $path ) )
                throw Object( 'Exception.OneDB', "The object path of a webservice should be either empty either an object path" );
            
            $path = trim( $path );
            
            if ( $path != '' && !preg_match( '/^[a-zA-Z\_\$]+((\.[a-zA-Z\_\$]+)+)?$/', $path ) )
                throw Object( 'Exception.OneDB', "Invalid webservice object path!" );
            
            $this->_webserviceObjectPath = $path;
            
            $this->_root->_change( 'webserviceObjectPath', $path );
        
        }
    ]);
    
    OneDB_Type_Category_Webservice::prototype()->defineProperty( "webserviceTimeout", [
        "get" => function() {
            return $this->_webserviceTimeout;
        },
        "set" => function( $int ) {
            
            $int = (int)$int;
            
            if ( $int < -1 )
                $int = -1;
            
            if ( $int == 0 )
                throw Object( 'Exception.OneDB', "The timeout of a webservice category should be either -1 either a positive integer value!" );
            
            $this->_webserviceTimeout = $int;
            
            $this->_root->_change( 'webserviceTimeout', $int );
        }
    ]);
    
?>