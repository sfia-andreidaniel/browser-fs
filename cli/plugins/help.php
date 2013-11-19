<?php

    require_once __DIR__ . "/lib/term.php";
    require_once __DIR__ . "/../../bootstrap.php";
    
    $out = [];
    
    $term = Object( 'Utils.Terminal' );
    
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
            'description' => "selects a onedb site"
        ],
        [
            'command' => 'show users',
            'description' => "shows informations about users"
        ],
        [
            'command' => 'show groups',
            'description' => "shows informations about groups"
        ],
        [
            'command' => 'show websites',
            'description' => "shows informations about websites"
        ],
        [
            'command' => 'useradd',
            'description' => "adds a user in a website"
        ],
        [
            'command' => 'usermod',
            'description' => "set settings for a user from a website"
        ],
        [
            'command' => 'userdel',
            'description' => "deletes an user from a website"
        ],
        [
            'command' => 'groupadd',
            'description' => "adds a group to a website"
        ],
        [
            'command' => 'prepare',
            'description' => "prepares something"
        ],
        [
            'command' => 'su',
            'description' => "switch current working user"
        ],
        [
            'command' => 'cd',
            'description' => "changes current working directory"
        ],
        [
            'command' => 'mkdir',
            'description' => "creates a directory"
        ],
        [
            'command' => 'rm',
            'description' => "removes a directory or a item"
        ],
        [
            'command' => 'man',
            'description' => "display a man page about a command"
        ]
    ];
    
    foreach ( $out as $command )
        echo '  ', $term->color( str_pad( $command[ 'command' ], 14 ), 'green' ), ' ', $command[ 'description' ], "\r";
    
?>