<?php
    
    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . '/../../bootstrap.php';
    
    term_init( $argv );
    
    term_manual( count( $argv ) == 1 ? 'man' : $argv[1] );
    
?>