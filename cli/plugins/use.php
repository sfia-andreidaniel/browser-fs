<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    $term = Object( 'Utils.Terminal' );

    function onedbpass() {
        return @file_get_contents( __DIR__ . '/../../etc/onedb.shadow.gen' );
    }
    
    try {
        
        if ( count( $argv ) > 1 ) {

            $client = Object( 'OneDB' )->connect( $argv[1], 'onedb', onedbpass() );
            term_set_env( 'site', $argv[1] );
            term_set_env( 'path', '/' );
            term_set_env( 'user', 'onedb' );
            term_set_env( 'password', '' );
            
        } else {
            term_set_env( 'site', '' );
            term_set_env( 'path', '' );
            term_set_env( 'user', '' );
            term_set_env( 'password', '' );
        }
    
    } catch ( Exception $e ) {
        echo $term->color( Object( 'Utils.Parsers.Exception')->explainException( $e ), 'red' ), "\r\r";
    }
    
?>