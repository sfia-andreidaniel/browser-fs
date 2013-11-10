<?php

    require_once "OneDB.class.php";
    
        $my = new OneDB();
        
        $namespaces = $my->database()->nameSpaces();
        
        print_r( $namespaces );
        
        die("\n");
    
        //echo $my->categories->getCategoryByPath("/Stiri/Stiri Interne"), "\n";
    
        /*

            $my->categories(
                array(
                    "selector" => "/ *"
                )
            )->flatten()
             ->getParent()
             ->filter(
                function($o) {
                    return true; //in_array( $o->name, array( 'Balanta', 'Rac', 'Leu', 'Fecioara' ));
                }
            )->sort(
                function ($a, $b) {
                    return $a->name > $b->name ? 1 : ( $a->name == $b->name ? 0 : -1 );
                }
            )->reverse(
            )->unique( "_id" )->each(
                function($o) {
                    echo "Category: ", $o->name,"\t",$o->_id,"\n";
                }
            )->articles()
             ->sort(
                function($a, $b) {
                    return $a->name > $b->name ? 1 : ( $a->name == $b->name ? 0 : -1 );
                }
            )->reverse(
            )->each(
                function( $o ) {
                    echo "Article: `", $o->name, "` in category: ", $o->getParent()->name, "\n";
                }
            );

    */
    
        /* Create a new Category */
        
        
        /*
        
        try {
        
            $c = $my->rootCategory()->get(0)->createCategory();
            $c->_autoCommit = FALSE;
            $c->name = "New Category 2";
            $c->type = 'Blog';
        
        } catch (Exception $e) {
            throw $e;
        }
        
        */
        
        /* IsChildOf */
        
        /*
        if ($my->categories(
                array(
                    "_id" => new MongoId("4f3bccc28882184d2b000000")
                )
            )->length > 0) echo "da\n";
            else echo "nu\n";
        
        */
        
        /*
        $my->rootCategory()->getChildren()->each(
            function(&$c) {
                echo $c->name,"\t",$c->_id,"\n";
                $c->myCustomProperty = "Test";
                $c->delete();
            }
        );
        */

        $myFile = $my->categories(
            array(
                "selector" => "/"
            )
        )->get(0)->createArticle('File');
        
        $myFile->setContent("This is file\ncontents", "text/plain");
        $myFile->name = "file.txt";

        $myFile->save();
?>