<?php

    require_once __DIR__ . "/bootstrap.php";
    
    $parser = Object( 'Utils.Parsers.Path' );
    
    for ( $i=0; $i<10000; $i++ ) {
    
    if ( $parser->isCommonParent( '/foo car/mar', '/foo+car/phar/../foo' ) )
        echo "yes\n";
    else
        echo "no\n";
    
    }

?>