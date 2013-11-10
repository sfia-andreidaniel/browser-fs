<?php

    header("Content-Type: text/json");

    try {

        $id = isset( $_POST['id'] ) && preg_match('/^[\da-f]+$/', $_POST['id']) ? $_POST['id'] : die("Which id?");
        $rating = isset( $_POST['rating'] ) && preg_match('/^(1|2|3|4|5)$/', $_POST['rating'])
            ? $_POST['rating']
            : die("Which rating?");

        if (isset($_GET['ONEDB_AUTH_TOKEN']) && empty($_GET['ONEDB_AUTH_TOKEN']))
            unset($_GET['ONEDB_AUTH_TOKEN']);

        chdir( dirname(__FILE__)."/.." );

        if (!isset($_SESSION))
            session_start();

        require_once "OneDB.class.php";

        $my = new OneDB();
    
        $item = $my->get()->articles( array(
            '_id' => MongoIdentifier(
                $id
            )
        ) )->get(0);
        
        $rateVal = $item->rate( $rating );
    
        $item->save();
    
        echo json_encode(
            $rateVal
        );
    
    } catch (Exception $e) {
    
        echo json_encode(array(
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
    }

?>