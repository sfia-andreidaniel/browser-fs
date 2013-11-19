<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );

    $term = Object( 'Utils.Terminal' );
    
    function help() {
        
        global $term;
        
        echo implode( "\r", [
            'dir syntax:',
            '    ' . $term->color( 'cd', 'green' ) . ' ' . $term->color( '[ <path> ]', 'yellow' ),
            '',
            'note that the command works in a website context (use <website> first).',
            '',
            'arguments:',
            '    [ ' . $term->color( '<path>', 'yellow' ) . ' ] - optional argument, relative or absolute path',
            '                                             if omitted, current path will be listed.',
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
    
    if ( count( $argv ) == 1 )
        $argv[] = term_get_env( 'path' );
    
    try {

        // initialize path parser
        $parser = Object( 'Utils.Parsers.Path' );
        
        // initialize client
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), term_get_env( 'user' ), term_get_env( 'password' ) );
        
        // this is the directory we're wanting to list it's contents
        $destination = $argv[1];
        
        if ( $parser->isAbsolute( $destination ) ) {

            $where = $parser->resolve( $destination );
        
        } else {
            
            $where = $parser->append( term_get_env( 'path' ), $destination );
            
        }
        
        if ( $where === FALSE )
            throw Object( 'Exception.IO', 'path "' . $destination . '" is invalid!' );
        
        $obj = $client->getElementByPath( $where );
        
        if ( $obj === NULL )
            throw Object( 'Exception.IO', 'path "' . $where . '" not found!' );
        
        if ( !$obj->isContainer() ) {
            $items = Object( 'OneDB.Iterator', $obj, $client );
        } else {
            $items = $obj->childNodes;
        }
        
        $longestLen = 0; // longest name length in the list
        
        Object( 'Utils.Class.Loader', 'Sys.Umask' );
        
        $items->each( function( $item ) use (&$longestLen) {
            if ( $longestLen < strlen( $item->owner . ':' . $item->group ) )
                $longestLen = strlen( $item->owner . ':' . $item->group );
        } )->here( function( $me ) use ( &$longestLen ) {
            
            echo "total ", $me->length, "\r";
        
        } )->sort( function( $a, $b ) {
            
            return strcmp( $a->name, $b->name );
            
        } )->each( function( $item ) use ( $longestLen, $term ) {
            
            echo Umask::mode_to_str( $item->mode ), "  ",
                 str_pad( $item->owner . ':' . $item->group, $longestLen ), "  ",
                 @date( 'm/d/Y H:i:s', $item->ctime ), '  ', 
                 $term->color( $item->name, $item->isContainer() ? 'cyan' : 'light_gray' ), "\r";
            
        } );
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>