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
    
    if ( count( $argv ) != 3 ) {
        
        echo $term->color( "cp: wrong number or bad arguments! displaying manual...", 'red' ), "\r";
        
        term_manual('cp');
    }
    
    try {
        // initialize path parser
        $parser = Object( 'Utils.Parsers.Path' );
        
        if ( in_array( term_get_env( 'user' ), ['', 'onedb' ] ) )
            throw Object( 'Exception.FS', "You must be logged in as a user in order to run this command!" );
        
        // initialize client
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), term_get_env( 'user' ), term_get_env( 'password' ) );
        
        // this is where we want to change directory
        $source = $argv[1];
        
        if ( $parser->isAbsolute( $source ) ) {
        
            $where = $parser->resolve( $source );
        
            if ( $where === FALSE )
                throw Object( 'Exception.IO', 'invalid source path "' . $source . '"' );
            
            $src = $client->getElementByPath( $where );
            
            if ( $src === NULL )
                throw Object( 'Exception.IO', 'source path "' . $where . '" does not exists!' );
        
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
        
        $destination = $argv[2];
        
        if ( $parser->isAbsolute( $destination ) ) {
        
            $where = $parser->resolve( $destination );
        
            if ( $where === FALSE )
                throw Object( 'Exception.IO', 'invalid destination path "' . $destination . '"' );
            
            $dst = $client->getElementByPath( $where );
            
            if ( $dst === NULL )
                throw Object( 'Exception.IO', 'destination path "' . $where . '" does not exists!' );
        
        } else {
            
            $current = isset( $current )
                ? $current
                : $client->getElementByPath( $cwd );
        
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
                    throw Object( 'Exception.IO', 'invalid destination path: "' . $cwd . '/' . $destination . '"' );
                
                $dst = $client->getElementByPath( $where );
                
                // exists?
                if ( $dst === NULL )
                    throw Object( 'Exception.IO', 'destination path "' . $where . '" does not exists!' );
                
            }
        
        }
        
        // check if cwd is a child of the src. if it is,
        // switch the cwd after the move to dst
        
        $dst->copyChild( $src );
        
        echo $term->color( $src->name, 'yellow' ) . ' was successfully copied inside ' . $dst->url, "\r";
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>