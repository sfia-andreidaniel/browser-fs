<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    //print_r( $argv );
    
    $arglen = count( $argv );
    
    $cmd = '';

    $term = Object( 'Utils.Terminal' );

    switch ( TRUE ) {
        
        case $arglen == 2 && $argv[1] == 'users':

            if ( ( $websitename = term_get_env( 'site' ) ) == '' ) {
                echo $term->color( 'this command needs to be executed in a "site" context', 'red' ), "\r";
                echo $term->color( 'please type "use <sitename>" command to change the site context', 'red' ), "\r\r";
                die(1);
            }

            $cmd = 'users';
            
            break;

        case $arglen == 2 && $argv[1] == 'groups':

            if ( ( $websitename = term_get_env( 'site' ) ) == '' ) {
                echo $term->color( 'this command needs to be executed in a "site" context', 'red' ), "\r";
                echo $term->color( 'please type "use <sitename>" command to change the site context', 'red' ), "\r\r";
                die(1);
            }
            
            $cmd = 'groups';
            
            break;
        
        case $arglen == 2 && $argv[ 1 ] == 'websites':
            
            $cmd = 'websites';
            
            break;
        
        default:
            
            term_manual('show');
            break;
    }
    
    function onedbpass() {
        return @file_get_contents( __DIR__ . '/../../etc/onedb.shadow.gen' );
    }
    
    try {
    
        $term = Object( 'Utils.Terminal' );
        
        Object( 'Sys.Umask' );
    
        switch ( $cmd ) {
            
            case 'users':
            
                $client = Object( 'OneDB' )->connect( $websitename, 'onedb', onedbpass() );
                
                $users = $client->sys->users;
                
                $longestName = 0;
                $longestUid  = 0;
                
                foreach ( $users as $user ) {
                    if ( ( $len = strlen( $user->name ) ) > $longestName )
                        $longestName = $len;
                    if ( ( $len = strlen( $user->uid . '' ) ) > $longestUid )
                        $longestUid = $len;
                }
                
                echo ( $len = count( $users ) ), " user" . ( $len != 1 ? 's' : '' ), ". showing: uid, umask, flags, name, groups\r";
                
                foreach ( $users as $user ) {
                    
                    echo str_pad( $user->uid, $longestUid ),
                         '  ',
                         Umask::mode_to_str( $user->umask ),
                         '  ',
                         (
                            implode('', [
                                $user->flags & Umask::AC_NOBODY ? 'n' : '-',
                                $user->flags & Umask::AC_SUPERUSER ? 's' : '-',
                                $user->flags & Umask::AC_REGULAR ? 'r' : '-'
                            ] )
                            
                        ), '  ', $term->color( str_pad( $user->name, $longestName ), 'cyan' ), " => ";
                    
                    $groups = $user->groups;
                    
                    if ( count( $groups ) ) {
                        
                        $groupnames = [];
                        
                        foreach ( $groups as $group ) {
                            $groupnames[] = $term->color( $group->name, 'light_blue' );
                        }
                        
                        echo implode( ', ', $groupnames );
                        
                    } else {
                        echo $term->color( "no groups", 'light_gray' );
                    }
                    
                    echo "\r";
                    
                }
            
                break;
            
            case 'groups':
                
                $client = Object( 'OneDB' )->connect( $websitename, 'onedb', onedbpass() );
                
                $groups = $client->sys->groups;
                
                echo ( $len = count( $groups ) ), " group", ( $len != 1 ? 's' : '' ), ". showing: gid, flags, name, users\r";
                
                $longestGroupName = 0;
                $longestGid       = 0;
                
                foreach ( $groups as $group ) {
                    if ( ( $len = strlen( $group->name ) ) > $longestGroupName )
                        $longestGroupName = $len;
                    
                    if ( ( $len = strlen( $group->gid . '' ) ) > $longestGid )
                        $longestGid = $len;
                }
                
                foreach ( $groups as $group ) {
                    
                    echo str_pad( $group->gid, $longestGid ),
                         '  ',
                         implode('', [
                            $group->flags & Umask::AC_NOBODY ? 'n' : '-',
                            $group->flags & Umask::AC_SUPERUSER ? 's' : '-',
                            $group->flags & Umask::AC_REGULAR ? 'r' : '-'
                         ]),
                         '  ',
                         $term->color( str_pad( $group->name, $longestGroupName ), 'cyan' ),
                         ' => ';
                    
                    $users = $group->users;
                
                    if ( count( $users ) ) {
                        
                        $usernames = [];
                        
                        foreach ( $users as $user ) {
                            $usernames[] = $term->color( $user->name, 'light_blue' );
                        }
                        
                        echo implode( ', ', $usernames );
                            
                    } else {
                        echo $term->color( "no users", 'light_gray' );
                    }
                    
                    echo "\r";
                }
                
                echo "\r";
                    
                break;
            
            case 'websites':
                
                $client = Object( 'OneDB' );
                
                $websites = $client->websites;
                
                echo count( $websites ), " website" . ( count( $websites ) == 1 ? '' : 's' ) . "\r";
                
                foreach ( $websites as $website ) {
                    
                    echo $term->color( $website, 'cyan' ), "\r";
                    
                }
                
                break;
            
        }
        
        echo "\r";
    
    } catch ( Exception $e ) {
        
        echo $term->color( $e->getMessage(), 'red' );
        
    }
    
?>