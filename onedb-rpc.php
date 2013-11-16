<?php
    
    // ONEDB VERSION 2 RPC SERVER SIDE IMPLEMENTATION
    // THIS FILE IS INTENDED TO BE USED ALONG WITH THE CLIENT SIDE
    // IMPLEMENTATION OF THE FRAMEWORK, LOCATED IN classes/onedb/drivers/onedb.rpc.js
    //
    // @author: sfia.andreidaniel@gmail.com
    // @package: OneDB v2
    
    require_once __DIR__ . "/bootstrap.php";
    
    $stdout = '';
    
    // setup a rpc exception handler to modify the output of the rpc
    // if an async exception occurs
    set_exception_handler( function( $e ) use ( &$stdout ) {
    
        $stdout = json_encode([
            'ok' => FALSE,
            'error' => TRUE,
            'reason' => Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 )
        ]);
        
    });
    
    // on shutdown, we dump the stdout to client
    register_shutdown_function( function() use ( &$stdout ) {
        
        // suppress error reporting. in case we'll get an exception
        // after this point it will be suppressed.
        
        
        // error_reporting( 0 );
        // ini_set( 'display_errors', 'off' );
        
        // send an aditional status 200 code, in case the server
        // will encounter an exception to enable the rpc to decode
        // the json response
        header( 'X-RPC-STATUS: 200' );
        header( 'Content-Type: application/json' );
        
        echo $stdout;
    } );
    
    try {
    
        $demuxer = Object( 'RPC.Demuxer' );
        $muxer   = Object( 'RPC.Muxer' );
    
        $do = isset( $_POST['do'] )
            ? $_POST['do']
            : NULL;
        
        switch ( $do ) {
            
            case 'set-property':
                
                $on = isset( $_POST['on'] ) // Instance class name
                    ? $_POST['on']
                    : NULL;
                
                if ( !is_string( $on ) || !strlen( $on ) )
                    throw Object( 'Exception.RPC', "Illegal 'on' clause!" );
                
                $instance = isset( $_POST['instance'] ) // Muxed instance data
                    ? $_POST['instance']
                    : NULL;
                
                if ( !is_string( $instance ) || !strlen( $instance ) )
                    throw Object( 'Exception.RPC', "Illegal 'instance' clause!" );
                
                $instance = @json_decode( $instance, TRUE );
                
                if ( !is_array( $instance ) )
                    throw Object( 'Exception.RPC', "Illegal json data in 'instance'" );
                
                $property = isset( $_POST['property'] ) // Name of property to retrieve
                    ? $_POST['property']
                    : NULL;
                
                if ( !is_string( $property ) || !strlen( $property ) )
                    throw Object( 'Exception.RPC', "Illegal property name" );
                
                $value = isset( $_POST['value'] )
                    ? $_POST['value']
                    : NULL;
                
                if ( !is_string( $value ) || !strlen( $value ) )
                    throw Object('Exception.RPC', "Illegal 'value' clause!" );
                
                $value = @json_decode( $value, TRUE );
                
                // Demux class instance
                
                $instance = $demuxer->demux( $instance, DEMUX_ENSURE_INSTANCE );
                
                if ( ( $inst = get_class( $instance ) ) !== $on )
                    throw Object('Exception.RPC', 'The resulted demux class instance is not an instance of a "' . $on . '" class but an instance of "' . $inst . '"' );
                
                // Demux property value
                $value = $demuxer->demux( $value );
                
                $property = explode( '.', $property );
                $result   = $instance->{$property[0]};
                
                for ( $i = 1, $len = count( $property ) - 1; $i<$len; $i++ )
                    $result = $result->{$property[$i]};
                
                // Set value
                $result->{$property[ count( $property ) - 1 ]} = $value;
                
                $stdout = json_encode( [
                    'ok' => TRUE,
                    'result' => $muxer->mux( $result )
                ] );
                
                break;

            case 'get-property':
                
                $on = isset( $_POST['on'] ) // Instance class name
                    ? $_POST['on']
                    : NULL;
                
                if ( !is_string( $on ) || !strlen( $on ) )
                    throw Object( 'Exception.RPC', "Illegal 'on' clause!" );
                
                $instance = isset( $_POST['instance'] ) // Muxed instance data
                    ? $_POST['instance']
                    : NULL;
                
                if ( !is_string( $instance ) || !strlen( $instance ) )
                    throw Object( 'Exception.RPC', "Illegal 'instance' clause!" );
                
                $instance = @json_decode( $instance, TRUE );
                
                if ( !is_array( $instance ) )
                    throw Object( 'Exception.RPC', "Illegal json data in 'instance'" );
                
                $property = isset( $_POST['property'] ) // Name of property to retrieve
                    ? $_POST['property']
                    : NULL;
                
                if ( !is_string( $property ) || !strlen( $property ) )
                    throw Object( 'Exception.RPC', "Illegal property name" );
                
                // Demux class instance
                
                $instance = $demuxer->demux( $instance, DEMUX_ENSURE_INSTANCE );
                
                if ( ( $inst = get_class( $instance ) ) !== $on )
                    throw Object('Exception.RPC', 'The resulted demux class instance is not an instance of a "' . $on . '" class but an instance of "' . $inst . '"' );
                
                $property = explode( '.', $property );
                $result   = $instance->{$property[0]};
                
                for ( $i = 1, $len = count( $property ); $i<$len; $i++ )
                    $result = $result->{$property[$i]};
                
                $stdout = json_encode( [
                    'ok' => TRUE,
                    'result' => $muxer->mux( $result )
                ] );
                
                break;
            
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
                
                if ( strpos( $method, '.' ) === FALSE )
                    $result = call_user_func_array( [ $instance, $method ], $args );
                else {
                    $path = explode( '.', $method );
                    
                    for ( $i=0, $len = count( $path ) - 1; $i<$len; $i++ )
                        $instance = $instance->{$path[$i]};
                    
                    $result = call_user_func_array( [ $instance, $path[ count($path ) - 1 ] ], $args );
                }
            
                $stdout = json_encode([
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
        
        $stdout = json_encode([
            'ok' => FALSE,
            'error' => TRUE,
            'reason' => Object( 'Utils.Parsers.Exception' )->explainException( $e, 128 )
        ]);
        
    }
?>