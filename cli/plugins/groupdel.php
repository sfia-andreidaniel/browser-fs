<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    $term = Object( 'Utils.Terminal' );
    
    if ( term_get_env( 'site' ) == '' )
        die( $term->color( 'this command requires a site context', 'red' ) . "\r" );
    
    if ( count( $argv ) != 2 )
        term_manual( 'groupdel' );

    try {
        
        // load class Sys.Security.Management
        Object( 'Utils.Class.Loader', 'Sys.Security.Management' );
        
        Sys_Security_Management::groupdel( term_get_env( 'site' ), $argv[1] );

        echo "group '", $term->color( $argv[1], 'yellow' ) . "' has been deleted\r\r";
    
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e ), 'red' ), "\r\r";
    
    }
?>