<?php

    if ( !extension_loaded( 'mongo' ) )
        die( "Mongo support not found!" );

    require_once __DIR__ . '/bootstrap.php';
    
    require_once __DIR__ . '/classes/onedb/Sys/Security/User.class.php';
    require_once __DIR__ . '/classes/onedb/Sys/Security/Group.class.php';
    
    try {
    
        $connection = Object('OneDB')->connect( 'loopback', 'root', 's34g4t3' );
        
        //echo $connection->__mux(), "\n";
        
        // echo $connection->get_shadow_collection(), "\n";
    
        //Sys_Security_User::useradd( 'loopback', 'root', 's34g4t3' );
        
        //Sys_Security_Group::groupadd( 'loopback', 'root' );
        
        echo $connection->sys->user( 'root' ), "\n";
    
    } catch ( Exception $e ) {
        
        echo Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), "\n";
        
    }
    
?>