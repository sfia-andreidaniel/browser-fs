<?php
    
    /* Terminal library handy for writing plugins */
    
    $_ENV_ = [];
    
    function term_init( &$args ) {
    
        global $_ENV_;
    
        for ( $i = count($args) - 1; $i>0; $i-- ){
        
            if ( $args[$i] == '---empty---argument---fpc---tprocess---bug---' )
                $args[$i] = '';
        
            //echo $args[$i], "\n";
        
            if ( substr( $args[$i], 0, 5 ) == '-ENV=' ) {
                
                $pair = substr( $args[$i], 5 );
                
                if ( ( $index = strpos( $pair, ':' ) ) !== FALSE ) {
                    $var = substr( $pair, 0, $index );
                    $val = substr( $pair, $index + 1 );
                } else {
                    $var = $pair;
                    $val = '';
                }
                
                //echo "var=$var, val=$val\n";
                
                $_ENV_[ $var ] = $val;

                array_splice( $args, $i, 1 );
            }
        }
        
        $args = array_values( $args );

        if ( isset( $_ENV_['site'] ) ) {
        
            if ( isset( $_ENV_['user'] ) && ( $_ENV_['user'] == 'onedb' || $_ENV_['user'] == '' ) ) {
                
                $_ENV_['user'] = 'onedb';
                $_ENV_['password'] = file_get_contents( __DIR__ . '/../../../etc/onedb.shadow.gen' );
                
            }
            
        }

    }
    
    
    function term_get_env( $varname ) {
        
        global $_ENV_;
        
        return isset( $_ENV_[ $varname ] )
            ? $_ENV_[ $varname ]
            : '';
    }
    
    function term_set_env( $varname, $value ) {
        
        global $_ENV_;
        
        $_ENV_[ $varname ] = $value;
        
    }
    
    function term_abort_env() {
        if ( !defined( 'ENV_ABORTED' ) )
            define( 'ENV_ABORTED', 1 );
    }
    
    register_shutdown_function( function(){
        
        // dump environment ...
        if ( defined( 'ENV_ABORTED' ) )
            return ;
        
        $out = [];
        
        global $_ENV_;
        
        foreach ( array_keys( $_ENV_ ) as $varname ) {
            $out[] = '$SETENV: ' . $varname . ' ' . $_ENV_[ $varname ];
        }
        
        echo "\n\n";
        echo implode( "\n", $out );
        echo "\n\n";
        
    } );
    
    function term_manual( $command, $abort = TRUE ) {
        
        if ( file_exists( __DIR__ . '/../man/' . $command ) ) {
            $buffer = @file_get_contents( __DIR__ . '/../man/' . $command );
        }
        
        if ( empty( $buffer ) )
            $buffer = '<red>MAN:</> manual page for command <green>' . $command . '</> was not found';
        
        $color   = [];
        $colst   = 0;
        $buffers = [];
        
        $term = Object( 'Utils.Terminal' );
        
        for ( $i=0, $len = strlen( $buffer ); $i<$len; $i++ ) {
            
            switch ( TRUE ) {
                
                case ( $buffer[$i] != '<' ):
                    if ( $colst == 0 ) {
                        echo $buffer[$i];
                    } else {
                        $buffers[ $colst - 1 ] .= $buffer[$i];
                    }
                    break;
                
                case $buffer[$i] == '<':
                
                    switch ( TRUE ) {
                
                        case preg_match( '/^\<([a-z_]+?)\>/', substr( $buffer, $i, 30 ), $matches ) ? TRUE : FALSE:
                            $color[ $colst ] = $matches[1];
                            $buffers[ $colst ] = '';
                            $colst++;
                            $i += ( strlen( $matches[0] ) - 1 );
                            break;
                        case ( $colst > 0 && preg_match( '/^\<\/\>/', substr( $buffer, $i, 10 ), $matches ) ) ? TRUE : FALSE:
                            echo $term->color( $buffers[ $colst - 1 ], $color[ $colst - 1 ] );
                            $colst--;
                            $i += ( strlen( $matches[0] ) - 1 );
                            break;
                        default:
                            if ( $colst > 0 )
                                $buffers[ $colst - 1 ] .= $buffer[$i];
                            else
                                echo $buffer[$i];
                            break;
                    }
                    
                    break;
            }
            
        }
        
        if ( $abort )
            die(1);
    }
    
?>