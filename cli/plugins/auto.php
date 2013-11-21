<?php
    
    require_once __DIR__ . '/lib/term.php';
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    /* The argument meanings of this script are as follow:
    
       $arg[0] => script name. ignore
       
       $arg[1] => argument index used for auto completion
       
       $arg[2] => if ( argument index > 0 ) the first argument value of the whole command
                  otherwise "0" <string = '0'>
       
       $arg[3] => what the user allready written at cursor position of the current argument
                  arg3 is optional, and assumed to be '' empty string
       
     */
    
    // no output is sent to the autocomplete engine
    if ( count( $argv ) < 3 )
        die(1);
    
    // the argument index we're trying to give suggestions
    $argIndex = (int)$argv[1];
    
    // the 0-index argument of the command
    $command  = $argv[2];
    
    if ( $command == '0' )
        $command = '';
    
    if ( $command == '' && $argIndex > 0 )
        // cannot complete the n-th argument of an empty command (n > 0)
        return;
    
    // the allready written part of the command of the argument $argIndex
    $argWritten = isset( $argv[3] )
        ? $argv[3]
        : '';
    
    // sources from where to obtain suggestions
    $sources = [];
    
    switch ( TRUE ) {
        
        // first argument is allready suggested from the man commands
        case $argIndex == 0:
            
            $sources[] = [
                'type' => 'man'
            ];
            
            break;
        
        case $argIndex > 0:
            // check to see if there is an auto-completion explain of the
            // argument
            
            // check to see if there is the command $command has an autocomplete
            // definition file in the __DIR__/autocomplete/$command.json
            
            if ( preg_match( '/^[a-z\d_\-]+$/i', $command ) ) {
                
                if ( file_exists( $filename = __DIR__ . '/autocomplete/' . $command . '.json' ) ) {
                    
                    // read autocomplete definitions list
                    $buffer = @file_get_contents( $filename );
                    
                    if ( !empty( $buffer ) ) {
                        
                        $defs = @json_decode( $buffer, TRUE );
                        
                        if ( is_array( $defs ) ) {
                            
                            for ( $i=0, $len = count( $defs ); $i<$len; $i++ ) {
                                
                                if ( isset( $defs[$i][ 'index' ] ) ) {
                                    
                                    // parse index from current autocomplete definition
                                    // the index can be in format:
                                    //
                                    // num
                                    // num..num
                                    
                                    if ( preg_match( '/^([\d]+)(\.\.([\d]+))?$/', $defs[$i]['index'], $matches ) ) {
                                        
                                        $indexStart = ~~$matches[1];
                                        
                                        $indexStop  = isset( $matches[3] ) && is_string( $matches[3] ) && strlen( $matches[3] )
                                            ? ~~$matches[3]
                                            : $indexStart;
                                        
                                        if ( $argIndex >= $indexStart && $argIndex <= $indexStop && isset( $defs[$i]['type'] ) ) {
                                            
                                            switch ( $defs[$i]['type'] ) {
                                                
                                                case 'man':
                                                case 'fs':
                                                case 'groups':
                                                case 'users':
                                                case 'websites':
                                                    $sources[] = [
                                                        'type' => $defs[$i]['type']
                                                    ];
                                                    break;
                                                case 'strings':
                                                    
                                                    if ( isset( $defs[$i]['values'] ) && is_array( $defs[$i]['values'] ) )
                                                        $sources[] = [
                                                            'type' => 'strings',
                                                            'values' => $defs[$i]['values']
                                                        ];
                                                    
                                                    break;
                                                
                                            }
                                            
                                        }
                                        
                                    }
                                    
                                }
                                
                            }
                            
                        }
                        
                    }
                    
                }
                
            }
            
            break;
    }
    
    // create the final sources of autosugester.
    $fsources = [];
    
    require_once __DIR__ . '/lib/autocomplete.php';
    
    for ( $i=0, $len = count( $sources ); $i<$len; $i++ ) {
        
        if ( $sources[$i]['type'] != 'strings' ) {
            $fsources[ $sources[$i]['type'] ] = $sources[$i];
        } else {
            
            if ( !isset( $fsources['strings'] ) )
                $fsources['strings'] = [
                    'type' => 'strings',
                    'values' => []
                ];
            
            for ( $j=0, $n = count( $sources[$i]['values'] ); $j < $n; $j++ ) {
                $fsources['strings'][ 'values' ][] = $sources[$i]['values'][$j];
            }
            
        }
        
    }
    
    $fsources = array_values( $fsources );
    
    for ( $i=0, $len = count( $fsources ); $i<$len; $i++ ) {
        
        if ( $fsources[$i]['type'] == 'strings' )
            continue;
        
        
        switch ( $fsources[$i]['type'] ) {
            
            case 'man':
                $fsources[$i]['values'] = autocomplete_get_man( $argWritten );
                break;
            
            case 'fs':
                $fsources[$i]['values'] = autocomplete_get_fs( $argWritten );
                break;
            
            case 'groups':
                $fsources[$i]['values'] = autocomplete_get_groups( $argWritten );
                break;
            
            case 'users':
                $fsources[$i]['values'] = autocomplete_get_users( $argWritten );
                break;
            
            case 'websites':
                $fsources[$i]['values'] = autocomplete_get_websites( $argWritten );
                break;
            
            default:
                break;
        }
        
    }
    
    // merge all autocompleted source into a single source, make source items be unique,
    // sort items, and dump items separated by new line
    
    $out = [];
    
    foreach ( $fsources as $source )
        $out = array_merge( $out, $source['values'] );
    
    $out = array_values( array_unique( $out ) );
    
    $out = array_filter( $out, function( $item ) use ($argWritten) {
        
        if ( $argWritten != '' )
            return strpos( $item, $argWritten ) === 0 && $item != $argWritten;
        else return TRUE;
        
    } );
    
    sort( $out );
    
    echo implode( "\r", $out );
    
?>