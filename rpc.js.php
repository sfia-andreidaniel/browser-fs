<?php

    require_once __DIR__ . '/bootstrap.php';
    
    $rpc = Object( 'RPC.Assembler' );
    
    header("Content-Type: text/javascript" );
    
    echo $rpc->code, "\n";

?>