<?php

    require_once __DIR__ . "/bootstrap.php";
    
    //$connection = OneDB::connect( 'loopback', 'root', 'toor' );
    
    //$file = $connection->getElementByPath( '/myfile' );
    
    //print_r( $file->views->getView( 'item', 'index' )->run() );
    
    //print_r( $file->views->enumerateViews() );
    
    //$file->views->deleteView( 'item.index.Document' );
    
    $frontend = Object( 'OneDB.Frontend', 'simple' );
    
    $frontend->begin->add( 'script', 'js/foo.js' );
    $frontend->begin->add( 'script', 'js/bar.js' );
    $frontend->begin->add( 'css', 'css/bootstrap.css' );
    $frontend->begin->add( 'script', 'function( foo ) {}', TRUE );
    $frontend->begin->add( 'css', 'body { background-color: red; }', TRUE );

    $frontend->begin->add( 'code', '<meta name=og:facebook value="facebook" />' );

    $frontend->end->add( 'script', 'js/foo.js' );
    $frontend->end->add( 'script', 'js/bar.js' );
    $frontend->end->add( 'css', 'css/bootstrap.css' );
    $frontend->end->add( 'script', 'function( foo ) {}', TRUE );
    $frontend->end->add( 'css', 'body { background-color: red; }', TRUE );
    
    echo $frontend->getText();
?>