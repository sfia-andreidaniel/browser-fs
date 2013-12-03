<?php

    require_once __DIR__ . "/bootstrap.php";
    
    $connection = OneDB::connect( 'loopback', 'root', 'toor' );
    
    /*
    $file = $connection->getElementByPath( '/myfile' );
    
    echo $file->data->getFileFormat( '360p.mp4' ), "\n";
    */
    
    $router = $connection->getRouter( '/bfs/picture/529d9b5a888218bd0e8b4567.jpg#width=400,height=300' );
    
    print_r( Object( 'RPC.Muxer' )->mux( $connection ) );

?>