<?php

    require_once "OneDB.class.php";
    
    $my = new OneDB();
    
    $doc = $my->getElementByPath('/Iowa Sen. Harkin will not seek re-election');
    
    echo $doc->html();
    
?>