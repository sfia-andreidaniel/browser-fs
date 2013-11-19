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

        if ( term_get_env( 'user' ) == 'onedb' )
            throw Object( 'Exception.IO', 'The mkdir command requires user authentication. Please use the "su" command before' );

        // initialize path parser
        $parser = Object( 'Utils.Parsers.Path' );
        
        // initialize client
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), term_get_env( 'user' ), term_get_env( 'password' ) );
        
        // this is where we want to create directory
        $destination = $argv[1];
        
        if ( $parser->isAbsolute( $destination ) ) {

            $where = $parser->resolve( $destination );
        
        } else {
            
            $where = $parser->append( term_get_env( 'path' ), $destination );
            
        }
        
        if ( $where === FALSE )
            throw Object( 'Exception.IO', 'path "' . $destination . '" is invalid!' );

        // find the parent of the destination
        $parent = $parser->substract( $where, 1 );
            
        if ( $parent === FALSE )
            throw Object( 'Exception.IO', 'failed to determine the destination path where to create the directory' );
            
        // find the basename of the destination
        $name   = $parser->basename( $where );
            
        if ( $name === FALSE )
            throw Object( 'Exception.IO', 'failed to determine the name of the directory!' );
            
        // test if $parent exists and is a container
            
        $oParent = $client->getElementByPath( $parent );
            
        if ( $oParent === NULL )
            throw Object( 'Exception.IO', 'the path "' . $parent . '" does not exists!' );
            
        if ( !$oParent->isContainer() )
            throw Object( 'Exception.IO', 'the path "' . $parent . '" is not a directory!' );
            
        // attempt to create the directory
        $dirNew = $oParent->create( 'Category', $name );
            
        try {
            // save the directory
            $dirNew->save();
        } catch ( Exception $e ) {
            // disable autoCommit
            $dirNew->autoCommit = FALSE;
            // throw exception forward
            throw $e;
        }
        
        echo $term->color( $name , 'green' ) . " created\r\r";
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>