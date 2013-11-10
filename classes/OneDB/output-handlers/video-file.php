<?php

    if (!isset($_SESSION))
        session_start();
    
    set_time_limit( 600 );
    error_reporting( E_ALL );
    ini_set( 'display_errors', 'on' );

    //file_put_contents( "/tmp/streamer.log", @json_encode( $_GET ). "\n" . @json_encode($_SERVER) . "\n\n", FILE_APPEND );

    function error($str, $code = 100) {
        header("HTTP/1.1 500 Server error");
        echo $str;
        exit( $code );
    }

    function startTranscoder( $fileID, $format, $cfg, $wait = FALSE ) {
        if (!$wait) {
            $screen = trim( `which screen` );
            if (empty( $screen ))
                error("screen command not found!");
            else
                $screen = escapeshellarg( $screen ) . " -d -m ";
        } else $screen = '';
        
        $php = trim( `which php` );
        if (empty( $php ))
            error("php command not found!");
        else
            $php = escapeshellarg( $php );
        
        $transcoder = escapeshellarg( realpath( "video-transcoder.php" ) );
        
        $cmd = "$screen$php $transcoder $fileID.$format --config ". escapeshellarg($cfg);
        
        $result = trim(`$cmd`);
        
        if (!$wait) {
            //sleep( 30 ); //give transcoder enough time to dump the file to disk, etc.
            return TRUE;
        } else {
            
            return preg_match ('/^OK /', $cmd );
            
        }
        
        //echo $cmd;
    }

    $fileID = isset($_GET['fileID']) ? strtolower($_GET['fileID']) : die("Which fileID?");
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : die("Which format?");
    
    $formats = array(
        'flv' => 'video/x-flv',
        'mp4' => 'video/mp4'
    );
    
    require_once dirname( __FILE__ ) . "/../OneDB.class.php";
    
    $my = new OneDB();
    
    $query = $my->articles(
        array(
            "_id" => MongoIdentifier( $fileID ),
            'type' => 'File'
        )
    );
    
    if (!$query || !$query->length)
        error("[Before starting transcoder] Video file #$fileID not found!");
    
    $video = $query->get(0);

    $geoBlocking = $video->geoBlocking;
    
    if (is_array( $geoBlocking ) && count( $geoBlocking ) ) { 

        if ( $block = $my->contentGeoBlocked( $video ) ) {
            require_once "utils.inc.php";
            http403( "Sorry, this content is not available in your country!" );
        } else {
            if ($block == 0) {
                header("X-CDN-Skip: TRUE");
                header("X-CDN-GeoBlocking: ". implode(',', $geoBlocking ) );
            }
        }
    
    }

    if (!$video->_getStorage()->bestTranscodeMatch( $format ))
        error("Format '$format' not supported!");
    
    if ( $video->mime == $formats[$format] ) {
        /* Check if native file format is equal with video file format */
    
        $stream = $video->getFile();
        
        /* End of checking */
    } else {
    
        /* Begin serving transcoded version */
    
        $transcoders = $video->transcoders;
        $transcoders = is_array( $transcoders ) ? $transcoders : array();
    
        if (!isset( $transcoders[$format] )) {
        
            if (!startTranscoder($fileID, $format, $my->config(), @$_GET['wait'] == 1))
                error("Error transcoding file!");
        
            unset( $video );
            
            /* We now dump the default transcoder file */
            header("Content-Type: " . $formats[$format]);
            header("X-CDN-Skip: TRUE");
            readfile( dirname(__FILE__) . "/transcoding.$format" );
            die('');
            
        }
        
        if (!isset( $transcoders[$format] ))
            error("[After starting transcoder] No transcoding available yet, please try again later");
            
        switch (true) {
            
            case preg_match('/^[a-f0-9]+$/', $transcoders[ $format ]):
                //A transcoded file exists
                break;
            
            default:
                /* We now dump the default transcoder file */
                header("Content-Type: " . $formats[$format]);
                readfile( dirname(__FILE__) . "/transcoding.$format" );
                die('');
    
                break;
        }
        
        //die("Dumping file #" . $transcoders[$format]);
        
        $stream = $video->_collection->db->getGridFS()->findOne(
            array(
                "_id" => MongoIdentifier( $transcoders[ $format ] )
            )
        );
        
    } //End serving from transcoded version stream

    if (!$stream)
        error("Transcoded file not found on OneDB filesystem");

    $contentLength = $stream->getSize();
    $start = isset( $_GET['start'] ) ? (int)$_GET['start'] : 0;
    $contentLength -= $start;
    
    if ($contentLength < 0)
        error("Seek outside stream file size");
    
    header("Content-Type: " . $formats[ $format ]);
    header("Content-Length: " . $contentLength);
    
    $stream = $stream->getResource();
    
    if ($start > 0)
        fseek( $stream, $start );
    
    while (!feof( $stream )) {
        echo fread( $stream, 100000 );
        flush();
    }
    
    fclose( $stream );

?>