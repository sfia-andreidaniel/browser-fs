<?php

    require_once "../../../template/conf/onedb.cfg.php";

    require_once "OneDB.class.php";

    $my = new OneDB();
    
    $sport = $my->getElementByPath( '/Sport/' );
    
    if ($my->contentGeoBlocked(
                $sport,
                '62.231.97.154'
            )
        ) echo "Access denied!\n"; else echo "Access allowed\n";
?>