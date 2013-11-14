<?php

    if ( !extension_loaded( 'mongo' ) )
        die( "Mongo support not found!" );

    require_once __DIR__ . '/bootstrap.php';
    
    try {
    
        $connection = Object('OneDB')->connect( 'loopback', 'andrei' );
        
        echo $connection->storage->name, "\n";
    
    
    } catch (Exception $e) {
        
        die("Exception: " . $e->getMessage() . "\nline: " . $e->getLine() . "\nfile: " . $e->getFile() );
        
        print_r( $e->getTrace() );
        
    }
    
?>