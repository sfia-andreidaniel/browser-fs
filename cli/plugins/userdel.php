<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    function help() {
        
        $term = Object( 'Utils.Terminal' );
        
        echo implode( "\r", [
            "userdel syntax:",
            '    ' . $term->color( 'userdel', 'green' ) . ' ' . $term->color( '<username>', 'yellow' ),
            '',
            'note that the command works in a website context (use <website> first).',
            '    ' . $term->color( '<username>', 'yellow' ) . ' the username to be deleted',
            '',
            'see also:',
            '    ' . $term->color( 'usermod', 'green' ),
            '    ' . $term->color( 'useradd', 'green' ),
            '',
            ''
        ] );
        
        die(1);
    }
    
    $term = Object( 'Utils.Terminal' );
    
    if ( term_get_env( 'site' ) == '' )
        die( $term->color( 'this command requires a site context', 'red' ) . "\r" );
    
    if ( count( $argv ) != 2 )
        help();

    try {
        
        // load class Sys.Security.Management
        Object( 'Utils.Class.Loader', 'Sys.Security.Management' );
        
        Sys_Security_Management::userdel( term_get_env( 'site' ), $argv[1] );

        echo "user '", $term->color( $argv[1], 'yellow' ) . "' has been deleted\r\r";
    
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e ), 'red' ), "\r\r";
    
    }
?>