<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );

    $term = Object( 'Utils.Terminal' );
    
    function help() {
        
        global $term;
        
        echo implode( "\r", [
            'su syntax:',
            '    ' . $term->color( 'su', 'green' ) . ' ' . $term->color( '<username>', 'yellow' ),
            '',
            'note that the command works in a website context (use <website> first).',
            '    ' . $term->color( '<username>', 'yellow' ) . ' - the username you want to become',
            '',
            ''
        ] );
        
        die(1);
    }

    if ( term_get_env( 'site' ) == '' ) {
        echo $term->color( 'this command requires a site context', 'red' ), "\r\r";
        die(1);
    }
    
    if ( count( $argv ) < 3 )
        help();
    
    try {
        // initialize client
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), $argv[1], $argv[2] );
        
        term_set_env( 'user', $argv[1] );
        term_set_env( 'password', $argv[2] );
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>