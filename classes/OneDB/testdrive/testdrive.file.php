<?php

    require_once "OneDB.class.php";
    
        $my = new OneDB();
    
        $myFile = $my->categories(
            array(
                "selector" => "/"
            )
        )->get(0)->createArticle('File');
        
        $myFile->storeFile('testdrive.sample.video.file');
        $myFile->_autoCommit = FALSE;

        //$myFile->save();
?>