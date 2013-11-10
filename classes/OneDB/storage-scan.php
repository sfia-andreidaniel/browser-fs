<?php

    function help() {
        global $argv;
        echo $argv[0], " <mongo_connection_uri_string> [-fix]\n";
    }

    if (!isset( $argv[1] ) )
        help();

    $info = parse_url( $argv[1] );
    
    if (!isset( $info['path'] ) || !strlen( $info['path'] ) )
        throw new Exception("Need the database to be specified in the path!");
    
    $info['path'] = trim($info['path'], ' /');
    
    
    print_r( $info );
    
    $class = 'MongoClient';
    if (!class_exists( $class ))
        $class = 'Mongo';
    
    $client = new $class( $argv[1] );
    
    if (!$client->connected)
        die("Could not connect to " . $argv[1] . "\n");

    $db = $client->selectDB( $info['path'] );

    $fs = $db->getGridFS();
    
    $cursor = $fs->find(array(
        
    ), array(
        "_id" => TRUE
    ));
    
    $good = 0;
    $bad  = 0;
    $total = 0;
    
    $files = array();
    
    while ($cursor->hasNext()) {
        $row = $cursor->getNext();
        $fileID = $row->file['_id'];
        $files[ "$fileID" ] = $fileID;
    }
    
    echo count( $files ), " files ...\n";
    
    $cursor = $db->articles->find(
        array(
            "type" => "File",
            "fileID" => array(
                '$in' => $files
            ),
            "storageType" => "database"
        ),
        array(
            'name' => TRUE,
            'fileID'  => TRUE
        )
    );
    
    while ($cursor->hasNext()) {
        $row = $cursor->getNext();
        $goodFileID = $row['fileID'];
        $files[ "$goodFileID" ] = FALSE;
        $good++;
    }
    
    echo "$good good files ...\n";
    
    $remove = array();
    
    $badList = array();
    
    $keys = array_keys( $files );
    for ($i=0, $len = count( $keys ); $i<$len; $i++) {
        if ($files[ $keys[$i] ] === FALSE)
            continue;
        $badList[] = $files[ $keys[$i] ];
        $bad++;
    }
    
    echo "$bad bad files ...\n";

    if ( isset( $argv[2] ) && $argv[2] == '-fix' ) {

            for( $i=0, $len = count( $badList); $i<$len; $i++) {
                echo $i, ":";
                $fs->remove(array( '_id' => $badList[$i] ) );
                echo 'removed, ', $len - $i, " remaining\n";
            }

    }
    
    echo "database fixed!\n";

    
?>