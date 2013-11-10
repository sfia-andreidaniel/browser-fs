<?php

    require_once "OneDB.class.php";
    
    try {
    
        $my = new OneDB();
    
        $widget = $my->categories( 
            array(
                "name" => "digi24.ro"
            )
        )->get(0)->views()->{"category.categoryView"};

        echo "HTML:\n";

        echo $widget->run();
        
        echo "\nDEPENDENCIES:\n";
        
        print_r( $widget->dependencies() );

    } catch (Exception $e) {
        echo "Exception: ", $e->getMessage(),"\nFILE: ", $e->getFile(),"\nLINE: ", $e->getLine(), "\n";
    }

?>