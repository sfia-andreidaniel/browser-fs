<?php

    $ONEDB_XTEMPLATE = array();
    $XTPL = NULL;
    
    function pushXTemplate( $tpl ) {
        $GLOBALS['ONEDB_XTEMPLATE'][] = $tpl;
        $GLOBALS['XTPL'] = $tpl;
    }
    
    function popXTemplate( ) {
        if (count( $GLOBALS['ONEDB_XTEMPLATE'] )) {
            $GLOBALS['ONEDB_XTEMPLATE'] = array_slice( $GLOBALS['ONEDB_XTEMPLATE'], 0, count( $GLOBALS['ONEDB_XTEMPLATE'] ) - 1);
            $GLOBALS['XTPL'] = count( $GLOBALS['ONEDB_XTEMPLATE'] ) ? end( $GLOBALS['ONEDB_XTEMPLATE'] ) : NULL;
        }
        else {
            $GLOBALS['XTPL'] = NULL;
            throw new Exception("No templates were found in stack!");
        }
    }
    
    function assign( $variable, $value ) {
        $GLOBALS['XTPL']->assign( $variable, $value );
    }
    
    function parse( $blockName ) {
        $GLOBALS['XTPL']->parse( empty($blockName) ? 'main' : "main.$blockName" );
    }
    
    function text( $blockName ) {
        return $GLOBALS['XTPL']->text( empty( $blockName ) ? 'main' : "main.$blockName" );
    }
    
    function out( $blockName ) {
        $GLOBALS['XTPL']->out( empty( $blockName ) ? 'main' : "main.$blockName" );
    }
    
    function abort_block( $blockName ) {
        $GLOBALS['XTPL']->parse( empty( $blockName ) ? 'main' : "main.$blockName" );
        $GLOBALS['XTPL']->reset( empty( $blockName ) ? 'main' : "main.$blockName" );
    }
    
?>