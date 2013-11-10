<?php

    require_once "OneDB.class.php";
    
    try {
    
        $my = new OneDB();
        
        try {
            $myNewForm = $my->forms( )->create('contact', 'post');
    
            print_r(  $myNewForm->toArray() );
        } catch (Exception $e) {
            echo "Form allready created!\n";
        }
        
        $form = $my->forms(
            array(
                'name' => 'contact'
            )
        )->get(0);
        
        print_r( $form->toArray() );
    
    } catch (Exception $e) {
        echo $e->getMessage(),"\n", $e->getLine(),"\n";
    }

?>