<?php

    if ( !extension_loaded( 'mongo' ) )
        die( "Mongo support not found!" );

    require_once __DIR__ . '/bootstrap.php';
    
    try {
    
        $path = Object( 'Utils.Parsers.Path' );
        
        var_dump( $path->isAbsolute( '/mar' ) );
        
        var_dump( $path->append( '/foo/bar/.././car/../bar/', '/mar' ) );
        
    
    } catch ( Exception $e ) {
        
        echo Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), "\n";
        
    }
    
?>