<?php
    
    /* Ensure directory exists, and returns it's OneDB category object */
    
    class OneDB_plugin_mkdir extends OneDB {
    
        function mkdir( $directory ) {
        
            $directory = trim($directory, '/');
            $parts = $directory == "" ? array() : explode('/', $directory);
        
            $parentDirectory = $this->categories(
                array(
                    'selector' => '/'
                )
            )->get(0);
        
            for ($i = 0, $len=count($parts); $i < $len; $i++) {

                $path = '/' . implode( "/", array_slice( $parts, 0, $i + 1 ) );
                $path = $path == '/' ? '/' : $path . '/';
            
                $endPath = $parts[ $i ];
            
                $validPath = TRUE;
            
                try {
                
                    $parentDirectory = $this->categories(
                        array(
                            "name" => $endPath,
                            "_parent" => $parentDirectory->_id
                        )
                    )->get(0);
                
                } catch (Exception $e) {
                    $validPath = FALSE;
                }
            
                if ($validPath === FALSE) {
                    /* Try to create a folder in the last $parentDirectory */
                    try {
                
                        $parentDirectory = $parentDirectory->createCategory();
                        $parentDirectory->_autoCommit = FALSE;
                        $parentDirectory->name = $endPath;
                
                        $parentDirectory->save();
                    
                    } catch (Exception $e) {
                        throw new Exception("Could not create path: \"$path\": ". $e->getMessage());
                    }
                }
            }
        
            return $parentDirectory;
        }
        
    }
    
?>