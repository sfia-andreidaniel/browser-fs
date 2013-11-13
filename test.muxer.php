<?php

    require_once __DIR__ . '/bootstrap.php';
    
    //$my = Object( 'OneDB.Client', 'loopback', 'andrei' );
    
    $muxer = Object( 'RPC.Demuxer' );
    
    
    for ( $i=0; $i<2; $i++ ) {
    
        $muxer->demux( [
            "type"=>"OneDB_Object",
            "v"=>[
                "type"=>"window.Array",
                "v"=>[
                    "5280fd2c888218b8348b4567",
                    "loopback:andrei"
                ]
            ]
        ] );
    
    }
    
?>