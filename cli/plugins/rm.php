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
    
    if ( count( $argv ) == 1 )
        term_manual( 'rm' );
    
    try {

        // initialize path parser
        $parser = Object( 'Utils.Parsers.Path' );
        
        // initialize client
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), term_get_env( 'user' ), term_get_env( 'password' ) );
        
        // this is the what we're aiming to remove
        $destination = end( $argv );
        
        if ( $parser->isAbsolute( $destination ) ) {

            $where = $parser->resolve( $destination );
        
        } else {
            
            $where = $parser->append( term_get_env( 'path' ), $destination );
            
        }
        
        $obj = $client->getElementByPath( $where );
        
        if ( $obj === NULL )
            throw Object( 'Exception.IO', 'path "' . $where . '" not found' );

        echo "path '" . $term->color( $where, 'yellow' ) . "' removed\n";
        
        if ( ( $len = $obj->childNodes->length ) > 0 ) {
            
            if ( !( count( $argv ) == 3 && $argv[1] == '-r' ) )
                throw Object( 'Exception.IO', 'path contains ' . $len . ' child nodes, use "-r" argument to remove it recursively.' );
        
        }
        
        $obj->delete();
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>