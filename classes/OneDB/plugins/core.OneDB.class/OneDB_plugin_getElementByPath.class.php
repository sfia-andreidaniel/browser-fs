<?php
    
    /* Ensure directory exists, and returns it's OneDB category object */
    
    class OneDB_plugin_getElementByPath extends OneDB {
    
        function getElementByPath( $path, $escape = TRUE ) {

            if ($escape)
                $path = urldecode( $path );

            $isCategory = substr( strrev( $path ), 0, 1 ) == '/';

            if ($path == '/')
                return $this->rootCategory();

            else {

                if ($isCategory) {

                    return $this->categories(
                        array(
                            "selector" => $path
                        )
                    )->get(0);

                } else {
                    $parts = explode( '/', trim( $path, "/" ) );

                    $path = '/' . implode('/', array_slice( $parts, 0, count( $parts ) - 1 ) );
                    $path = $path == '/' ? $path : "$path/";

                    $name = end( $parts );

                    $category = $this->categories(
                        array(
                            "selector" => $path
                        )
                    )->get(0);

                    return $category->articles( 
                        array(
                            "_parent" => $category->_id,
                            "name" => $name
                        )
                    )->get(0);
                }
            }
        }
    }
?>