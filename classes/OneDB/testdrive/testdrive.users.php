<?php

    require_once "OneDB.class.php";
    
    $my = new OneDB();
    
    $newUser = $my->users->create(
        array(
            'name' => 'root',
            'email' => 'sfia.andreidaniel@gmail.com'
        )
    );
    
    $newUser->password = '12345';
    
    $newUser->save();
    
    /*
    $newUser = $my->users(
        array(
            "name" => "root"
        )
    )->sort(
        function ($a, $b) {
            return -1;
        }
    )->each(function($u) {
        print_r( $u->toArray() );
    })->memberOf(
        'everyone'
    )->login("12345")->get(0);
    
    print_r( $newUser->toArray() );
    */
    
?>