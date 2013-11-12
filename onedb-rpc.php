<?php
    
    // ONEDB VERSION 2 RPC SERVER SIDE IMPLEMENTATION
    // THIS FILE IS INTENDED TO BE USED ALONG WITH THE CLIENT SIDE
    // IMPLEMENTATION OF THE FRAMEWORK, LOCATED IN classes/onedb/drivers/onedb.rpc.js
    //
    // @author: sfia.andreidaniel@gmail.com
    // @package: OneDB v2
    
    require_once __DIR__ . "/bootstrap.php";
    
    try {
    
        $demuxer = Object( 'RPC.Demuxer' );
        $muxer   = Object( 'RPC.Muxer' );
    
        $do = isset( $_POST['do'] )
            ? $_POST['do']
            : NULL;
        
        switch ( $do ) {
            
            case 'run-method':
            
                $on = isset( $_POST['on'] )
                    ? $_POST['on']
                    : NULL;
                
                if ( !is_string( $on ) || !strlen( $on ) )
                    throw Object( 'Exception.RPC', "Invalid rpc 'on' clause: " . json_encode( $on ) );
            
                $method = isset( $_POST['method'] )
                    ? $_POST['method']
                    : NULL;
                
                if ( !is_string( $method ) || !strlen( $method ) )
                    throw Object( 'Exception.RPC', "Invalid rpc 'method' clause: " . json_encode( $method ) );
            
                $instance = isset( $_POST['instance'] )
                    ? $_POST['instance']
                    : NULL;
                
                if ( !is_string( $instance ) || !strlen( $instance ) )
                    throw Object( 'Exception.RPC', "Invalid rpc 'instance' clause: " . json_encode( $instance ) );
                
                $args = isset( $_POST['args'] )
                    ? $_POST['args']
                    : NULL;
                
                if ( !is_string( $args ) || !strlen( $args ) )
                    throw Object( 'Exception.RPC', "Invalid rpc 'args' clause: " . json_encode( $args ) );
                
                $instance = @json_decode( $instance, TRUE );
                
                if ( empty( $instance ) )
                    throw Object( 'Exception.RPC', 'Bad instance data (unjsonable)' );
                
                $args = @json_decode( $args, TRUE );
                
                if ( empty( $args ) )
                    throw Object( 'Exception.RPC', 'Bad args data (unjsonable)' );
                
                $instance = $demuxer->demux( $instance, DEMUX_ENSURE_INSTANCE );
                $args     = $demuxer->demux( $args    , DEMUX_ENSURE_ARRAY );
                
                if ( get_class( $instance ) != $on )
                    throw Object( 'Exception.RPC', 'The demuxed instance snapshot is not a "' . $on . '"' );
            
                $result = call_user_func_array( [ $instance, $method ], $args );
            
                echo json_encode([
                    'ok' => TRUE,
                    'result' => $muxer->mux( $result )
                ]);
            
                break;
            
            default:
                throw Object( 'Exception.RPC', "Invalid handler command: " . json_encode( $do ) );
                break;
            
        }
    
        //print_r( $_POST );
    
    } catch  ( Exception $e ) {
        
        die( json_encode([
            'ok' => FALSE,
            'error' => TRUE,
            'reason' => Object( 'Utils.Parsers.Exception' )->explainException( $e ),
            'request' => $_POST
        ]) );
        
    }
?>