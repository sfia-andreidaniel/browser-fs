<?php
    
    class RPC_Assembler extends Object {
        
        protected static $_files = NULL;
        
        private function fix_exception_messages( $source_file, $source_code ) {
            
            if ( !is_string( $source_code ) )
                return $source_code; // We don't want to mess something
            
            $line = 0;
            $lines = preg_split( '/(\r)?\n/', $source_code );
            
            $sfile = json_encode( $source_file );
            
            for ( $i = 0, $len = count( $lines ); $i<$len; $i++ ) {
                
                $lines[$i] = str_replace( '__FILE__', $sfile, $lines[$i] );
                $lines[$i] = str_replace( '__LINE__', ( $i + 1 ), $lines[$i] );
                
            }
            
            return implode( "\n", $lines );
        }
        
        public function init() {
            
            if ( self::$_files === NULL ) {
                
                self::$_files = [];
            
                $files = scandir( __DIR__ . '/JS' );
                
                foreach ( $files as $file ) {
                    
                    if ( preg_match( '/^([\d]+)\-(.*)\.js$/', $file, $matches ) ) {
                        
                        self::$_files[] = [
                            'index'    => $matches[1],
                            'name'     => $file,
                            'contents' => $this->fix_exception_messages( $file, file_get_contents( __DIR__ . '/JS/' . $file ) )
                        ];
                        
                    }
                    
                }
                
                usort( self::$_files, function ( $a, $b ) {
                    
                    return ~~$a[ 'index' ] - ~~$b[ 'index' ];
                    
                } );
            
            }
            
            //print_r( self::$_files );
            
        }
        
    }
    
    RPC_Assembler::prototype()->defineProperty( 'code', [
        
        "get" => function() {
            
            $out = [];
            
            foreach ( self::$_files as $file ) {
                
                $out[] = $file['contents'] . "\n";
                
            }
            
            return implode( "\n", $out );
            
        }
        
    ] );
    
?>