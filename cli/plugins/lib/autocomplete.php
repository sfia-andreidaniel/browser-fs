<?php
    
    function autocomplete_get_man( $written = '' ) {
        
        $files = scandir( __DIR__ . '/../man/' );
        
        $out = [];
        
        foreach ( $files as $file ) {
            
            if ( $file && $file[0] != '.' ) {
                
                if ( $written == '' || strpos( $file, $written ) === 0 )
                    $out[] = $file;
            }
            
        }
        
        return $out;
        
    }
    
    function autocomplete_get_users( $written = '' ) {
        
        $out = [];
        
        // cannot get users if not in a website context
        if ( ( $website = term_get_env( 'site' ) ) == '' )
            return [];
        
        try {
            
            $client = Object( 'OneDB' )->connect( $website, term_get_env( 'user' ), term_get_env( 'password' ) );
            
            $ulist = $client->sys->users;
            
            foreach ( $ulist as $user ) {
                
                if ( $written == '' || strpos( $user->name, $written ) === 0 )
                    $out[] = $user->name;
                
            }
            
            return $out;
            
        } catch ( Exception $e ) {
            
            // if an exception is made then we don't forward it, but instead
            // we return an  empty set of data
            return [];
        }

    }
    
    function autocomplete_get_groups( $written = '' ) {

        $out = [];
        
        // cannot get users if not in a website context
        if ( ( $website = term_get_env( 'site' ) ) == '' )
            return [];
        
        try {
            
            $client = Object( 'OneDB' )->connect( $website, term_get_env( 'user' ), term_get_env( 'password' ) );
            
            $glist = $client->sys->groups;
            
            foreach ( $glist as $group ) {
                
                if ( $written == '' || strpos( $group->name, $written ) === 0 )
                    $out[] = $group->name;
                
            }
            
            return $out;
            
        } catch ( Exception $e ) {
            // if an exception is made then we don't forward it, but instead
            // we return an  empty set of data
            return [];
        }
        
    }
    
    function autocomplete_get_websites( $written = '' ) {
        
        $out = [];
        
        try {
            
            $websites = Object( 'OneDB' )->websites;
            
            foreach ( $websites as $website ) {
                
                if ( $written == '' || strpos( $website, $written ) === 0 )
                    $out[] = $website;
                
            }
            
            return $out;
            
        } catch ( Exception $e ) {
            return [];
        }
        
    }
    
    function autocomplete_get_fs( $written = '' ) {
        $out = [];
        
        if ( ( $website = term_get_env( 'site' ) ) == '' )
            return $out; // needs a website context to do auto-completion
        
        try {
        
            // init site connection
            $client   = Object('OneDB')->connect( $website, term_get_env('user'), term_get_env( 'password' ) );
            
            // init path resolver
            $resolver = Object('Utils.Parsers.Path' );
            
            // current working dir
            $cwd = ( $cwd = term_get_env( 'path' ) )
                ? $cwd
                : '/';
            
            $absolute = FALSE;
            
            $checkIn = [];
            
            switch ( TRUE ) {
                
                case $written == '': // nothing written, we suggest from current working dir
                    $checkIn[] = ( $targetPath = $cwd );
                    break;
                
                case $resolver->isAbsolute( $written ):
                    
                    $targetPath = $written;
                    
                    $checkIn[] = $written;
                    $written2 = $written;
                    
                    $resolver->pop( $written2 );
                    $checkIn[] = $written2;
                    
                    $checkIn[] = preg_replace( '/\/([^\/]+)?$/', '', $written );
                    
                    break;
                
                default:
                    
                    $checkIn[] = $targetPath = $resolver->append( $cwd, $written );
                    
                    $checkIn[] = $resolver->substract( $targetPath, 1 );
                    
                    break;
                
            }
            
            $checkIn = array_filter( $checkIn, function( $s ) {
                return is_string( $s );
            } );
            
            for ( $i=0, $len = count( $checkIn ); $i<$len; $i++ ) {
                if ( substr($checkIn[$i], 0, 1 ) != '/' )
                    $checkIn[$i] = '/' . $checkIn[$i];
            }
            
            $checkIn = array_unique( $checkIn );
            
            foreach ( $checkIn as $check ) {
                
                //echo "find in: $check\n";
                
                $find = $client->getElementByPath( $check );
                
                if ( $find === NULL )
                    continue;
                
                $out[] = $find->url;
                
                $find->childNodes->each( function( $item ) use ( &$out, $resolver ) {
                    
                    //echo "found: $item->url\n";
                    
                    $out[] = $resolver->decode( $item->url );
                } );
                
            }
            
            $out = array_values( array_unique( $out ) );
            
            //echo "target path: $targetPath\n";
            
            $parent = ( preg_match( '/\/$/', $targetPath ) || $written == '' )
                ? $targetPath
                : $resolver->substract( $targetPath, 1 );
            
            //echo "parent target path: $parent\n";
            
            //print_r( $out );
            
            // decode all paths
            for ( $i=0, $len = count( $out ); $i<$len; $i++ )
                $out[$i] = $resolver->decode( $out[$i] );

            $out = array_values( array_filter( $out, function( $item ) use ( &$resolver, $parent ) {
                
                return ( $item === FALSE || $item === NULL || !$resolver->isParentOf( $parent, $item ) )
                    ? FALSE
                    : TRUE;
                
            } ) );
            
            // determine what to publish to autocompleter
            if ( preg_match( '/^((.*)\/)([^\/]+)?$/', $written, $matches ) ) {
                $keep  =  $matches[1];
                $addTo = isset( $matches[3] ) && strlen( $matches[3] ) ? $matches[3] : '';
            } else {
                $keep = '';
                $addTo = $written;
            }
            
            $realMatchStart = $addTo != ''
                ? ltrim( $resolver->decode( $addTo ), '/' )
                : '';
            
            $realMatchStartLength = strlen( $realMatchStart );
            
            $finalOut = [];
            
            $mode = $resolver->isAbsolute( $written ) ? 1 : 0;
            
            for( $i=0, $len = count( $out ); $i<$len; $i++ ) {
                
                $out[$i] = $resolver->basename( '/' . $out[$i] );
                
                if ( substr( $out[$i], 0, $realMatchStartLength ) == $realMatchStart ) {
                    
                    $item = $keep .  $addTo . ( $written == '..' ? '/' : '' ) .substr( $out[$i], $realMatchStartLength );
                    
                    switch ( $mode ) {
                        case 1:
                            // test if $item exists
                            if ( !( $obj = $client->getElementByPath( $item ) ) )
                                $item = NULL;
                            else {
                            
                                $item .= $obj->isContainer() ? '/' : '';
                                
                                if ( $written == '' && $cwd == '/' )
                                    $item = '/' . $item;
                            }
                            
                            break;
                        
                        case 0:
                            if ( !( $obj = $client->getElementByPath( $resolver->resolve( $cwd ) . $item ) ) )
                                $item = NULL;
                            else {
                            
                                $item .= $obj->isContainer() ? '/' : '';
                                
                                if ( $written == '' && $cwd == '/' )
                                    $item = '/' . $item;
                            }
                            
                            break;
                    }
                    
                    if ( $item !== NULL )
                        $finalOut[] = $item;
                }
                
            }
            
            return $finalOut;
        
        } catch ( Exception $e ) {
            
            echo "\r\r" . $e->getMessage() . "\r\r";
            
            return $out;
        }
        
    }
    
?>