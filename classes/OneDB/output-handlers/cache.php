<?php
    
    // print_r( $_SERVER['QUERY_STRING'] );
    
    if ( isset( $_SERVER['QUERY_STRING'] ) && strlen( $_SERVER['QUERY_STRING'] ) ) {
        $query = explode('&', $_SERVER['QUERY_STRING'] );
        $out = array();
        foreach ($query as $part) {
            $parts = explode('=', $part);
            if ( count($parts) == 2 && $parts[0] == '_URI_' )
                continue;
            $out[] = $part;
        }
        $query = count($out) ? ( '?' . implode('&', $out) ) : '';
    } else $query = '';
    
    function error( $code, $message ) {
        @header('HTTP/1.0 ' . $code . ' ' . str_replace("\n", "", $message ));
        header('Content-Type: text/plain');
        die( $message );
    }
    
    if (!isset( $_SERVER ) || $_SERVER['REQUEST_METHOD'] != 'GET' ) {
        error( '405', 'Method Not Allowed' );
    }
    
    $file = isset( $_GET['file'] ) ? $_GET['file'] : error( '500', 'Bad request!' );
    
    $file = str_replace(' ', '+', $file );
    $file .= $query;
    
    $ttl  = isset( $_GET['ttl'] ) ? (int)$_GET['ttl'] : error( '500', 'Bad TTL' );
    
    if ($ttl > 600)
        $ttl = 600;
    
    error_reporting( E_ALL );
    ini_set('display_errors', 'on');
    
    try {
    
        $cfg_file = $_SERVER['DOCUMENT_ROOT'] . '/conf/onedb.cfg.php';
    
        if (!file_exists( $cfg_file )) {
            
            $cfg_file = $_SERVER['DOCUMENT_ROOT'] . '/conf/onedb.memcache.cfg.php';
    
            if (!file_exists( $cfg_file )) {
                error( '500', 'This handler is intended to run only on frontend environment!' );
            }
        }
    
        require_once ( $cfg_file );
    
        if ( !isset( $_FRONTEND_CFG_ ) )
            throw new Exception("The global variable \$_FRONTEND_CFG_ was not found");
        
        if (!is_array( $_FRONTEND_CFG_ ) || !isset( $_FRONTEND_CFG_['memcache.host'] ) )
            throw new Exception("\$_FRONTEND_CFG_['memcache.host'] not defined!");
        
        $_FRONTEND_CFG_['memcache.port'] = isset( $_FRONTEND_CFG_['memcache.port'] ) ? $_FRONTEND_CFG_['memcache.port'] : 11211;
        
        $memcache = new Memcache();
        
        if (!$memcache->connect( $_FRONTEND_CFG_['memcache.host'], $_FRONTEND_CFG_['memcache.port'] ))
            throw new Exception("Could not connect to memcache server!");
        
        $domain = isset( $_SERVER['SERVER_NAME'] ) ? $_SERVER['SERVER_NAME'] : 'localhost';
        $proto  = isset( $_SERVER['HTTPS'] ) ? (
            !empty( $_SERVER['HTTPS'] ) ? 'https' : 'http'
        ) : 'http';
        
        if (!preg_match( '/^(http(s)?)\:\/\//', $file ) ) {
            $file = "$proto://$domain/$file";
        }
        
        
        $cache = $memcache->get( "onedb-cache:$file" );
        
        if (!empty($cache)) {
            
            $data = json_decode( $cache, TRUE );
            
            header( 'Content-Type: ' . $data['contentType'] );
            header( 'X-OneDB-Cache-Origin: memcache' );
            header( 'Content-Length: ' . strlen( $data['content'] ) );
            echo $data['content'];
            
        } else {
            
            /* Fetch the content */
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_URL, $file );
            
            curl_setopt( $ch, CURLOPT_USERAGENT, isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'OneDB' );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            
            if (isset( $_SERVER['HTTP_REFERER'] ) )
                curl_setopt( $ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER'] );
            
            $buffer = curl_exec( $ch );
            
            $contentType = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
            $httpCode = (int)curl_getinfo( $ch, CURLINFO_HTTP_CODE );
            
            curl_close( $ch );
            
            if ($httpCode == 200) {
                
                /* Test for only text/... */
                
                if ( preg_match( '/^text(\/|$)/', $contentType ) ) {
                    /* Do caching */
                    
                    $cache = json_encode( array(
                        'contentType' => $contentType,
                        'content' => $buffer
                    ));
                    
                    $memcache->set( "onedb-cache:$file", $cache, MEMCACHE_COMPRESSED, $ttl );

                    header('X-OneDB-Cache-Set: true');
                    
                } else
                    header('X-OneDB-Cache-Set: false');
                    
                header('X-OneDB-Cache-Origin: ' . $file );
                header('Content-Type: ' . $contentType );
                header('Content-Length: ' . strlen( $buffer ) );

                echo $buffer;
            } else {
                error( $httpCode, 'Bad HTTP Code: ' . $httpCode . "\n$file" );
            }
        }

    } catch (Exception $e) {
        error('500', $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() );
    }
    
?>