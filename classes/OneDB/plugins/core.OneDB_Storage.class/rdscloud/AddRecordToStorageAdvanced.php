<?php

require_once( dirname(__FILE__) . '/AddRecordToStorage.php' );
require_once( dirname(__FILE__) . '/MakeVTIJobsByUrl.php' );
require_once( dirname(__FILE__) . '/MakeVTVJobsByUrl.php' );

function bindAlertErr( $msg ) {
    throw new Exception(
        $msg
    );

}

function bindAddRecordToStorageAdvanced( $user, $pass, $sourceFile, $priority = 50,
    $versions = array( '-1' ), $snapshots = array( '-1' )
) {

    //
    // 1. get the repo storage response
    //

    $res1 = bindAddRecordToStorage( $user, $pass, $sourceFile, $priority );

    // test JSON response
    $res1Decoded = @json_decode( $res1, true );
    if ( $res1Decoded == false ) {
        bindAlertErr( $res1 );
        return false;
    }
    
    // test ok
    if ( ( ! isset( $res1Decoded[ 'ok' ] ) )
        || ( ( isset( $res1Decoded[ 'ok' ] ) )
            && ( $res1Decoded[ 'ok' ] == false )
        )
    ) {
        bindAlertErr( $res1 );
        return false;
    }

    $path = $res1Decoded[ 'path' ];

    //
    // 2. get the repo make vtv response
    //

    $res2 = bindMakeVTVJobsByUrl( $user, $pass, $path, $priority, $versions );
    $res2Decoded = json_decode( $res2, true );
    if ( $res2Decoded == false ) {
        bindAlertErr( $res2 );
        return false;
    }

    $noVersions = FALSE;

    // test ok
    if ( ( ! isset( $res2Decoded[ 'ok' ] ) )
        || ( ( isset( $res2Decoded[ 'ok' ] ) )
            && ( $res2Decoded[ 'ok' ] == false )
        )
    ) {
        if (!preg_match('/^ERR_RECORD_NOT_TRANSCODERABLE /', $res2Decoded['error'] )) {
            bindAlertErr( $res2 );
            return false;
        } else {
            $noVersions = TRUE;
        }
    }

    $versions = $noVersions ? NULL : $res2Decoded[ 'versions' ];


    //
    // 3. get the repo make vti response
    //

    $res3 = bindMakeVTIJobsByUrl( $user, $pass, $path, $priority, $snapshots );
    $res3Decoded = json_decode( $res3, true );
    if ( $res3Decoded == false ) {
        bindAlertErr( $res3 );
        return false;
    }

    $noSnapshots = FALSE;

    // test ok
    if ( ( ! isset( $res3Decoded[ 'ok' ] ) )
        || ( ( isset( $res3Decoded[ 'ok' ] ) )
            && ( $res3Decoded[ 'ok' ] == false )
        )
    ) {
        
        if (!preg_match('/^ERR_RECORD_NOT_SNAPSHOTABLE /', $res3Decoded['error'] )) {
            bindAlertErr( $res3 );
            return false;
        } else {
            $noSnapshots = TRUE;
        }
    }

    $snapshots = $noSnapshots ? NULL : $res3Decoded[ 'snapshots' ];


    //
    // 4. return response
    //

    $a = array();
    
    if ($versions !== NULL) {
        $a['versions'] = array();
        
        foreach ($versions as $version) {
            foreach (array_keys( $version ) as $versionName ) {
                $a['versions'][$versionName] = $version[ $versionName ];
            }
        }
        
    }
        
    if ($snapshots !== NULL) {
        $a['snapshots'] = array();
        foreach ($snapshots as $snapshot) {
        
            $a['snapshots'][] = reset( $snapshot );
        }
    }

    $out = array_merge(
        $res1Decoded,
        $a
    );

    return json_encode( $out );
}


