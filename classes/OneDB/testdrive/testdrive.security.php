<?php

    require_once "../../conf/globals.php";
    require_once "../../conf/open_db.php";

    require_once "OneDB.class.php";

    $my = new OneDB();
    
    $my->categories(array(
        'selector' => '/ > *'
    ))->each(
        function( $element ) {
            echo $element->name, "\n";
        }
    );

    $security = $my->security('root');
    
    print_r( $security->all );
    
    $acl = $security->{"4ffd2c88888218b118000002"};
    
    print_r( $acl->explain() );
    
    $acl->setAccess( 'r', TRUE, 'user root');
    
    print_r( $acl->explain() );
    
    $acl->setAccess( 'r', FALSE, 'user root' );
    
    print_r( $acl->explain() );
    
    if ($acl->canRead())
        echo "user can read\n";
    else
        echo "user cannot read\n";
?>