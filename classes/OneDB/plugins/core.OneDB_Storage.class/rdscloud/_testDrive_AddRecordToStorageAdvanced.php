<?php

// define('REPO_DEBUG', 1 );

// get the deps (class + function)

require_once( 'AddRecordToStorageAdvanced.php' );


// Test add record to storage

$sourceFile = array();
$sourceFile[] = '/tmp/video';

foreach ( $sourceFile as $file ) {
    $priority = 50;
    $versions = array(
        // '.240p.mp4',
        // '.360p.mp4',
        // '.480p.mp4',
        "-1"
    );
    $snapshots = array(
        // '.snapshot.%d.jpg',
        "-1"
    );
    $response = bindAddRecordToStorageAdvanced( 'digisport', 'ds01uSr01', $file );
    echo $response . "\n";
}






