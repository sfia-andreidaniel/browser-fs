<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . "/../../bootstrap.php";
    
    $term = Object( 'Utils.Terminal' );
    
    
    echo "onedb version: ", $term->color( '2.0', 'light_green'), "\r";
    echo "cli   version: ", $term->color( '1.0', 'light_green'), "\r";
    
?>