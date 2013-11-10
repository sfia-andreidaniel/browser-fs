<?php

    require_once "utils.inc.php";
    
    error_reporting( E_ALL );
    ini_set('display_errors', 'on');
    
    $path = isset($_GET['file']) ? $_GET['file'] : ( 
        isset( $argv[1] ) ? $argv[1] : http404()
    );
    
    $parts = explode("___iframe___", $path);
    $path = reset( $parts );
    if (count($parts) > 0)
        $_GET['iframe'] = end( $parts );

    chdir( dirname(__FILE__)."/.." );
    require_once "OneDB.class.php";
    
    try {
    
        $my = new OneDB();
        
        if (preg_match('/^[a-f0-9]+$/', $path)) {
            
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
                    "_id" => new MongoId($path)
                )
            ) :
            $my->categories(
                array(
                    "selector" => $category
                )
            )->articles(
                array(
                    "name" => $file
                )
            );
        
        if (!$fileDB->length)
            http404();
        
        $fileDB = $fileDB->get(0);
        
        if (!preg_match('/^File image\/([^*]+)$/', $fileDB->type))
            http500("Item is not a valid image file but a $fileDB->type");
        
        if (!count( array_values( $_FILES )))
            http500("No files were uploaded");
        
        $file = reset( array_values( $_FILES ));
        
        require_once "MIME/Type.php";
        
        $fileDB->setContent( file_get_contents( $file['tmp_name'] ), MIME_Type::autoDetect($file['tmp_name']) );

        /* Delete all cached versions of the picture from disk!!! */
        
        $cacheFileNames = ($searchFolder = $my->temp( $fileDB->_id ) ) . $fileDB->_id;
        
        $cacheFiles = scandir( $searchFolder );
        foreach ($cacheFiles as $cfile) {
            if (strpos( $cfile, "$fileDB->_id." ) === 0)
                @unlink( $searchFolder . DIRECTORY_SEPARATOR . $cfile );
        }

        $iframe = isset($_GET['iframe']) ? $_GET['iframe'] : "";
        
        if ($iframe != '') {
            $iframeCode = "
            <script>
                try {
                if (window.parent) {
                    var iframe = window.parent.document.querySelector('iframe[name=$iframe]') ;
                    if (iframe)
                        window.parent.window.getOwnerWindow( iframe ).onCustomEvent('picture-saved', true);
                } else {
                    console.error('No window.parent!');
                }
                } catch (e) {
                    console.error( e );
                }
            </script>";
        } else $iframeCode = '<script>console.error("No iframe!");</script>';
        
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        echo "<html><body>File was successfully saved, iframe is: $iframe. $iframeCode</body></html>";
    
    } catch (Exception $e) {
        http500( $e->getMessage()."\n\n" . $e->getFile() . "\n\n" . $e->getLine() );
    }
    

?>