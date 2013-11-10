#!/usr/bin/php
<?php
    
    $cfg = isset( $argv[1] ) ? $argv[1] : die('');
    
    
    
    function debug( $what = 'debug', $dieAfter = FALSE ) {
        if ( $dieAfter ) {
    
            file_put_contents( dirname(__FILE__) . '/daemon.log', @date('H:i:s') . ' - ' . $what . "\n", FILE_APPEND );
        
            global $cfg;
            
            if ( $cfg && file_exists( $cfg ) )
                @unlink( $cfg );
            
            die('');
        }
    }
    
    if ( !file_exists( $cfg ) )
        debug( $cfg . ' - not found. abort!', true );
    
    $asyncfile = preg_replace( '/\.lock$/', '.async', $cfg );
    $unlockFile= preg_replace( '/\.lock$/', '.unlock', $cfg );
    
    if (!file_exists( $asyncfile ) )
        debug( $asyncfile . ' - not found. abort!', true );
    
    $json = @json_decode( file_get_contents( $cfg ), TRUE );

    if ( !is_array( $json ) )
        debug( $cfg . ' - does not cotain json. abort!', true );
    
    if ( !isset( $json[ 'url' ] ) )
        debug( $cfg . ' - does not contain an URL. abort!', true );
    
    $ch = curl_init();
    
    curl_setopt( $ch, CURLOPT_URL, $json['url'] );
    
    debug( $json['url'] . ' - start');
    
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    
    if ( isset( $json['post'] ) ) {
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $json['post'] );
        curl_setopt( $ch, CURLOPT_POST, 1 );
    }
    
    curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
    
    if ( isset( $json['auth'] ) ) {
        curl_setopt( $ch, CURLOPT_USERPWD, $json["auth"] );
    }
    
    $buffer = curl_exec( $ch );
    
    debug( strlen( $buffer ) . ' bytes' );
    
    if ( empty ($buffer ) ) {
        debug("$json[url] - empty server response!", true );
    }
    
    $buffer = trim( $buffer );
    
    $data = @json_decode( $buffer, TRUE );
    
    if (!is_array( $data ) )
        debug("$json[url] - decoded json is not an array!", true);
    
    file_put_contents( $asyncfile, $buffer );
    file_put_contents( $unlockFile, '1' );
    
    debug( "$json[url] - successfully completed!" );
    
    @unlink( $cfg );
    
?>