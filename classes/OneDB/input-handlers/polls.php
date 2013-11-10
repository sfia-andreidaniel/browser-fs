<?php

    try {
    
        require_once "OneDB.class.php";

        $_id = isset( $_GET['_id'] ) && strlen( $_GET['_id'])  ? MongoIdentifier( $_GET['_id'] ) : die("Which _id?");
        $operation = isset( $_GET['operation'] ) && is_string( $_GET['operation'] ) ? $_GET['operation'] : die("Which operation?");
        
        switch ( $operation ) {
            
            case 'vote':
                
                $my = new OneDB();
                
                $poll = $my->articles( array(
                    '_id'  => $_id,
                    'type' => 'Poll'
                ) )->get(0);
                
                $options = isset( $_POST['options'] ) && strlen( $_POST['options'] ) && is_string( $_POST['options'] ) ?
                    explode(',', $_POST['options'] ) : die("Which options?");
                
                echo json_encode(
                    $poll->vote( $options )
                );
                
                break;
            
            case 'get':
                $my = new OneDB();
                
                $poll = $my->articles( array(
                    '_id'  => $_id,
                    'type' => 'Poll'
                ) )->get(0);
                
                header("Content-Type: text/html");
                
                echo $poll->html();
                
                break;
            
        }

    
    } catch (Exception $e) {
    
        header("Content-Type: text/json");
    
        echo json_encode(array(
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
    }

?>