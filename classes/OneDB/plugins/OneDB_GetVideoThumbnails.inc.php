<?php

    require_once dirname(__FILE__)."/ffmpeg_php.class/ffmpeg_animated_gif.class.php";

    function OneDB_GetVideoThumbnails( $filePath, &$FileArticle ) {
        
        $vSnapshotsID = $FileArticle->_collection->db->categories->findOne(
            array(
                "name" => "video snapshots",
                "_parent" => NULL
            )
        );
        
        if (!empty( $vSnapshotsID ))
            $vSnapshotsID = "$vSnapshotsID[_id]";
        else {
            trigger_error( "Warning: OneDB category '/video snapshots' was not found! Movie snapshot generation aborted!" );
            return;
        }
        
        /*
        $movie = new ffmpeg_movie( $filePath );
        
        if (!@$movie->hasVideo())
            return FALSE;
        
        $duration = (int)@$movie->getDuration();
        $frames   = @$movie->getFrameCount();
        $width    = @$movie->getFrameWidth();
        $height   = @$movie->getFrameHeight();
        
        do {
            $middleFrame = rand( 1, $frames - 1 );
            $frame = $movie->getFrame( $middleFrame );
        } while (!$frame);
        
        $img = $frame->toGDImage();
        
        $jpg = tempnam( sys_get_temp_dir(), "movie-snap-" ) . ".jpg";
        */
        
        $outPrefix = '/tmp/' . time() . '-' . rand( 0, 10000 ). '-' . rand( 0, 10000 );
        
        $cmd = '/bin/bash ' . escapeshellarg( dirname(__FILE__) . DIRECTORY_SEPARATOR . 'video-snapshot' . DIRECTORY_SEPARATOR . 'snapshot.sh' ) . 
               ' ' . escapeshellarg( $filePath ) . " $outPrefix  6";
        
        // die( $cmd );
        
        $result = `$cmd`;
        
        $jpgFile = @file_get_contents( ( $jpg = $outPrefix . '.' . ( 1 + rand( 2, 5 ) ) . '.jpg' ) );
        
        for ($i=1; $i <= 6; $i++) {
            if ($jpg != ( $jpgT = $outPrefix . '.' . $i . '.jpg' ) ) {
                @unlink( $jpgT );
            }
        }
        
        if (!strlen( $jpgFile )) {
            @unlink( $jpg );
            return;
        }
        
        
        try {
        
        $inserted = array(
            "name" => microtime() . "_snapshot.jpg",
            "_parent" => MongoIdentifier( $vSnapshotsID ),
            "type" => "File",
            "mime" => "image/jpeg",
            "size" => strlen( $jpgFile ),
            "fileID" => $FileArticle->_collection->db->getGridFS()->storeFile(
                $jpg
            )
        );
        
        if (! strlen("$inserted[fileID]") ) {
            @unlink( $jpg );
            return;
        }
        
        
        $snapshotFileID = $FileArticle->_collection->db->articles->insert(
            $inserted,
            array(
                'safe' => TRUE
            )
        );
        
        } catch (Exception $e) {
            @unlink( $jpg );
            return;
        }
        
        @unlink( $jpg );
        
        
        //echo "$snapshotFileID\n";
        //echo strlen( $jpgFile ), "\n";
        
        $FileArticle->icon = "onedb/picture:$inserted[_id]";
        
        $FileArticle->_unlink = array(
            array(
                'collection' => 'articles',
                'id'    => $inserted['_id']
            ),
            array(
                'collection' => '@files',
                'id'    => "$inserted[fileID]"
            )
        );
        
        return TRUE;
        
        //echo "File Icon: $FileArticle->icon\n";
        
    }

?>