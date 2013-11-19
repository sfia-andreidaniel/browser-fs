<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );

    $term = Object( 'Utils.Terminal' );
    
    function help() {
        
        global $term;
        
        echo implode( "\r", [
            'cd syntax:',
            '    ' . $term->color( 'cd', 'green' ) . ' ' . $term->color( '<path>', 'yellow' ),
            '',
            'note that the command works in a website context (use <website> first).',
            '',
            'arguments:',
            '    ' . $term->color( '<path>', 'yellow' ) . ' - relative or absolute path',
            '',
            ''
        ] );
        
        die(1);
    }

    if ( term_get_env( 'site' ) == '' ) {
        echo $term->color( 'this command requires a site context', 'red' ), "\r\r";
        die(1);
    }
    
    if ( ( $cwd = term_get_env( 'path' ) ) == '' ) {
        echo $term->color( 'the client binary did not reported to cd command the current working directory', 'red' );
        die(1);
    }
    
    if ( count( $argv ) != 2 )
        help();
    
    try {
        // initialize path parser
        $parser = Object( 'Utils.Parsers.Path' );
        
        // initialize client
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), term_get_env( 'user' ), term_get_env( 'password' ) );
        
        // this is where we want to change directory
        $destination = $argv[1];
        
        if ( $parser->isAbsolute( $destination ) ) {
        
            $where = $parser->resolve( $destination );
        
            if ( $where === FALSE )
                throw Object( 'Exception.IO', 'invalid path "' . $destination . '"' );
            
            $obj = $client->getElementByPath( $where );
            
            if ( $obj === NULL )
                throw Object( 'Exception.IO', 'path "' . $where . '" does not exists!' );
            
            if ( !$obj->isContainer() )
                throw Object( 'Exception.IO', 'path "' . $where . '" exists but is not a directory' );
            
            // path is valid
            term_set_env( 'path', $where );
            
        } else {
            
            $current = $client->getElementByPath( $cwd );
        
            if ( $current === NULL ) {
                // current working directory is invalid
                // we set the path to '/'
                
                term_set_env( 'path', '/' );
                
                echo $term->color( '* warning: current path is invalid. changing current working dir to "/".', 'cyan' ), "\r";
                
                throw Object( 'Exception.IO', 'failed to change directory' );
                
            } else {
                
                // the current working directory is valid
                
                $where = $parser->append( $cwd, $destination );
                
                // can be resolved?
                if ( $where === FALSE )
                    throw Object( 'Exception.IO', 'invalid path: "' . $cwd . '/' . $destination . '"' );
                
                $obj = $client->getElementByPath( $where );
                
                // exists?
                if ( $obj === NULL )
                    throw Object( 'Exception.IO', 'path "' . $where . '" does not exists!' );
                
                // is a dir?
                if ( !$obj->isContainer() )
                    throw Object( 'Exception.IO', 'path "' . $where . '" exists but is not a directory' );
                
                // path is valid
                term_set_env( 'path', $where );
            }
        
        }
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>