<?php

    if ( !extension_loaded( 'mongo' ) )
        die( "Mongo support not found!" );

    require_once __DIR__ . '/bootstrap.php';
    
    try {
    
        $my = Object( 'OneDB.Client', 'loopback', 'andrei' );
        
        /*
        $myWidget = $my->getElementByPath( '/A widget' );
        
        $myWidget->data->php = file_get_contents( __DIR__ . '/php_code.txt' );
        
        $myWidget->data->html = file_get_contents( __DIR__ . '/html_code.txt' );
        
        $myWidget->data->engine = "html";
        
        $myWidget->save();
        
        echo $myWidget->data->run( [ "foo" => "bar" ] );
        */
        
        /*
        $my->getElementByPath( '/' )->childNodes->each( function( $item ) {
            
            echo $item->name, "\t", $item->type, "\n";
            
            if ( $item->type == 'Category' )

                $item->childNodes->each( function( $item ) {
                    
                    echo "\t", $item->name, "\t", $item->type, "\n";
                    
                } );
            
        } );
        */
        
        // $my->getElementByPath( '/' )->delete();
        
        if ( ( $ws = $my->getElementByPath( '/ws' ) ) === NULL ) {
            $ws = $my->getElementByPath( '/' )->create( 'Category.WebService', 'ws' );
        }
        
        $ws->data->webserviceUrl = 'http://www.rcs-rds.ro/external/epg/channel-data/';
        $ws->data->webserviceConf = [
            'get' => [
                'channel_id' => 904,
                'time_start' => '$func:time'
            ]
        ];
        
        print_r( $ws->data->webserviceConf );
        
        $ws->save();
        
        $ws->data->refresh();
    
    } catch (Exception $e) {
        
        die("Exception: " . $e->getMessage() . "\nline: " . $e->getLine() . "\nfile: " . $e->getFile() );
        
        print_r( $e->getTrace() );
        
    }
    
?>