<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    //print_r( $argv );
    $term = Object( 'Utils.Terminal' );

    
    if ( count( $argv ) < 2 )
        term_manual( 'usermod');

    if ( term_get_env( 'site' ) == '' )
        die( $term->color( 'this command requires a site context', 'red' ) . "\r\r" );

    function onedbpass() {
        return @file_get_contents( __DIR__ . '/../../etc/onedb.shadow.gen' );
    }

    try {
        
        // connect to website
        $client = Object( 'OneDB' )->connect( term_get_env( 'site' ), 'onedb', onedbpass() );
        
        $user = $client->sys->user( $argv[1] );
        
        if ( $user === NULL )
            throw Object( 'Exception.Security', 'user "' . $argv[1] . '" was not found' );
        
        $mod = [];
        
        for ( $i = 2, $len = count( $argv ); $i < $len; $i++ ) {
            
            switch ( $argv[$i] ) {
                
                case '-p':
                    if ( isset( $argv[ $i + 1 ] ) ) {
                        
                        $mod[ 'password' ] = $argv[ $i + 1 ];
                        $i++;
                        
                    } else throw Object( 'Exception.Runtime', 'unexpected end of args ( -p )' );
                    break;
                
                case '-g':
                case '+g':
                case ':g':
                    
                    $op = substr( $argv[$i], 0, 1 );
                    
                    if ( !isset( $mod[ 'groups' ] ) )
                        $mod[ 'groups' ] = [];
                    
                    if ( !isset( $argv[ $i + 1 ] ) )
                        throw Object( 'Exception.Runtime', 'unexpected end of args ( ' . $argv[$i] . ')' );
                    
                    $mod[ 'groups' ][] = $op . $argv[ $i + 1 ];
                    
                    $i++;
                    
                    break;
                
                case '-umask':
                    
                    if ( !isset( $argv[ $i + 1 ] ) )
                        throw Object( 'Exception.Runtime', 'unexpected end of args ( -umask )' );
                    
                    $mask = $argv[ $i + 1 ];
                    
                    $i++;
                    
                    Object( 'Utils.Class.Loader', 'Sys.Umask' );
                    
                    $mask = Umask::str_to_mode( $mask );
                    
                    $mod[ 'umask' ] = $mask;
                    
                    break;
                
                case '-f':
                case '+f':
                    
                    $op = substr( $argv[ $i ], 0, 1 ) == '+' ? 'add' : 'del';
                    
                    if ( !isset( $mod[ 'flags' ] ) )
                        $mod[ 'flags' ] = $user->flags;
                    
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
                    term_manual( 'usermod');
                    break;
                
            }
            
        }
        
        if ( !count( $mod ) )
            die("no modifications were made to user '" . $argv[1] . "'.\rcommand usermod requires more arguments.\r\r");
        
        Object( 'Utils.Class.Loader', 'Sys.Security.Management' );
        
        Sys_Security_Management::usermod( term_get_env( 'site' ), $argv[1], $mod );
        
        //print_r( $mod );
        
        //echo "ok\r\r";
        
        echo "user '" . $term->color( $argv[1], 'yellow' ) . "' has been modified successfully\r";
        
        echo "\r";
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>