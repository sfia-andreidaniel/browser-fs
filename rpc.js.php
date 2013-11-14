<?php

    /* The role of this file is to dump the OneDB rpc javascript
       code to the browser
     */

    require_once __DIR__ . '/bootstrap.php';
    
    $rpc = Object( 'RPC.Assembler' );
    
    header("Content-Type: text/javascript" );
    
    echo $rpc->code, "\n";

?>