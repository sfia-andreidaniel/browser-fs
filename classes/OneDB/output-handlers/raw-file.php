<?php

    require_once dirname(__FILE__)."/../OneDB.class.php";
    require_once "utils.inc.php";
    
    $path = isset($argv[1]) ? $argv[1] : 
        ( isset( $_GET['file'] ) ? $_GET['file'] : http404() );

    if (!preg_match('/^[0-9a-f]{24,32}$/', trim($path,"/"))) {
        $parts = explode("/", trim($path," /") );
    
        $category = "/" . implode("/", array_slice( $parts, 0, count($parts)-1 ) );
        $category = $category == "/" ? "/" : "$category/";

        $fileName = end( $parts );

        $isFileByPath = TRUE;
    } else {
        $isFileByPath = FALSE;
        $path = trim( $path, "/" );
    }
    
    try {
        
        if (!isset($_SESSION))
            session_start();
        
        $db = new OneDB();
        
        $theFile = $isFileByPath 
        
        ? (
            $db->categories(
                array(
                    "selector" => $category
                )
            )->articles(
                array(
                    "name" => $fileName
                )
            )->get(0)
        ) : (
            $db->articles(
                array(
                    "_id" => new MongoId( $path )
                )
            )->get(0)
        );
        
        if (!preg_match('/^file([^*]+)?$/i', $theFile->type))
            throw new Exception( "$path is not a file, but a $theFile->type!" );
        
        $geoBlocking = $theFile->geoBlocking;
        
        if ( is_array( $geoBlocking ) && count( $geoBlocking ) ) {
        
            if ( $block = $db->contentGeoBlocked( $theFile ) ) {
                http403("Sorry, this content is not available in your country!");
            } else {
                if ($block == 0) {
                    //Content is not cacheable
                    header("X-CDN-Skip: TRUE");
                    header("X-CDN-GeoBlocking: " . implode(',', $geoBlocking ) );
                }
            }
        
        }
        
        $resource = $theFile->getFile()->getResource();
        
        header("Content-Type: $theFile->mime");
        header("Content-Disposition: attachment; name=" . $theFile->name );
        
        while (!feof( $resource ))
            echo fread( $resource, 8192 );
        
        fclose( $resource );
        
    } catch (Exception $e) {
        http500( $e->getMessage() );
    }

?>