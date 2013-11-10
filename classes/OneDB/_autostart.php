<?php

if (!defined('ONEDB_AUTOSTART_EXECUTED')) {

    define('ONEDB_AUTOSTART_EXECUTED', 1);

    /* auto-loading support */
    spl_autoload_register(function( $className ) {
    
        if ( isset( $_SESSION ) && isset( $_SESSION['spl_autoload_bindings'] ) && isset( $_SESSION['spl_autoload_bindings'][ $className ] ) ) {
            require_once $_SESSION['spl_autoload_bindings'][ $className ];
            return;
        }
    
        $includePath = array_merge( explode( ':', ini_get( 'include_path' ) ), array(
            'classes/*',
            'inc/*',
            'lib/*',
            '3rd-party/*'
        ) );
        
        $textExtensions = array( ".class.php", ".inc.php", ".php" );
        
        $i = 0;
        
        $scan = function( $pathStart, $noAdd = TRUE ) use ( &$includePath, &$scan ) {
            if (!@realpath( $pathStart ))
                return;
            
            if (!$noAdd)
                $includePath[] = $pathStart;
            
            $files = @scandir( $pathStart );
            
            foreach ($files as $file) {
                if (!in_array( $file, array( '..', '.' ) ) ) {
                    if (is_dir( $nextPath = ( $pathStart . '/' . $file ) ) ) {
                        $scan( $nextPath, FALSE );
                    }
                }
            }
        };
        
        while ( $i < count( $includePath ) ) {
        
            $path = $includePath[ $i ];
            
            $fullPath = NULL;
            
            switch (TRUE) {
                case $path[0] == '/':
                    $fullPath = $path;
                    break;
                default:
                    $fullPath = ( isset( $_SERVER['DOCUMENT_ROOT'] ) && strlen( $_SERVER['DOCUMENT_ROOT'] ) ? $_SERVER['DOCUMENT_ROOT'] : dirname( __FILE__ ) ) . "/$path";
                    break;
            }
            
            if ( preg_match( '/([^*]+)([\/\\\\])\*$/', $fullPath, $matches ) ) {
                $scan( $matches[1], FALSE );
            }
            
            if ( $fullPath = @realpath( $fullPath ) ) {
            
                foreach ($textExtensions as $extension) {
                    if ( file_exists( $requireFile = $fullPath . DIRECTORY_SEPARATOR . $className . $extension ) ) {
                        if (isset( $_SESSION )) {
                            
                            $_SESSION['spl_autoload_bindings'] = ( isset( $_SESSION['spl_autoload_bindings'] ) && is_array( $_SESSION['spl_autoload_bindings'] ) ) ? $_SESSION['spl_autoload_bindings'] : array();
                            $_SESSION['spl_autoload_bindings'][ $className ] = $requireFile;
                            
                        }
                        
                        require_once $requireFile;
                        break;
                    }
                }
            }
            
            $i++;
        }
        
        if (!class_exists( $className ) && !defined( 'DISABLE_ONEDB_AUTOLOAD' ))
            throw new Exception("Class $className could not be auto-loaded!");
    }, TRUE, TRUE);
    /* End of auto-load support */
    
    if ( isset( $_SERVER['DOCUMENT_ROOT'] ) && strlen( $_SERVER['DOCUMENT_ROOT'] ) ) {

        /* Session support */
        // requires support for sessions. They can be either mysql, either memcache
        if (@file_exists( $_SERVER['DOCUMENT_ROOT'] . "/conf/session.memcache.opendb.php") ) {
            require_once $_SERVER['DOCUMENT_ROOT'] . "/conf/session.memcache.opendb.php";
            require_once dirname(__FILE__) . "/components/session/OneDB_Session_Memcache.class.php";
        } elseif (@file_exists( $_SERVER['DOCUMENT_ROOT'] . "/conf/session.mysql.opendb.php") ) {
            require_once $_SERVER['DOCUMENT_ROOT'] . "/conf/session.mysql.opendb.php";
            require_once dirname(__FILE__) . "/components/session/OneDB_Session_MySQL.class.php";
        }
        /* Session support */
    }
    
}

?>