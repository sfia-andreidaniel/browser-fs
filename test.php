<?php

    require_once __DIR__ . "/bootstrap.php";
    
    $parser = Object( 'Utils.Parsers.Path' );
    
    if ( $parser->isCommonParent( '/foo car/mar', '/foo+car/phar/../foo' ) )
        echo "yes\n";
    else
        echo "no\n";
    
    echo $parser->decode( '/foo%20bar/car+mar' ), "\n";
    
    if ( $parser->isEqual( '/foo%20bar/car+mar', '/foo+bar/../foo+bar//car+mar/.' ) )
        echo "equal\n";

?>