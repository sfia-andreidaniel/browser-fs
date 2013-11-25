<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    $term = Object( 'Utils.Terminal' );
        
    
    if ( term_get_env( 'site' ) == '' ) {
        echo $term->color( 'this command requires a site context', 'red' ), "\r\r";
        die(1);
    }
    
    if ( count( $argv ) < 2 || 
         count( $argv ) > 3 || 
         ( count( $argv ) == 3 && $argv[2] == 'database' && $argv[1] != '-force' ) ||
         ( count( $argv ) == 2 && !in_array( $argv[1], [ 'database', 'environment' ] ) )
    )
        term_manual('prepare');
    
    function onedbpass() {
        return @file_get_contents( __DIR__ . '/../../etc/onedb.shadow.gen' );
    }
    
    try {
        
        switch ( count( $argv ) == 3 ? $argv[2] : $argv[1] ) {
            
            case 'database':
                
                // BEGIN SCRIPT PREPARING DATABASE.
                $connection = Object( 'OneDB' )->connect( term_get_env( 'site' ), 'onedb', onedbpass() );
                
                $database = $connection->get_mongo_database();
                
                $dropfirst = [
                    'counters',
                    'objects',
                    'shadow'
                ];
                
                echo "* checking for existing collections...\r";
                
                foreach ( $dropfirst as $drop ) {

                    $col = $database->selectCollection( $drop );

                    if ( $col->findOne() != NULL ) {

                        if ( !isset( $argv[1] ) || $argv[1] != '-force' ) {
                            
                            die( $term->color( implode( "\r", [
                                "",
                                "  the database seems to be prepared ( found at least an object in collection $drop ).",
                                "  if you really know what you are doing, use the '-force' argument.",
                                "",
                                "  note that if you run a -force database prepair, ALL DATA WILL BE LOST FOREVER.",
                                "",
                                "  command refused due to safety considerations",
                                "",
                                ""
                            ] ), 'red' ) );
                            
                        }
                    }
                    
                    $col->drop();
                }
                
                echo "* creating database indexes...\r";
                
                // create indexes for collections...
                
                $counters = $database->selectCollection( 'counters' );

                $counters->ensureIndex(
                    [
                        'name' => 1
                    ],
                    [
                        'unique' => TRUE
                    ]
                );
                
                $shadow = $database->selectCollection( 'shadow' );
                
                $shadow->ensureIndex(
                    [
                        'type' => 1,
                        'name' => 1
                    ],
                    [
                        'unique' => TRUE
                    ]
                );
                
                $objects = $database->selectCollection( 'objects' );
                
                $objects->ensureIndex(
                    [
                        'url' => 1
                    ],
                    [
                        'unique' => TRUE
                    ]
                );
                
                $objects->ensureIndex(
                    [
                        'name' => 1,
                        '_parent' => 1
                    ],
                    [
                        'unique' => TRUE
                    ]
                );
                
                $objects->ensureIndex(
                    [
                        'ctime' => 1
                    ]
                );
                
                $objects->ensureIndex(
                    [
                        'online' => 1
                    ]
                );
                
                $objects->ensureIndex(
                    [
                        'keywords' => 1
                    ]
                );
                
                $objects->ensureIndex(
                    [
                        'tags' => 1
                    ]
                );
                
                echo "* creating database built-in users and groups...\r";
                
                Object( 'Utils.Class.Loader', 'Sys.Security.Management' );
                
                echo "  adding user root with password 'root' ...\r";
                Sys_Security_Management::useradd( term_get_env( 'site' ), 'root', 'toor' );
                echo "  adding user anonymous with password 'anonymous' ...\r";
                Sys_Security_Management::useradd( term_get_env( 'site' ), 'anonymous', 'anonymous' );
                
                echo "  adding group 'root' ...\r";
                Sys_Security_Management::groupadd( term_get_env( 'site' ), 'root' );
                echo "  adding group 'anonymous' ...\r";
                Sys_Security_Management::groupadd( term_get_env( 'site' ), 'anonymous' );
                
                echo "  making user root member of group root ...\r";
                Sys_Security_Management::usermod( term_get_env( 'site' ), 'root', [
                    
                    'groups' => [
                        ':root'
                    ]
                    
                ] );

                echo "  making user anonymous member of group anonymous ...\r";
                Sys_Security_Management::usermod( term_get_env( 'site' ), 'anonymous', [
                    
                    'groups' => [
                        ':anonymous'
                    ]
                    
                ] );
                
                echo "mongo database for website " . $term->color( term_get_env( 'site' ), 'yellow' ) . " has been prepared successfully\r\r";
            
                break;
            
            case 'environment':
                
                
                
                break;
            
        }
        
    } catch ( Exception $e ) {
        
        echo $term->color( Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 ), 'red' ), "\r\r";
    
    }
?>