<?php

    require_once "OneDB.class.php";
    
    $myDB = new OneDB();
    
    $my = OneDB::get("_default");
    
    var_dump( $my );
    
?>