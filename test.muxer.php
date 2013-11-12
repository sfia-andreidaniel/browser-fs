<?php

    require_once __DIR__ . '/bootstrap.php';
    
    $my = Object( 'OneDB.Client', 'loopback', 'andrei' );
    
    $muxer = Object( 'RPC.Muxer' );
    
    if ( $muxer->is_primitive_type( $my ) )
        echo "* IS primitive!\n";
    else
        echo "* NOT primitive\n";
    
    if ( $muxer->is_composed_type( $my ) )
        echo "* IS composed\n";
    else
        echo "* NOT composed\n";
    
    if ( $muxer->is_instantiated_type( $my ) )
        echo "* IS instantiated\n";
    else
        echo "* NOT instantiated\n";
    
    echo json_encode( $muxer->mux( $my->getElementByPath( '/' )->childNodes ) );
?>