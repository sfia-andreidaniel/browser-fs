<?php

    require_once "utils.inc.php";
    
    define( 'ONEDB_MAX_REMOTE_FILE_SIZE', 10000000 );
    
    error_reporting( E_ALL );
    ini_set('display_errors', 'on');
    
    $path = isset($_GET['file']) ? $_GET['file'] : ( 
        isset( $argv[1] ) ? $argv[1] : http404()
    );
    
    $settings = isset( $_GET['settings'] ) ? $_GET['settings'] : (
        isset( $argv[2] ) ? $argv[2] : ""
    );
    
    $hash = array();
    
    $modifyOnTheFly = FALSE;
    
    if (!empty( $settings )) {
        //Parse settings
        $parts = explode(',', $settings);
        foreach ($parts as $part) {
            $arr = explode("=", $part);
            if (count($arr) > 1) {
                switch ($arr[0]) {
                    case 'width':
                    case 'height':
                    case 'crop':
                    case 'cut':
                        $modifyOnTheFly = TRUE;
                    case 'cache':
                        $GLOBALS[ $arr[0] ] = $arr[0] != 'cut' ? (int)$arr[1] : $arr[1];
                        $hash[] = $arr[0] . '=' . (int)$arr[1];
                        break;
                    default:
                        http500("Invalid setting: " . $arr[0] );
                }
            } else
                http500("Settings should be in format name=value");
        }
    }

    if ( isset( $cut ) ) {
        if ( preg_match('/^([\d]+)-([\d]+)-([\d]+)-([\d]+)$/', $cut, $matches) ) {
            /* Crop original picture ... */
            if ($cut == '0-0-0-0')
                unset( $cut );
            else {
                for ( $i=1; $i<5; $i++ ) {
                    $matches[$i] = (int)$matches[$i];
                    if ( $matches[$i] < 0 )
                        $matches[$i] = 0;
                    if ( $matches[$i] > 100 )
                        $matches[$i] = 100;
                }
                
                $cut = array('top' => $matches[1], 'right' => $matches[2], 'bottom' => $matches[3], 'left' => $matches[4]);
            }
        } else {
            http500("Invalid value for the 'cut' transformer!");
        }
    }
            

    
    $hash = md5(implode(",", $hash));
    
    require_once dirname(__FILE__)."/../OneDB.class.php";
    
    $pathType = NULL;
    
    try {
    
        if (!isset($_SESSION))
            session_start();
    
        $my = new OneDB();
        
        if (preg_match('/^[a-f0-9]{24}$/', $path)) {
            
            $pathType = 'id';
            
        } else 
        if ( preg_match('/^http(s)?\:\/[a-zA-Z\d]/', $path ) ) {
            
            $pathType = 'url';
            
            $path = preg_replace('/^http(s)?\:\/([a-zA-Z\d])/', 'http$1://$2', $path );
            
        } else {
        
            $pathType = 'name';
        
            //path by name
            $parts = explode('/', trim($path, ' /'));

            $category = "/" . implode('/', array_slice( $parts, 0, count( $parts ) - 1));
            $category = $category == "/" ? "/" : "$category/";
            
            $file     = end( $parts );
            
            $pathByID = FALSE;
        }
        
        switch ( $pathType ) {
            
            case 'id':
                $fileDB = $my->articles(
                    array(
                        '_id' => new MongoId( $path )
                    )
                );
                break;
                
            case 'name':
                $fileDB = $my->categories(
                    array(
                        'selector' => $category
                    )
                )->articles(
                    array(
                        'name' => $file
                    )
                );
                break;
            
            case 'url':
                break;
        }
        
        // The stream to the file is a OneDB file
        
        if ( in_array( $pathType, array( 'id', 'name' ) ) ) {
        
            if (!$fileDB || !$fileDB->length)
                http404();
        
            $fileDB = $fileDB->get(0);
        
            /* Check for geoBlocking functionality */
            
            $geoBlocking = $fileDB->geoBlocking;
        
            if ( is_array( $geoBlocking ) && count( $geoBlocking ) ) {
            
                if ( $block = $my->contentGeoBlocked( $fileDB ) ) {
                    http403("Sorry, this content is not available in your country!");
                } else {
                    if ($block == 0) {
                        //Content is not cacheable
                        header("X-CDN-Skip: TRUE");
                        header("X-CDN-GeoBlocking: " . implode(',', $geoBlocking ) );
                    }
                }
            
            }
            
            if (!preg_match('/^File (image|video)\/([^*]+)$/', $fileDB->type))
                http500("Item is not a valid image or video file but a $fileDB->type");
            
            if ($modifyOnTheFly === FALSE) {
                
                $imageStream = $fileDB->_getStorage()->getImageStream();
                
                if ( method_exists( $imageStream, 'getURL' ) ) {
                    header("Location: ". $imageStream->getURL() );
                    die('');
                }
            }
            
            $cacheFileName = $my->temp( $fileDB->_id ). $fileDB->_id . ".$hash.cache";
        
            if (file_exists( $cacheFileName ) && !isset($_GET['disable-cache'])) {
                header("Content-Type: " . ( preg_match('/^image/', $fileDB->mime ) ? $fileDB->mime : 'image' ) );

                $expires = 60*60*24*14;
                header("Pragma: public");
                header("Cache-Control: maxage=".$expires);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

                readfile( $cacheFileName );
            
                exit(0);
            }
        
            if (preg_match('/^File video\//', $fileDB->type)) {
                /* This is a vide file, we should read the property icon */
                $icon = $fileDB->icon;
                if (empty($icon)) {
                    //print_r( $fileDB->toArray() );
                    http500("No icon attached to this video!");
                }
                
            
                $iconFileID = end(explode(':', $fileDB->icon));
                
                unset( $fileDB );
                
                $fileDB = $my->articles(
                    array(
                        "_id" => new MongoId( $iconFileID )
                    )
                );
                
                if (!$fileDB->length)
                    http500("Video icon file not found on oneDB server!");
                
                $fileDB = $fileDB->get(0);

            }
        
            // END OF LOGIC IF THE FILE IS SITUATED IN THE ONEDB.
        } else {
            
            $fakeFileID = md5( $path );
            
            $cacheFileName = $my->temp( $fakeFileID ) . $fakeFileID . ".url.$hash.cache";
            
            if ( file_exists( $cacheFileName ) && !isset( $_GET['disable-cache'] ) ) {
                $expires = 60 * 60 * 24 * 14;
                header("Content-Type: image");
                header("Pragma: public");
                header("Cache-Control: maxage=$expires");
                header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
                readfile( $cacheFileName );
                exit(0);
            }
            
            class AbstractFileURLWrapper {
                
                protected $url = FALSE;
                
                public $mime = 'image';
                
                public function __construct( $url ) {
                    $this->url = $url;
                }
                
                public function getBytes() {
                    $handle = @fopen( $this->url, 'r' );
                    
                    if ( !$handle )
                        throw new Exception("Cannot get file url ($this->url) bytes contents!");
                    
                    $out = '';
                    $bytes = 0;
                    
                    while ( !feof( $handle ) ) {
                        $chunk = @fread( $handle, 8192 );
                        
                        if ( !empty( $chunk ) ) {
                            $out .= $chunk;
                        } else
                            throw new Exception("NULL chunk detected in HTTP transport!");
                        
                        $bytes += strlen( $chunk );
                        
                        if ( $bytes > ONEDB_MAX_REMOTE_FILE_SIZE )
                            throw new Exception("Remote URL file too large!");
                    }
                    
                    @fclose( $handle );
                    
                    return $out;
                }
                
                public function __call( $methodName, $arguments ) {
                    return $this;
                }
                
            }
            
            $fileDB = new AbstractFileURLWrapper( $path );
            
            // END OF LOGIC IF THE FILE IS SITUATED IN THE WORLD WIDE WEB
        }
        
        
        
        if (!isset( $width ) && !isset( $height ) && !isset( $cut ) ) {
            //We deliver the file unmodified if no width and height is specified
            header( "Content-Type: $fileDB->mime");
            file_put_contents( $cacheFileName, $fileDB->getFile()->getBytes());
            readfile( $cacheFileName );
        } else {
            //We resize the image ...
                
            $bytes = $fileDB->_getStorage()->getImageStream()->getBytes();
            
            $img = @imagecreatefromstring( $bytes );
            
            if (!is_resource( $img ))
                http500("Image error - Error loading image");
            
            if ( isset( $cut ) && !isset( $width ) && !isset( $height ) ) {
                $width = imagesx( $img );
                $height= imagesy( $img );
            }
            
            
            $w = imagesx( $img );
            $h = imagesy( $img );
            
            $ratio = $w/$h;
            
            if (!isset( $width )) {
                $width = floor( $height * $ratio );
            } else if (!isset( $height )) {
                $height= floor( $width / $ratio );
            }
            
            $x = 0;
            
            if (isset($crop) && $crop == 1) {
                $ratio = max($width/$w, $height/$h);
                $h = $height / $ratio;
                $x = ($w - $width / $ratio) / 2;
                $w = $width / $ratio;
            }
            
            $new = imagecreatetruecolor( $width, $height );
            
            if (in_array( strtolower( $fileDB->mime ), array('image/gif', 'image/png') )) {
                imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
                imagealphablending($new, false);
                imagesavealpha($new, true);
            }
            
            
            imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);
            imagedestroy( $img );
            
            
            if ( isset( $cut ) ) {
                
                $percentWidth  = imagesx( $new ) / 100;
                $percentHeight = imagesy( $new ) / 100;
                
                $canvasWidth = floor( $percentWidth * ( 100 - $cut['left'] - $cut['right'] ) );
                $canvasHeight= floor( $percentHeight * ( 100 - $cut['top'] - $cut['bottom'] ) );
                
                $canvas = imagecreatetruecolor( $canvasWidth, $canvasHeight );
                imagecopyresampled( $canvas, $new, 
                    0, 0, 
                    floor( $percentWidth * $cut['left'] ), floor( $percentHeight * $cut['top'] ), 
                    $canvasWidth, $canvasHeight, $canvasWidth, $canvasHeight );
                
                imagedestroy( $new );
                
                $new = $canvas;
            }
            
            
            if (isset($cache) && $cache) {
                $expires = 60*60*24*14;
                header("Pragma: public");
                header("Cache-Control: maxage=".$expires);
                header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
            }
            
            /* Store file into cache */
            
            //create file
            switch ( strtolower( $fileDB->mime ) ) {
                case 'image/png':
                    @imagepng( $new, $cacheFileName );
                    
                    if (!file_exists( $cacheFileName ))
                        throw new Exception("Could not store file in cache file: $cacheFileName");
                    else 
                        header("Content-Type: image/png");
                    break;
                    
                case 'image/jpg':
                case 'image/jpeg':
                    @imagejpeg( $new, $cacheFileName );
                    
                    if (!file_exists( $cacheFileName ))
                        throw new Exception("Could not store file in cache file: $cacheFileName");
                    else 
                        header("Content-Type: image/jpeg");
                    break;
                case 'image/gif':
                    imagegif( $new, $cacheFileName );
                    
                    if (!file_exists( $cacheFileName ))
                        throw new Exception("Could not store file in cache file: $cacheFileName");
                    else 
                        header("Content-Type: image/gif");
                    break;
                default:
                    imagepng( $new, $cacheFileName );
                    
                    if (!file_exists( $cacheFileName ))
                        throw new Exception("Could not store file in cache file: $cacheFileName");
                    else 
                        header("Content-Type: image/png");
                    break;
            }
            
            imagedestroy( $new );

            readfile( $cacheFileName );
        }
        
        //print_r( $fileDB->toArray() );
    
    } catch (Exception $e) {
        http500( $e->getMessage()."\n\n" . $e->getFile() . "\n\n" . $e->getLine() );
    }
    

?>