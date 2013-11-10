<?php

    if (!isset($_SESSION))
        session_start();
    
    function error($str, $code = 100) {
        header("Content-Type: text/plain");
        header("HTTP/1.1 500 Server error");
        echo $str;
        exit( $code );
    }

    require_once dirname( __FILE__ ) . "/../OneDB.class.php";
    
    new OneDB();
    
    $fileID = isset( $_GET['fileID'] ) ? $_GET['fileID'] : '';
    $format = isset( $_GET['format'] ) ? $_GET['format'] : error( "Feed output format not specified!" );
    
    $token  = isset( $_GET['token'] ) && strlen( $_GET['token'] ) ? trim( $_GET['token'], '/' ) : '';
    
    if ( !preg_match( '/^[a-f\d]{24}$/', $fileID ) )
        error( "Bad OneDB file ID format", 500);
    
    $edgeServersList = OneDB()->registry()->{"OneDB.Video.EdgeServersList"};
    $platformsList   = OneDB()->registry()->{"OneDB.Video.Platforms"};
    
    if ( empty( $edgeServersList ) )
        error( "Registry setting OneDB.Video.EdgeServersList is not defined. Please read OneDB manual and correct this setting" );
    
    if ( empty( $platformsList ) )
        error( "Registry setting OneDB.Video.Platforms is not defined. Please read OneDB manual and correct this setting" );
    
    $edgeServersList = @json_decode( $edgeServersList, TRUE );
    $platformsList   = @json_decode( $platformsList, TRUE );
    
    if ( !is_array( $edgeServersList ) )
        error( "Registry setting OneDB.Video.EdgeServersList could not be properly decoded as JSON!" );
    
    if ( !is_array( $platformsList ) )
        error( "Registry setting OneDB.Video.Platforms could not be properly decoded as JSON!" );
    
    if ( !isset( $platformsList[ $format ] ) )
        error( "Platform '$format' is not configured in registry setting OneDB.Video.Platforms!" );
    
    if ( !count( $edgeServersList ) ) {
        $edgeServersList = array(
            $_SERVER['SERVER_NAME']
        );
        header("X-OneDB-Warning: No edge servers were setup via OneDB.Video.EdgeServersList" );
    }
    
    if ( !is_array( $platformsList[ $format ] ) )
        error( "Expected array in OneDB.Video.Platforms[ $format ] registry setting!" );
    
    if ( !count( $platformsList[ $format ] ) )
        error("Platform '$format' is defined in OneDB.Video.Platforms but has no formats!" );
    
    $format = $platformsList[ $format ];
    
    while ( count( $edgeServersList ) < ( count( $format ) + 1 ) )
        $edgeServersList = array_merge( $edgeServersList, $edgeServersList );
    
    
    shuffle( $edgeServersList );
    
    header("Content-Type: text/plain" );
    
    echo '<rss version="2.0" xmlns:jwplayer="http://rss.jwpcdn.com/"><channel><item><title>Play Video</title>', "\n";
    
    for ( $i=0, $len = count( $format ); $i<$len; $i++ )
        echo '<jwplayer:source file="http://', $edgeServersList[$i], '/onedb/transcode/', $fileID, '.', $format[$i], '?token=', $token, '" label="', reset( $tmp = explode('.', $format[$i] ) ), '" />', "\n";
    
    echo '<jwplayer:image>http://', $edgeServersList[ count($format) - 1 ], '/onedb/picture/', $fileID, '</jwplayer:image>', "\n", '</item></channel></rss>';
    
    /*
    print_r( array(
        'edgeservers' => $edgeServersList,
        'formats'     => $format
    ) );
    */
    
    
?>