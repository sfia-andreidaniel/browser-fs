<?php

    require_once "utils.inc.php";
    
    error_reporting( E_ALL );
    ini_set('display_errors', 'on');
    
    $path = isset($_GET['file']) ? $_GET['file'] : ( 
        isset( $argv[1] ) ? $argv[1] : http404()
    );
    
    $settings = isset( $_GET['settings'] ) ? $_GET['settings'] : (
        isset( $argv[2] ) ? $argv[2] : ""
    );
    
    $hash = array();
    
    $hash = md5(implode(",", $hash));
    
    require_once dirname(__FILE__)."/../OneDB.class.php";
    
    try {
    
        if (!isset($_SESSION))
            session_start();
    
        $my = new OneDB();
        
        if (preg_match('/^[a-f0-9]{24,32}+$/', $path)) {
        
            $pathByID = true;
            
        } else {
            //path by name
            $parts = explode('/', trim($path, ' /'));

            $category = "/" . implode('/', array_slice( $parts, 0, count( $parts ) - 1));
            $category = $category == "/" ? "/" : "$category/";
            
            $file     = end( $parts );
            
            $pathByID = FALSE;
        }
        
        $fileDB = $pathByID ? 
            $my->articles(
                array(
                    '_id' => MongoIdentifier($path),
                    'type' => 'File'
                )
            ) :
            $my->categories(
                array(
                    "selector" => $category
                )
            )->articles(
                array(
                    'name' => $file,
                    'type' => 'File'
                )
            );
    
        if (!$fileDB || !$fileDB->length)
            http404();
        
        $fileDB = $fileDB->get(0);
        
        if (!preg_match('/^File video\/([^*]+)$/', $fileDB->type))
            http500("Item is not a valid video file, but a $fileDB->type");
        
        /* Test if the file has a property getVideoSnapshots */
        
        $storage = $fileDB->_getStorage();
        if (method_exists( $storage, 'getVideoSnapshots' ) && method_exists( $storage, 'setVideoSnapshot' )) {
            $snapshots = $storage->getVideoSnapshots();
            
            if (is_array( $snapshots ) && count( $snapshots )) {
                $snapshotIndex = rand( 0, count( $snapshots ) - 1 );

                $storage->setVideoSnapshot( $snapshots[ $snapshotIndex ] );
                
                $image = $fileDB->_getStorage()->getImageStream();
                
                header("Content-Type: image");
                echo $image->getBytes();
                die('');
            }
        }
        
        /* End of testing */
        
        $icon = $fileDB->icon;
        
        if ( strlen( "$icon" ) ) {

            if (!preg_match( '/^onedb\/picture\:([a-f0-9]+)$/', $icon, $iconFile ))
                http500("Unknown video-file icon or no icon was set!");
        
            /* Delete old icon file */
            $iconFileID = MongoIdentifier($iconFile[1]);
        
            try {
                
                $unlinkFile = $my->articles(
                    array(
                        '_id' => $iconFileID
                    )
                )->get(0);

            } catch (Exception $e) {
                $unlinkFile = NULL;
            }
        
        } else $unlinkFile = NULL;
        
        try {
            $result = $fileDB->executePlugin('OneDB_GetVideoThumbnails');
        } catch (Exception $e) {
            die( $e->getMessage() );
            $result = FALSE;
        }
        
        //After resnapshot has been made, delete the old video snapshot
        if ($result === TRUE) {
            if ($unlinkFile !== NULL)
                $unlinkFile->delete();
        } else {
            $fileDB->icon = $icon;
            $fileDB->save();
            throw new Exception("Could not resnapshot video: Failed to execute plugin!");
        }
        
        /* read file, and dump it */

        $icon = $fileDB->icon;

        if (!preg_match( '/^onedb\/picture\:([a-f0-9]+)$/', $icon, $iconFile ))
            http500("Error [after execute resnapshot]: Unknown video-file icon or no icon was set!");
        
        $iconFileID = MongoIdentifier( $iconFile[1] );
        
        $dumpFile = $my->articles(
            array(
                '_id' => $iconFileID
            )
        )->get(0);
        
        header('Content-type: image');
        echo $dumpFile->getFile()->getBytes();
    
    } catch (Exception $e) {
        header("Content-Type: text/plain");
        http500( $e->getMessage()."\n\n" . $e->getFile() . "\n\n" . $e->getLine() );
    }
    

?>