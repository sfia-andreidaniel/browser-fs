<?php

    chdir (dirname( __FILE__ ));

    if (!isset( $argv[1] ))
        die('10');
    
    if (!isset($argv[2] ))
        $snaps = 3;
    else
        $snaps = (int)$argv[2];
    
    require_once "VideoParser.class.php";
    
    $props = parse_video_file( $argv[1] );
    
    // print_r ($props);
    
    if ( !empty( $props ) && isset($props['duration']) && isset( $props['video'] ) ) {
        
        //print_r( $snaps );
        
        echo $snaps  .  '/' . round( $props['duration'] );
        
    } else echo '0';

?>