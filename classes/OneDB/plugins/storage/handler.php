<?php

    $do = isset( $_POST['do'] ) ? $_POST['do'] : die("What to do?");
    
    switch ($do) {
    
        case 'get-storage':
            $items = isset( $_POST['items'] ) ? @json_decode( $_POST['items'], TRUE ) : die("Which items?");
            
            if (!is_array( $items ))
                die("Bad Items");
            
            for ( $i=0, $len=count($items); $i<$len; $i++)
                $items[ $i ] = MongoIdentifier( $items[$i] );
                
            $out = array();
            
            OneDB::get()->articles( array(
                '_id' => array(
                    '$in' => $items
                )
            ) )->each( function ($article) use (&$out) {
                
                $out[ "$article->_id" ] = $article->storageType;
                
            } );
            
            echo json_encode( $out );
            break;
    
        case 'get-storage-types':
            
            $files = scandir( dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'core.OneDB_Storage.class' );
            
            $out = array();
            
            foreach ($files as $file) {
                if ( preg_match('/^OneDB_Storage_([a-zA-Z0-9_]+)\.class\.php$/', $file, $matches ) )
                    $out[] = $matches[1];
            }
            
            echo json_encode( $out );
            
            break;
    
        case 'move-to-storage':
            $_id = isset( $_POST['_id'] ) ? $_POST['_id'] : die("\"Which _id?\"");
            $type = isset( $_POST['type'] ) ? $_POST['type'] : die("\"Which type?\"");
            
            try {
                
                OneDB::get()->articles( array(
                        '_id' => MongoIdentifier( $_id ),
                        'type'=> 'File'
                    ),
                    array(
                    ),
                    1
                )->each( function (&$article) use ($type) {
                    
                    $article->_getStorage()->moveToStorage( $type );
                    
                } );
                
                $result = 'ok';
                
            } catch (Exception $e) {
                $result = 'FAILED: ' . $e->getMessage() . ', in: ' . $e->getFile() . ':' . $e->getLine();
            }
            
            echo json_encode( $result );
            
            break;
    
        default:
            throw new Exception("Unknown handler command '$do' in onedb plugin file " . __FILE__ );
    }

?>