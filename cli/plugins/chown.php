<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );

    $term = Object( 'Utils.Terminal' );
    
    if ( term_get_env( 'site' ) == '' ) {
        echo $term->color( 'this command requires a site context', 'red' ), "\r\r";
        die(1);
    }
    
    if ( ( $cwd = term_get_env( 'path' ) ) == '' ) {
        echo $term->color( 'the client binary did not reported to cd command the current working directory', 'red' );
        die(1);
    }
    
    if ( count( $argv ) < 3 || count( $argv ) > 4 || ( count( $argv ) == 4 && $argv[1] != '-r' ) ) {
        
        echo $term->color( "mv: wrong numbers or bad usage of arguments! displaying manual...", 'red' ), "\r";
        
        term_manual('chown');
    }
    
    $recursive  = ( count($argv) == 4 && $argv[1] == '-r' );
    $owner      = ( count($argv) == 4 ? $argv[2] : $argv[1] );
    $path       = ( count($argv) == 4 ? $argv[3] : $argv[2] );
    
    try {
        // initialize path parser
        $parser = Object( 'Utils.Parsers.Path' );
        
        if ( in_array( term_get_env( 'user' ), ['', 'onedb' ] ) )
            throw Object( 'Exception.FS', "You must be logged in as a user in order to run this command!" );
        
        // initialize client
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), term_get_env( 'user' ), term_get_env( 'password' ) );
        
        // this is where the target we're changing it's owner
        $source = $path;
        
        if ( $parser->isAbsolute( $source ) ) {
        
            $where = $parser->resolve( $source );
        
            if ( $where === FALSE )
                throw Object( 'Exception.IO', 'invalid object path "' . $source . '"' );
            
            $src = $client->getElementByPath( $where );
            
            if ( $src === NULL )
                throw Object( 'Exception.IO', 'object path "' . $where . '" does not exists!' );
        
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
                
                $where = $parser->append( $cwd, $source );
                
                // can be resolved?
                if ( $where === FALSE )
                    throw Object( 'Exception.IO', 'invalid source path: "' . $cwd . '/' . $source . '"' );
                
                $src = $client->getElementByPath( $where );
                
                // exists?
                if ( $src === NULL )
                    throw Object( 'Exception.IO', 'source path "' . $where . '" does not exists!' );
                
            }
        
        }
        
        $src->chown( $owner, $recursive );
        
        echo 'the owner of object ' . $term->color( $src->name, 'yellow' ) . ' was successfully changed to ' . $owner, "\r";
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>