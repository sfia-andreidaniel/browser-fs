<?php

    require_once "OneDB.class.php";
    
    $my = new OneDB();
    
    print_r( OneDB_JSONModifier( array(
        'a' => 2,
        'ts' => "OneDB( test_function )"
    ) ) );

?>