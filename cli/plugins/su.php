<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );

    $term = Object( 'Utils.Terminal' );
    
    if ( term_get_env( 'site' ) == '' ) {
        echo $term->color( 'this command requires a site context', 'red' ), "\r\r";
        die(1);
    }
    
    if ( count( $argv ) < 3 )
        term_manual('su');
    
    try {
        // initialize client
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), $argv[1], $argv[2] );
        
        term_set_env( 'user', $argv[1] );
        term_set_env( 'password', $argv[2] );
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>