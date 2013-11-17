<?php
    require_once __DIR__ . "/../../bootstrap.php";
    
    $out = [];
    
    $term = Object( 'Utils.Terminal' );
    
    echo  "\n";
    
    $out = [
        [
            'command' => 'help',
            'description' => 'displays this help'
        ],
        [
            'command' => 'clear',
            'description' => 'clears console screen'
        ],
        [
            'command' => 'version',
            'description' => 'display this console software version'
        ],
        [
            'command' => 'use',
            'description' => "selects a onedb site\r
              EXAMPLE: use loopback - selects onedb site 'loopback'"
        ]
    ];
    
    foreach ( $out as $command )
        echo '  ', $term->color( str_pad( $command[ 'command' ], 8 ), 'green' ), '    ', $command[ 'description' ], "\n\n";
    
?>