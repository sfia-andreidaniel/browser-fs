<?php

    try {

    require_once "OneDB_Connector.class.php";

    $connector = new OneDB_Connector( $argv[1] );
    
    print_r( $connector->getItems( $argv[2] ));
    
    } catch (Exception $e) {
        echo $e->getMessage(), "\n";
    }

?>