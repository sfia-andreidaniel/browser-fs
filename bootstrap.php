<?php

    // Require core class
    require_once __DIR__ . '/classes/onedb/Object.class.php';

    // Instantiate the ini parser
    Object( 'Utils.Parsers.OneDBCfg', __DIR__ . '/conf/onedb.ini' );

?>