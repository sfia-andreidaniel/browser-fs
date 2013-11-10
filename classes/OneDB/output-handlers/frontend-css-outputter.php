<?php

    $resource = isset($_GET['resource'] ) ? $_GET['resource'] : "";

    if (!is_string($resource))
        die('bad resource input!');

    switch (TRUE) {
        
        case in_array( $resource, array('', '/' ) ):
        
            $css = '';
            
            $files= scandir('components/frontend/css/');
            
            foreach ($files as $file) {
                if ( preg_match('/\.css$/', $file) ) {
                    $css .= ( "\n\n/* file: $file */\n" . file_get_contents( 'components/frontend/css/' . $file ) );
                }
            }
            
            header("Content-Type: text/css");
            echo $css;
        
            break;
        
        default:
            $components = realpath( 'components/frontend/css/' );
            
            $file = realpath( 'components/frontend/css/' . $resource );
            
            if ( strpos( "$file", $components ) !== 0 || !file_exists( $file )) {
                header("HTTP/1.1 404 Not found");
                echo "$resource - Not found!";
                die('');
            }
            
            switch (TRUE) {
                case preg_match('/\.css$/i', $file ):
                    header("Content-Type: text/css");
                    break;
                case preg_match('/\.png$/i', $file ):
                    header("Content-Type: image/png");
                case preg_match('/\.jp(e)?g$/i', $file ):
                    header("Content-Type: image/jpg");
                case preg_match('/\.gif$/i', $file ):
                    header("Content-Type: image/gif");
            }
            
            echo file_get_contents( $file );
            
            break;
            
        
    }

?>