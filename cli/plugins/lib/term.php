<?php
    
    /* Terminal library handy for writing plugins */
    
    $_ENV_ = [];
    
    function term_init( &$args ) {
    
        global $_ENV_;
    
        for ( $i = count($args) - 1; $i>0; $i-- ){
        
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
    
?>