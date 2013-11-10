<?php

    require_once __DIR__ . '/bootstrap.php';
    
    $my = Object( 'OneDB.Client', 'loopback', 'andrei' );
    
    $obj = $my->getElementByPath( '/First article' );
    
    $obj->data->isDocumentTemplate = TRUE;
    
    print_r( $obj->toObject() );
    
    
?>