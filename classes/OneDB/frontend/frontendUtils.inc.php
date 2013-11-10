<?php

    function addCss( $cssFile, $location = 'head' ) {
        if (!isset($GLOBALS['frontend']))
            throw new Exception("No frontend defined in global variable \$frontend");
        global $frontend;
        $frontend->addDependency('css', $location, $cssFile );
    }

    function addJs( $jsFile, $location = 'head' ) {
        if (!isset($GLOBALS['frontend']))
            throw new Exception("No frontend defined in global variable \$frontend");
        global $frontend;
        $frontend->addDependency('javascript', $location, $jsFile );
    }
    
    function abort() {
        if (!isset($GLOBALS['frontend']))
            throw new Exception("No frontend defined in global variable \$frontend");
        global $frontend;
        $frontend->abort();
    }
    
    function stdout( $buffer ) {
        if (!isset( $GLOBALS['frontend'] ))
            throw new Exception("No frontend defined in global variable \$frontend");
        global $frontend;
        $frontend->stdout( $buffer );
    }

?>