<?php

    header("Content-Type: text/json");
    
    ini_set( 'display_errors', 'on');

    chdir( $_SERVER['DOCUMENT_ROOT'] );

    if (!file_exists( 'conf/onedb.cfg.php' ) )
        die("ERROR: This OneDB handler should be executed on production site!");

    $action = isset( $_GET['action'] ) ? $_GET['action'] : die("Which action?");
    
    require_once 'conf/onedb.cfg.php';
    require_once 'classes/OneDB/frontend/SiteFrontend_Memcache.class.php';

    $cache = new SiteFrontend_Memcache();
    
    switch ($action) {
        case 'get':
            $key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : die("Which key?");
            echo json_encode( $cache->cache_get( $key ) );
            break;
        case 'set':
            $key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : die("Which key?");
            $value = isset( $_REQUEST['value'] ) ? $_REQUEST['value'] : die("Which value?");
            $expires = isset( $_REQUEST['expires'] ) ? (int)$_REQUEST['expires'] : 600;
            echo json_encode( $cache->cache_set( $key, $value, $expires ) );
            break;
        case 'delete':
            $key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : die("Which key?");
            echo json_encode( $cache->cache_delete( $key ) );
            break;
        case 'flush':
            echo json_encode( $cache->cache_flush( ) );
            break;
        default:
            die("Unknown action: $action");
    }

?>