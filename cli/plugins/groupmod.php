<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    function help() {
        
        $term = Object( 'Utils.Terminal' );
        
        echo implode( "\r", [
            "groupmod syntax:",
            '    ' . $term->color( 'groupmod', 'green' ) . ' ' . $term->color( '<groupname>', 'yellow' ),
            '       ' . $term->color( ' [ [ -u <username> | +u <username> | :u <username> ] ... [ -u <username> | +u <username> | :u <username> ]  ] ', 'brown' ),
            '       ' . $term->color( ' [ [ +f <flag> | -f <flag> ] ... [ +f <flag> | -f <flag> ] ]', 'light_cyan' ),
            
            '',
            'notes:',
            '    note that the command works in a website context only (use <website> first).',
            '',
            'arguments explanation:',
            '    ' . $term->color( '<groupname>', 'yellow' )     . '       - the group name to be modified',
            '    ' . $term->color( '-u <username>', 'brown' ) . '   - remove user <username> from this group',
            '    ' . $term->color( '+u <username>', 'brown' ) . '   - add user <username> to this group',
            '    ' . $term->color( ':u <username>', 'brown' ) . '   - add user <username> to this group and make this group the default group of the user',
            '    ' . $term->color( '+f <flag>', 'light_cyan' ) . '        - add group flag <flag> (n,s,r)',
            '    ' . $term->color( '-f <flag>', 'light_cyan' ) . '        - remove group flag <flag> (n,s,r)',
            '',
            'flags explanation:',
            '    n flag: group is a "nobody" type group',
            '    s flag: group is a "superuser" type group',
            '    r flag: group is a "regular" type group',
            '',
            'see also:',
            '    ' . $term->color( 'groupadd', 'green' ),
            '    ' . $term->color( 'groupdel', 'green' ),
            '    ' . $term->color( 'show groups', 'green' ),
            '',
            ''
        ] );
        
        die(1);
    }
    
    //print_r( $argv );
    $term = Object( 'Utils.Terminal' );

    
    if ( count( $argv ) < 2 )
        help();

    if ( term_get_env( 'site' ) == '' )
        die( $term->color( 'this command requires a site context', 'red' ) . "\r\r" );

    function onedbpass() {
        return @file_get_contents( __DIR__ . '/../../etc/onedb.shadow.gen' );
    }

    try {
        
        // connect to website
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), 'onedb', onedbpass() );
        
        $group = $client->sys->group( $argv[1] );
        
        if ( $group === NULL )
            throw Object( 'Exception.Security', 'group "' . $argv[1] . '" was not found' );
        
        $mod = [];
        
        for ( $i = 2, $len = count( $argv ); $i < $len; $i++ ) {
            
            switch ( $argv[$i] ) {
                
                case '-u':
                case '+u':
                case ':u':
                    
                    $op = substr( $argv[$i], 0, 1 );
                    
                    if ( !isset( $mod[ 'users' ] ) )
                        $mod[ 'users' ] = [];
                    
                    if ( !isset( $argv[ $i + 1 ] ) )
                        throw Object( 'Exception.Runtime', 'unexpected end of args ( ' . $argv[$i] . ')' );
                    
                    $mod[ 'users' ][] = $op . $argv[ $i + 1 ];
                    
                    $i++;
                    
                    break;
                
                case '-f':
                case '+f':
                    
                    $op = substr( $argv[ $i ], 0, 1 ) == '+' ? 'add' : 'del';
                    
                    if ( !isset( $mod[ 'flags' ] ) )
                        $mod[ 'flags' ] = $group->flags;
                    
                    if ( !isset( $argv[ $i + 1 ] ) )
                        throw Object( 'Exception.Runtime', 'unexpected end of args ( ' . $argv[ $i ] . ' )' );
                    
                    $flag = $argv[ $i + 1 ];
                    $i++;
                    
                    if ( !in_array( $flag, [ 'n', 's', 'r' ] ) )
                        throw Object( 'Exception.Runtime', 'bad flag value "' . $flag . '", expected: "n" or "s" or "r"' );
                    
                    Object( 'Utils.Class.Loader', 'Sys.Umask' );
                    
                    switch ( $op ) {
                        
                        case 'add':
                            
                            switch ( $flag ) {
                                
                                case 'n':
                                    $mod[ 'flags' ] |= Umask::AC_NOBODY;
                                    break;
                                
                                case 's':
                                    $mod[ 'flags' ] |= Umask::AC_SUPERUSER;
                                    break;
                                
                                case 'r':
                                    $mod[ 'flags' ] |= Umask::AC_REGULAR;
                                    break;
                                
                            }
                            
                            break;
                        
                        case 'del':
                                
                                switch ( $flag ) {
                                    
                                    case 'n':
                                        
                                        if ( $mod[ 'flags' ] & Umask::AC_NOBODY )
                                            $mod[ 'flags' ] ^= Umask::AC_NOBODY;
                                        
                                        break;
                                    
                                    case 's':
                                        
                                        if ( $mod[ 'flags' ] & Umask::AC_SUPERUSER )
                                            $mod[ 'flags' ] ^= Umask::AC_SUPERUSER;
                                        
                                        break;
                                    
                                    case 'r':
                                        
                                        if ( $mod[ 'flags' ] & Umask::AC_REGULAR )
                                            $mod[ 'flags' ] ^= Umask::AC_REGULAR;
                                        
                                        break;
                                    
                                }
                                
                            break;
                        
                    }
                    
                    break;
                
                default:
                    echo $term->color( 'unknown argument: ' . $argv[$i], 'red' ) . "\r\r";
                    help();
                    break;
                
            }
            
        }
        
        if ( !count( $mod ) )
            die("no modifications were made to group '" . $argv[1] . "'.\rcommand groupmod requires more arguments.\r\r");
        
        Object( 'Utils.Class.Loader', 'Sys.Security.Management' );
        
        Sys_Security_Management::groupmod( term_get_env( 'site' ), $argv[1], $mod );
        
        //print_r( $mod );
        
        //echo "ok\r\r";
        
        echo "group '" . $term->color( $argv[1], 'yellow' ) . "' has been modified successfully\r";
        
        echo "\r";
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>