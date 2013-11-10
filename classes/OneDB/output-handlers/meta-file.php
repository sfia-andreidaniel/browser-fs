<?php

    require_once "utils.inc.php";
    
    error_reporting( E_ALL );
    ini_set('display_errors', 'on');
    
    $path = isset($_GET['file']) ? $_GET['file'] : ( 
        isset( $argv[1] ) ? $argv[1] : http404()
    );

    require_once dirname(__FILE__)."/../OneDB.class.php";
    
    try {
    
        if (!isset($_SESSION))
            session_start();
    
        $my = new OneDB();
        
        $theFile = NULL;
        $propertyName = NULL;
        
        switch (TRUE) {
        
            case preg_match( '/^([a-f0-9]{24,32})\/([\d]+|pages|pdf?|text)$/', $path, $matches ):
                
                $fileID = $matches[1];
                
                $propertyName = $matches[2];
                
                $theFile = $my->articles(
                    array(
                        "_id" => MongoIdentifier( $fileID ),
                        "type"=> "File"
                    )
                );
                
                if (!$theFile->length)
                    throw new Exception("File not found by id $fileID");
                
                $theFile = $theFile->get(0);
                
                break;
                
            default:
                
                $pathParts = explode('/', trim($path, '/') );
                $len = count( $pathParts );
                
                if ($len < 2)
                    throw new Exception("Bad metafile path!");
                
                $filePath = "/" . implode( '/', array_slice( $pathParts, 0, $len - 1 ) );
                
                $propertyName = end( $pathParts );
                
                try {
                    $theFile = $my->getElementByPath(
                        $filePath
                    );
                } catch (Exception $e) {
                    throw new Exception("File '$filePath' was not found in OneDB filesystem!");
                }
                
                break;
        }
        
        $meta = $theFile->metaFile();
        
        $prop = $meta->{"$propertyName"};
        
        if ( !is_array( $prop ) )
            throw new Exception("Invalid property: $propertyName");
        
        header("Content-Type: $prop[mime]");
        header("Content-Length: ". strlen($prop['data']));
        
        if ( $propertyName != 'pdf' ) {
            header("Content-Disposition: inline");
        } else {
            header("Content-Disposition: attachment; name=" . urlencode( $theFile->name ) . ".pdf" );
        }
        echo $prop['data'];
        
        
    } catch (Exception $e) {
        header("Content-Type: text/plain");
        http500( $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() );
    }

?>