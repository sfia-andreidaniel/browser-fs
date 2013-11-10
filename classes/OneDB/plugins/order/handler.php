<?php

    $do = isset( $_POST['do'] ) ? $_POST['do'] : die("What to do?");
    
    switch ($do) {
        
        case 'load':
            $categoryID = isset( $_POST['categoryID'] ) ? $_POST['categoryID'] : die("Which categoryID?");
            $viewName = isset( $_POST['viewName'] ) ? $_POST['viewName'] : die("Which viewName?");
            
            $categoryID = strlen( $categoryID ) ? $categoryID : NULL;
            
            $out = array();
            
            $category = OneDB::get()->categories(
                array(
                    "_id" => MongoIdentifier( $categoryID )
                )
            )->each(
                function( $category ) use (&$out, $viewName) {
                    $order = $category->{"$viewName"};
                    
                    if (is_array( $order )) {
                        
                        $idList = array();
                        
                        foreach ($order as $item) {
                            $idList[] = MongoIdentifier( $item['id'] );
                        }
                        
                        $cursor = OneDB::get()->db->articles->find(
                            array(
                                '_id' => array(
                                    '$in' => $idList
                                )
                            ),
                            array(
                                '_id' => true,
                                'name'=> true,
                                'type'=> true
                            )
                        );
                        
                        $nameMappings = array();
                        
                        while ($cursor->hasNext()) {
                            $row = $cursor->getNext();
                            $nameMappings[ "$row[_id]" ] = array(
                                'name' => $row['name'],
                                'type' => 'item/' . $row['type']
                            );
                        }
                        
                        for ( $i=0, $len=count($order); $i<$len; $i++) {
                            $order[ $i ]['name'] = isset( $nameMappings[ $order[ $i ]['id'] ] ) ? $nameMappings[ $order[ $i ][ 'id' ] ]['name'] : $order[$i]['id'];
                            $order[ $i ]['type'] = isset( $nameMappings[ $order[ $i ]['id'] ] ) ? $nameMappings[ $order[ $i ][ 'id' ] ]['type'] : 'item';
                        }
                        
                        $out = $order;
                    }
                    
                }
            );
            
            echo json_encode( $out );
            
            break;
        
        case 'save-order':
        
            $categoryID = isset( $_POST['categoryId'] ) ? $_POST['categoryId'] : die("Which categoryID?");
            $viewName   = isset( $_POST['viewName'] ) ? $_POST['viewName'] : die("Which viewName?");
            
            if (!strlen( $categoryID ))
                $categoryID = NULL;
        
            $items = isset( $_POST['items'] ) ? @json_decode( $_POST['items'], TRUE ) : die("Which items?");
        
            if (!is_array( $items ))
                die("Bad items!");
        
            OneDB::get()->categories(
                array(
                    '_id' => MongoIdentifier( $categoryID )
                )
            )->each(
                function ($category) use ($items, $viewName) {
                    $category->{"$viewName"} = $items;
                }
            );
        
            echo json_encode('ok');
            
            break;
        
        default:
            throw new Exception("Unknown handler command '$do' in onedb plugin file " . __FILE__ );
    }

?>