<?php

    $do = isset( $_POST['do'] ) ? $_POST['do'] : die("What to do?");
    
    switch ($do) {
    
        case 'save':
            
            $data = isset( $_POST['data'] ) ? @json_decode( $_POST['data'], TRUE ) : NULL;
            
            if (!is_array( $data ))
                die("Which / Bad data?");
            
            if (!is_array( $data['database'] ))
                die("Which data.database?");
            
            $theParent = OneDB::get()->categories( array(
                "_id" => MongoIdentifier( $data['database']['_parent'] )
            ) )->get(0);

            if (empty($data['category'] ) && !empty( $data['headlines'] )) {
                throw new Exception("Plase select a category first!");
            }
            
            if (!empty( $data['category'] ) && !empty( $data['croll'] )) {
                throw new Exception("Croll items cannot have a category assigned!");
            }

            $parentTags = $theParent->tags;
            
            if (!is_array( $parentTags ) || !in_array('tv', $parentTags )) {
                throw new Exception("Please create or save items only inside the '/TV/...' folder!");
            }
            
            if ( empty( $data['database']['name'] ) ) {
                
                $newName = strlen( $data['headlines'] ) ? 'h ' . $data['headlines'] : 'c ' . $data['croll'];
                $newName = OneDB_toAscii( $newName );
                
                $newName = OneDB_truncateText(
                    trim( preg_replace( '/[^a-z\d]+/i', ' ', $newName ) ),
                    10,
                    'words',
                    ''
                );
                
                //just in fucking case ... :)
                $newName = strlen( $newName ) ? $newName : "untitled";

                $inc = '';
                
                while ( OneDB::get()->articles(
                        array(
                            "_parent" => $theParent->_id,
                            "name"    => $newName . ($inc == '' ? '' : ' ' . $inc )
                        )
                    )->length ) {
                        $inc = $inc == '' ? 1 : $inc+1;
                        if ($inc > 1000)
                            die("Loop detected!");
                }
                
                $theData = $theParent->createArticle( 'TV_object' );
                $theData->name = $newName . ( $inc == '' ? '' : ( ' ' . $inc ) );
                $theData->owner = 'JSPlatform/Users/' . $_SESSION['UNAME'];
                
            } else {
                
                $theData = OneDB::get()->articles( array(
                    '_id'     => MongoIdentifier( $data['database']['_id'] ),
                    '_parent' => MongoIdentifier( $data['database']['_parent'] )
                ) )->get(0);
                

                $newName = strlen( $data['headlines'] ) ? 'h ' . $data['headlines'] : 'c ' . $data['croll'];
                $newName = OneDB_toAscii( $newName );
                
                $newName = OneDB_truncateText(
                    trim( preg_replace( '/[^a-z\d]+/i', ' ', $newName ) ),
                    10,
                    'words',
                    ''
                );

                //just in fucking case ... :)
                $newName = strlen( $newName ) ? $newName : "untitled";
                $newName .= ( " " . $data['database']['_id'] );
                
                $theData->name = $newName;

            }
            
            $theData->headlines = $data['headlines'];
            $theData->croll     = $data['croll'];
            $theData->textContent = trim( "$data[headlines] $data[croll] $data[category]" );
            
            
            $theData->online    = $data['online'];
            
            $theData->modifier  = 'JSPlatform/Users/' . $_SESSION['UNAME'];
            $theData->revision  = !$theData->revision ? 1 : $theData->revision++;
            $theData->category  = $data['category'];
            
            $theData->save();
            
            echo json_encode( $theData->toArray() );
            
            break;
        
        case 'get':
            
            $_id = isset( $_POST['_id'] ) && strlen( $_POST['_id'] ) ? $_POST['_id'] : die("Which _id?");
            
            $theData = OneDB::get()->articles(
                array(
                    "_id" => MongoIdentifier( $_id )
                )
            )->get(0)->toArray();
            
            $theData['_id'] = "$theData[_id]";
            $theData['_parent'] = "$theData[_parent]";
            
            echo json_encode( $theData );
            
            break;
    
        case 'get-categories':
        
            $out = array();
            
            OneDB::get()->categories( array(
                'tags' => 'TV'
            ))->sort( function( $a, $b ) {
                return strcmp( strtolower( $a->name ), strtolower( $b->name ) );
            } )->each( function($category) use (&$out) {
                $out[] = $category->name;
            } );
            
            echo json_encode($out);
            
            break;
    
        default:
            throw new Exception("Unknown handler command '$do' in onedb plugin file " . __FILE__ );
    }

?>