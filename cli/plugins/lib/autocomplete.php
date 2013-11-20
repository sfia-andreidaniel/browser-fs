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
        if ( ( $website = term_get_env( 'website' ) ) == '' )
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
        if ( ( $website = term_get_env( 'website' ) ) == '' )
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
        
        // fs autocompletion not coded yet
        return [];
        
    }
    
?>