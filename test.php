<?php

    require_once __DIR__ . "/bootstrap.php";
    
    $connection = OneDB::connect( 'loopback', 'root', 'toor' );
    
    $file = $connection->getElementByPath( '/myfile' );
    
    print_r( $file->views->getView( 'item', 'index' )->run() );
    
?>