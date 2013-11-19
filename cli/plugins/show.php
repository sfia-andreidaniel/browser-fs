<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    //print_r( $argv );
    
    $arglen = count( $argv );
    
    $cmd = '';

    $term = Object( 'Utils.Terminal' );

    function help() {
        
        $term = Object( 'Utils.Terminal' );
        
        echo implode( "\r", [
            "show syntax:",
            "     " . $term->color( 'show ', 'green' ) . $term->color( 'users   ', 'yellow' ) . " - displays info about users in the system",
            "     " . $term->color( 'show ', 'green' ) . $term->color( 'groups  ', 'yellow' ) . " - displays info about groups in the system",
            "     " . $term->color( 'show ', 'green' ) . $term->color( 'websites', 'yellow' ) . " - displays the list of available websites",
            "",
            ""
        ] );
        
        die(1);
    }

    switch ( TRUE ) {
        
        case $arglen == 2 && $argv[1] == 'users':

            if ( ( $websitename = term_get_env( 'site' ) ) == '' ) {
                echo $term->color( 'this command needs to be executed in a "site" context', 'red' ), "\r";
                echo $term->color( 'please type "use <sitename>" command to change the site context', 'red' ), "\r\r";
                die(1);
            }

            $cmd         = 'users';
            
            break;

        case $arglen == 2 && $argv[1] == 'groups':

            if ( ( $websitename = term_get_env( 'site' ) ) == '' ) {
                echo $term->color( 'this command needs to be executed in a "site" context', 'red' ), "\r";
                echo $term->color( 'please type "use <sitename>" command to change the site context', 'red' ), "\r\r";
                die(1);
            }
            
            $cmd         = 'groups';
            
            break;
        
        case $arglen == 2 && $argv[ 1 ] == 'websites':
            
            $cmd         = 'websites';
            
            break;
        
        default:
            
            help();
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
                
                echo "\rusername         uid   umask          flags\r";
                echo "-------------------------------------------\r";
                
                foreach ( $users as $user ) {
                    
                    echo $term->color( str_pad( $user->name, 16 ), 'cyan' ),
                         ' ',
                         str_pad( $user->uid, 5 ),
                         ' ',
                         Umask::mode_to_str( $user->umask ),
                         '     ',
                         (
                            implode('', [
                                $user->flags & Umask::AC_NOBODY ? 'n' : '-',
                                $user->flags & Umask::AC_SUPERUSER ? 's' : '-',
                                $user->flags & Umask::AC_REGULAR ? 'r' : '-'
                            ] )
                            
                        ), "\r";
                    
                    $groups = $user->groups;
                    
                    if ( count( $groups ) ) {
                        
                        $groupnames = [];
                        
                        foreach ( $groups as $group ) {
                            $groupnames[] = $term->color( $group->name, 'light_blue' );
                        }
                        
                        echo $term->color( "member of: ", 'light_gray' ),
                             implode( ', ', $groupnames ), "\r";
                        
                    } else {
                        echo $term->color( "member of no groups", 'light_gray' ), "\r";
                    }
                    
                    echo "\r";
                    
                }
            
                break;
            
            case 'groups':
                
                $client = Object( 'OneDB' )->connect( $websitename, 'onedb', onedbpass() );
                
                $groups = $client->sys->groups;
                
                echo "\rgroup            gid   flags\r";
                echo "----------------------------\r";
                
                foreach ( $groups as $group ) {
                    
                    echo $term->color( str_pad( $group->name, 16 ), 'cyan' ),
                         ' ',
                         str_pad( $group->gid, 5 ),
                         ' ',
                         implode('', [
                            $group->flags & Umask::AC_NOBODY ? 'n' : '-',
                            $group->flags & Umask::AC_SUPERUSER ? 's' : '-',
                            $group->flags & Umask::AC_REGULAR ? 'r' : '-'
                         ]), "\r";
                    
                    $users = $group->users;
                
                    if ( count( $users ) ) {
                        
                        $usernames = [];
                        
                        foreach ( $users as $user ) {
                            $usernames[] = $term->color( $user->name, 'light_blue' );
                        }
                        
                        echo $term->color( "has users: ", 'light_gray' ),
                             implode( ', ', $usernames ), "\r";
                            
                    } else {
                        echo $term->color( "has no users", 'light_gray' ), "\r";
                    }
                    
                    echo "\r";
                }
                
                echo "\r";
                    
                break;
            
            case 'websites':
                
                $client = Object( 'OneDB' );
                
                $websites = $client->websites;
                
                echo "\rwebsite name\r";
                echo "------------\r";
                
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