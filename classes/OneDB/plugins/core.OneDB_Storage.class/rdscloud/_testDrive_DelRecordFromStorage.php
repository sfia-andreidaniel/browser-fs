<?php

require_once( 'functions.php' );


// Test add record to storage

$sourceFile = array();
$sourceFile[] = 'http://storage01transcoder.rcs-rds.ro/storage/2012/10/25/107_105__85_83__sample_mpeg4.mp4';

foreach ( $sourceFile as $file ) {
    $newUrl = bindDelRecordFromStorage( 'digisport', 'ds01uSr01', $file );
    var_export( $newUrl );
}






