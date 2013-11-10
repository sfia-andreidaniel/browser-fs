<?php

    require_once "../OneDB.class.php";
    
    $my = new OneDB();
    
    $doc = $my->getElementByPath('/Import/Steaua vs Dinamo');
    
    echo $doc->html();
    
?>