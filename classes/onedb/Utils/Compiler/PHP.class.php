<?php

    class Utils_Compiler_PHP extends Object {
        
        /* Returns TRUE if the PHP code is valid, otherwise
           returns a String indicating the eror
         */
        
        public function compile( $phpCode ) {
            
            $utils = Object( 'Utils.OS' );

            $php = $utils->which( 'php' );
            
            if ( !$php )
                throw Object( 'Exception.OneDB', "The php cli was not found!" );
            
            $tmp = sys_get_temp_dir();
            
            if ( file_put_contents( $diskFile = $tmp . DIRECTORY_SEPARATOR . uniqid() . ".php", $phpCode ) === FALSE )
                return "ERROR: Could not write temp file: $diskFile";
            
            if (!file_exists( $diskFile ))
                return "ERROR: PHP cli file not found!";
            
            $cmdLine = "$php -l $diskFile";
            
            /* Create a process to php, and read stream of php process compilation */
            
            $descriptorspec = array(
                0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
                1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
                2 => array("pipe", "w")   // stderr is a pipe that the child will write to
            );
            
            $process = proc_open($cmdLine, $descriptorspec, $pipes );
            
            if (is_resource( $process )) {
                
                $stdout = stream_get_contents( $pipes[ 1 ] );
                @fclose( $pipes[ 1 ] );
                
                $stderr = stream_get_contents( $pipes[ 2 ] );
                @fclose( $pipes[ 2 ] );
                
                @fclose( $pipes[ 0 ] );
                
                $output = trim( $stderr.$stdout );
                
                proc_close( $process );
            }
            
            @unlink( $diskFile );
            
            $pregTmp = addcslashes( $tmp, DIRECTORY_SEPARATOR . ".!@#$%^&*()_-=+[\{]\};:\"':;<>,.\/?" );
            
            return preg_match('/^No syntax errors detected in/', $stdout ) ? TRUE : preg_replace("/in $pregTmp\/[a-f0-9]+.php on line /", ", line ", $stderr );
        }
        
    }

?>