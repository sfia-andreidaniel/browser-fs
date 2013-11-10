<?php

    /* Get configuration */
    
    for ($i=1, $len=count($argv); $i<$len; $i++) {
        if ($argv[$i] == '--config') {
            if (!isset( $_SESSION ))
                @session_start();
            
            $_SESSION['onedb_connection'] = json_decode( @$argv[$i + 1], TRUE );
        }
    }

    function error( $msg, $errorCode = 100 ) {
        header("HTTP/1.1 500 Server error");
        echo $msg,"\n";
        exit( $errorCode );
    }

    $ffmpeg = trim(`which ffmpeg`);
    
    if (empty( $ffmpeg ) || !file_exists( $ffmpeg ))
        error("ffmpeg package not found!");

    $mediaID = isset( $argv[1] ) ? $argv[1] :
        (isset( $_GET['movie'] ) ? $_GET['movie'] : error("no movie present!") );
    
    if (!preg_match('/^([a-f0-9]+)\.(flv|mp4)$/i', $mediaID, $matches ))
        error("movie is in format: OneDB_ArticleID.movieExtension");
    else {
        $movieID         = strtolower($matches[1]);
        $transcodeFormat = strtolower($matches[2]);
    }
    
    //echo "movie: $movieID,\nformat: $transcodeFormat\n";
    
    chdir( dirname(__FILE__) ."/.." );
    
    require_once "OneDB.class.php";
    
    $my = new OneDB();
    
    $article = $my->articles(
        array(
            "_id" => new MongoId( $movieID )
        )
    );
    
    if (!$article->length)
        error("Article not found!");
    
    $article = $article->get(0);
    
    if (!preg_match('/^File video\//', $article->type))
        error("File is not a video!");
    
    $transcoders = $article->transcoders;
    
    $MODIFIED = FALSE;
    
    if (empty($transcoders)) {
        $transcoders = array();
        $MODIFIED = TRUE;
    }
    
    if (
        isset( $transcoders[$transcodeFormat]) && 
        preg_match('/^[a-f0-9]+$/', $transcoders[$transcodeFormat])
    ) error("ALLREADY_TRANSCODED: " . $transcoders[$transcodeFormat]);
    
    if (
        isset( $transcoders[$transcodeFormat]) && 
        preg_match('/^[a-f0-9]+$/', $transcoders[$transcodeFormat])
    ) error("ALLREADY_TRANSCODING IN: " . $transcoders[$transcodeFormat]);

    /* Dump file on disk */

    $fileOnDisk = tempnam( sys_get_temp_dir(), 'onedb_transcoder-' );
    
    $transcoders[$transcodeFormat] = "$fileOnDisk.$transcodeFormat";
    $article->transcoders = $transcoders;
    $article->save();

    $sout = $article->getFile()->getResource();
    $sin  = @fopen( $fileOnDisk, 'w' ) or error("Could not open temp file $fileOnDisk for write!");
    
    while (!feof( $sout )) {
        $buff = fread( $sout, 65535 );
        fwrite( $sin, $buff );
    }
    
    fclose( $sin );
    fclose( $sout );

    //echo "Created file $fileOnDisk\n";

    /* Create ffmpeg process */
    
    $cmd = "$ffmpeg -i \"$fileOnDisk\" -sameq -vcodec libx264 -acodec libfaac \"$fileOnDisk.$transcodeFormat\"";
    
    $descriptorspec = array(
        0 => array('pipe', 'r'), //stdin
        1 => array('pipe', 'w'), //stdout
        2 => array('pipe', 'w')  //stderr
    );
    
    $process = @proc_open( $cmd, $descriptorspec, $pipes );
    
    if (!is_resource( $process )) {
        /* Delete files first ... */
        @unlink( $fileOnDisk );
        @unlink( "$fileOnDisk.$transcodeFormat" );
        error("Could not create transcoder process!");
    }
    
    $outputClosed = $errorClosed = FALSE;
    $logOutput = $logError = "";
    
    do {
        if (!$outputClosed && !feof( $pipes[1] ))
            $logOutput .= fread( $pipes[1], 2048 );
        else $outputClosed = TRUE;
        
        if (!$errorClosed && !feof( $pipes[2] ))
            $logError .= fread( $pipes[2], 2048 );
        else $errorClosed = TRUE;
        
    } while (!$outputClosed || !$errorClosed );

    //echo "Process finished:\nInput: $logInput\n\nOutput:\n$logOutput\n\nErrors:\n$logError\n\n";
    
    if (!file_exists("$fileOnDisk.$transcodeFormat") || !filesize("$fileOnDisk.$transcodeFormat") ) {
        /* Delete files first ... */
        @unlink( $fileOnDisk );
        @unlink( "$fileOnDisk.$transcodeFormat" );
        error("Could not create transcoder process!\n\nTranscoder Output Log:\n$logError");
    }
    
    /* TODO: If destination format is FLV, then add file seek indexes with flvtool 
     */
    
    $flvtool2Output = "";
    
    if ($transcodeFormat == 'flv') {
        
        $flvtool2 = escapeshellarg( trim(`which flvtool2`) );
        $cmd = "$flvtool2 -U " . escapeshellarg( "$fileOnDisk.$transcodeFormat" );
        
        $output = `$cmd`;
        
        $flvtool2Output = "\n\nDoing flvtool2:\nCMD = $cmd\nOUT = \n$output";

    }
    
    // Put transcode results in a log file in /tmp/
    @file_put_contents( sys_get_temp_dir() . DIRECTORY_SEPARATOR . "transcoder.log", $logError . $flvtool2Output );

    /* Store file in database and update video file */
    
    $gridFS = $article->_collection->db->getGridFS();
    $fileID = $gridFS->storeFile( "$fileOnDisk.$transcodeFormat", array( "_parent" => new MongoId($movieID) ) );
    
    $transcoders[$transcodeFormat] = "$fileID";
    $article->transcoders = $transcoders;

    $_unlink = $article->_unlink;
    $_unlink = is_array( $_unlink ) ? $_unlink : array();
    
    $_unlink[] = array(
        'collection' => '@files',
        'id' => "$fileID"
    );
    
    $article->_unlink = $_unlink;
    
    $article->save();
    
    /* Delete files first ... */
    @unlink( $fileOnDisk );
    @unlink( "$fileOnDisk.$transcodeFormat" );
    
    echo "OK $fileID $fileOnDisk.$transcodeFormat";

?>