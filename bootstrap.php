<?php

    ini_set( 'display_errors', 'on' );
    
    error_reporting( E_ALL );

    // Require core class
    require_once __DIR__ . '/classes/onedb/Object.class.php';
    require_once __DIR__ . '/classes/onedb/OneDB.class.php';

    // Instantiate the ini parser
    Object( 'Utils.Parsers.OneDBCfg', __DIR__ . '/etc/onedb/onedb.ini' );

?>