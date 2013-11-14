<?php

    if ( !extension_loaded( 'mongo' ) )
        die( "Mongo support not found!" );

    require_once __DIR__ . '/bootstrap.php';
    
    try {
    
        print_r( Object( 'OneDB')->websites );
    
        //$my = Object( 'OneDB' )->login( 'loopback', 'andrei' );
        
        // $my->getElementByPath( '/' )->create( 'Widget', 'widget' )->save();
        
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
        
        /*
        
        if ( ( $ws = $my->getElementByPath( '/ws' ) ) === NULL ) {
            $ws = $my->getElementByPath( '/' )->create( 'Category.WebService', 'ws' );
        }
        
        $ws->data->webserviceTtl = 60;
        
        $ws->data->webserviceUrl = 'http://www.rcs-rds.ro/external/epg/channel-data/';
        $ws->data->webserviceConf = [
            'get' => [
                'channel_id' => 904,
                'time_start' => '$func:time'
            ]
        ];
        
        $ws->childNodes->each( function( $item ) {
            echo $item->id, "\t", $item->name, "\n";
        } );
        
        */
        
        /*
        if ( ( $qr = $my->getElementByPath( '/qr' ) ) === NULL ) {
            
            $qr = $my->getElementByPath( '/' )->create( 'Category.Search', 'qr' );
            
            $qr->data->query = [
                '_type' => 'Document'
            ];
            
            $qr->save();
        
        }
        
        $qr->childNodes->each( function( $item ) {
            
            echo $item->url, "\n";
            
        } );
        */
        
        /*
        if ( ( $ag = $my->getElementByPath( '/ag' ) ) === NULL ) {
            
            $ag = $my->getElementByPath( '/' )->create( 'Category.Aggregator', 'ag' );
            
            $ag->data->paths = [
                '/ws',
                '/qr'
            ];
            
            $ag->save();
            
        }
        
        $ag->childNodes->find([
            'name' => 'STIRI'
        ])->each( function( $item ) {
            
            echo $item->url, "\n";
            
        } );
        */
    
    } catch (Exception $e) {
        
        die("Exception: " . $e->getMessage() . "\nline: " . $e->getLine() . "\nfile: " . $e->getFile() );
        
        print_r( $e->getTrace() );
        
    }
    
?>