<?php

// get the deps (class + function)

require_once( 'AddRecordToStorage.php' );


// Test add record to storage

$sourceFile = array();
$sourceFile[] = '/tmp/video.mp4';
foreach ( $sourceFile as $file ) {
    $priority = 50;
    $newUrl = bindAddRecordToStorage( 'username', 'password', $file, $priority );
    var_export( $newUrl );
}






