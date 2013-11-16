<?php

    if ( !extension_loaded( 'mongo' ) )
        die( "Mongo support not found!" );

    require_once __DIR__ . '/bootstrap.php';
    
    require_once __DIR__ . '/classes/onedb/Sys/Security/User.class.php';
    require_once __DIR__ . '/classes/onedb/Sys/Security/Group.class.php';
    require_once __DIR__ . '/classes/onedb/Sys/Security/Management.class.php';
    
    try {
    
        $connection = Object('OneDB')->connect( 'loopback', 'andrei', 'new password' );
        
        //echo $connection->__mux(), "\n";
        
        // echo $connection->get_shadow_collection(), "\n";
    
        //Sys_Security_Management::useradd( 'loopback', 'andrei', 'nu stiu' );
        
        //Sys_Security_Management::groupadd( 'loopback', 'anonymous' );
        
        //echo $connection->sys->user( 'root' ), "\n";
        
        //Sys_Security_Management::userdel( 'loopback', 'test' );
    
        //$members = $connection->sys->getMembers( 'user', 7 );
        
        //assert( 'is_array( $members )' );
        
        //echo count( $members ), " members\n";
        //print_r( $members );
        
        /*
        Sys_Security_Management::usermod( 'loopback', 'andrei', [
            
            'password' => 'new password',
            'umask' => 512,
            'groups' => [
                ':root',
                'anonymous'
            ]
            
        ] );
        */
        /*
        Sys_Security_Management::groupmod( 'loopback', 'root', [
            
            'users' => [
                '+andrei',
                '-root',
                ':root'
            ],
            'flags' => 512
            
        ] );
        */
        
        echo "the gid of the user $connection->user is ", $connection->user->gid, "\n";
        
        foreach ( $connection->user->groups as $member ) {
            echo implode( ', ', $member->users ), "\n";
        };
        
        //print_r( $connection->user->groups );
    
    } catch ( Exception $e ) {
        
        echo Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), "\n";
        
    }
    
?>