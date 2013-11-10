<?php

    $uri = isset( $_GET['_URI_'] ) ? $_GET['_URI_'] : die("Which _URI_?");
    
    if ($uri[0] == '/') $uri = substr( $uri, 1 );
    
    chdir( dirname( __FILE__ ) );
    
    // die( $uri );
    
    switch (TRUE) {
        
        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)picture-upload:([^*]+)$/', $uri, $matches):
            $_GET['file']               = $matches[3];
            $_GET['ONEDB_AUTH_TOKEN']   = $matches[1];
            require_once "input-handlers/picture-upload.php";
            break;
        
        case preg_match('/^onedb(:|\/)cache\/(get|flush|delete|set)(\/$|$)/', $uri, $matches):
            $_GET['action']             = $matches[2];
            require_once "output-handlers/site-cache.php";
            break;
        
        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)transcode(:|\/)([a-f0-9]+)\.([a-z0-9\.]+)$/', $uri, $matches):
            $_GET['fileID']             = $matches[4];
            $_GET['format']             = $matches[5];
            $_GET['ONEDB_AUTH_TOKEN']   = $matches[1];
            require_once "output-handlers/video-file.php";
            break;
        
        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)video(:|\/)([a-f0-9]+)\/([\d]+\/)?([a-z\d]+)\.rss$/', $uri, $matches ):
            $_GET['ONEDB_AUTH_TOKEN']   = $matches[1];
            $_GET['format']             = $matches[6];
            $_GET['token']              = $matches[5];
            $_GET['fileID']             = $matches[4];
            require_once "output-handlers/video-rss.php";
            break;
        
        case preg_match('/^onedb(\([a-f0-9]+\))?[\/:]forms\/([A-Za-z\-\.0-9_]+)\/(post|get)\/$/', $uri, $matches):
            $_GET['ONEDB_AUTH_TOKEN']   = $matches[1];
            $_GET['__FORM_NAME__']      = $matches[2];
            $_GET['__FORM_PROTOCOL__']  = $matches[3];
            require_once "input-handlers/form-handler.php";
            break;
        
        case preg_match('/^onedb(\:|\/)captcha\/([A-Za-z\-\.\d_]+)(.jpg|.png|.gif)$/', $uri, $matches ):
            $_GET['captcha_id']         = $matches[2];
            require_once "deps/captcha/index.php";
            break;
        
        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)picture(\(([a-z0-9=,\-_]+)?\))?(:|\/)([^*]+)$/', $uri, $matches ):
            $_GET['file']               = $matches[6];
            $_GET['settings']           = $matches[4];
            $_GET['ONEDB_AUTO_TOKEN']   = $matches[1];
            
            require_once "output-handlers/picture-file.php";
            break;
        
        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)video-resnapshot:([^*]+)$/', $uri, $matches):
            $_GET['file']               = $matches[3];
            $_GET['ONEDB_AUTH_TOKEN']   = $matches[1];
            require_once "output-handlers/video-resnapshot.php";
            break;
        
        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)meta(:|\/)([^*]+)$/', $uri, $matches):
            $_GET['ONEDB_AUTH_TOKEN']   = $matches[1];
            $_GET['file']               = $matches[4];
            require_once "output-handlers/meta-file.php";
            break;

        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)cache(:|\/)([\d]+)(:|\/)([^*]+)$/', $uri, $matches):
            $_GET['ONEDB_AUTH_TOKEN']   = $matches[1];
            $_GET['file']               = 
                preg_replace(
                    '/^(http|https)\:\/([^\/])/', 
                    '$1://$2', 
                    preg_replace(
                        '/^onedb(\([a-f0-9]+\))?(:|\/)cache(:|\/)/', '', $matches[6]
                    )
            );
            $_GET['ttl'] = $matches[4];
            require_once "output-handlers/cache.php";
            break;
        
        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)ratings(:|\/)([\da-f]+)$/', $uri, $matches):
            $_GET['ONEDB_AUTH_TOKEN'] = $matches[1];
            $_GET['_id'] = $matches[5];
            require_once "input-handlers/ratings.php";
            break;

        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)polls(:|\/)(vote|get)(:|\/)([\da-f]+)$/', $uri, $matches):
            $_GET['ONEDB_AUTH_TOKEN'] = $matches[1];
            $_GET['_id'] = $matches[6];
            $_GET['operation'] = $matches[4];
            require_once "input-handlers/polls.php";
            break;


        case preg_match('/^onedb\/frontend.js$/', $uri):
            require_once "output-handlers/frontend-javascript-outputter.php";
            break;
        
        case preg_match('/^onedb\/frontend-css\/([^*]+)?$/', $uri, $matches):
            $_GET['resource'] = $matches[1];
            require_once "output-handlers/frontend-css-outputter.php";
            break;
            
        case preg_match('/^onedb(\/|\:)time$/', $uri, $matches):
            require_once "output-handlers/time.php";
            break;
            break;

        case preg_match('/^onedb(\([a-f0-9]+\))?(:|\/)([^*]+)$/', $uri, $matches ):
            $_GET['file']               = '/' . $matches[3];
            $_GET['ONEDB_AUTH_TOKEN']   = $matches[1];
            require_once "output-handlers/raw-file.php";
            break;
        
        default:
            throw new Exception("Invalid OneDB URL: $uri");
            break;
    }

?>