<?php

    require_once "OneDB.class.php";
    
    $my = new OneDB();
    

    $result = $my->database()->exec("db.articles.find()");
    print_r( $result );

?>