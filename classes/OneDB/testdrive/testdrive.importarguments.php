<?php

    $ENV = array(
        "foo" => "foo",
        "bar" => "bar"
    );
    
    require_once "OneDB.inc.php";
    
    eval( $code = OneDB_ImportArgumentsToLocalScope( $ENV, 'ENV' ) );
    
    echo $code;
    
    print_r( $GLOBALS );

?>