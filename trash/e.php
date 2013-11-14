<?php

    require_once "bootstrap.php";
    
    $o = Object( 'Exception.OneDB', "This is an exception", 10 );

    echo $o->getCode(), "\n";
    
?>