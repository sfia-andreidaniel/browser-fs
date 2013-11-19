<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    function help() {
        
        $term = Object( 'Utils.Terminal' );
        
        echo implode( "\r", [
            "useradd syntax:",
            '    ' . $term->color( 'useradd', 'green' ) . ' ' . $term->color( '<username>', 'yellow' ) . ' ' . $term->color( '<password>', 'cyan' ),
            '',
            'note that the command works in a website context (use <website> first).',
            '    ' . $term->color( '<username>', 'yellow' ) . ' argument should contain only letters, numbers, and dot(s)',
            '    ' . $term->color( '<password>', 'cyan'   ) . ' argument represents the user password',
            '',
            'see also:',
            '    ' . $term->color( 'usermod', 'green' ),
            '    ' . $term->color( 'userdel', 'green' ),
            '',
            ''
        ] );
        
        die(1);
    }

    $term = Object( 'Utils.Terminal' );
        
    
    if ( term_get_env( 'site' ) == '' ) {
        echo $term->color( 'this command requires a site context', 'red' ), "\r\r";
        die(1);
    }
    
    if ( count( $argv ) != 3 )
        help();

    try {
        
        // load class Sys.Security.Management
        Object( 'Utils.Class.Loader', 'Sys.Security.Management' );
        
        Sys_Security_Management::useradd( term_get_env( 'site' ), $argv[1], $argv[2] );

        echo "user '", $term->color( $argv[1], 'yellow' ) . "' has been added\r\r";
    
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>