<?php

    require __DIR__ . '/../bootstrap.php';
    
    // commands:
    
    // ### GROUPS MANAGEMENT
    // onedb sitename groupadd groupname
    // onedb sitename groupdel groupname
    // onedb sitename groupmod +|-user1 +|-user2 ... +|-usern

    // ### USERS MANAGEMENT
    // onedb sitename useradd username password group1[, group2, ... groupn]
    // onedb sitename passwd  username password
    // onedb sitename userdel username
    // onedb sitename usergrp username +|-group1[,+|-group2 ...]

    // ### SECURITY MANAGEMENT
    // onedb sitename umask username mask
    // onedb sitename chown [-R] [path|objectid] user:group
    // onedb sitename chmod [-R] [path|objectid] mask
    
    // ### FILESYSTEM MANAGEMENT
    // onedb sitename ls [path|objectid]
    // onedb sitename rm [-R] [path|objectid]
    
    // ### ENVIRONMENT MANAGEMENT
    // onedb sitename prepare cachedir path_to_local_directory
    // onedb sitename prepare db
    
    // ### DATABASE MANAGEMENT
    // onedb sitename db stats
    
    if ( php_sapi_name() != 'cli' ) {
        echo "ERROR: This program is intended to be used in console!\n";
        exit(1);
    }
    
    
    
    print_r( $argv );
?>