<?php

    define( 'ONEDB_OBJECT_READONLY'  , 2 );
    define( 'ONEDB_OBJECT_CONTAINER' , 4 );
    define( 'ONEDB_OBJECT_UNLINKED',   8 );
    define( 'ONEDB_OBJECT_ROOT',      16 );

    $flags = [ ONEDB_OBJECT_READONLY, ONEDB_OBJECT_CONTAINER, ONEDB_OBJECT_UNLINKED, ONEDB_OBJECT_ROOT ];

    $value = ONEDB_OBJECT_READONLY ^ ONEDB_OBJECT_ROOT;
    
    for ( $i = 0, $len = count( $flags ); $i<$len; $i++ ) {
        if ( $value & $flags[$i] )
            echo $i, " => yes\n";
        else
            echo $i, " => no\n";
    }
    
    echo "value: $value\n";

?>