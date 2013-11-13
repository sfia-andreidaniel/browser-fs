<?php
    
    class RPC_Assembler extends Object {
        
        protected static $_files = NULL;
        
        public function init() {
            
            if ( self::$_files === NULL ) {
                
                self::$_files = [];
            
                $files = scandir( __DIR__ . '/JS' );
                
                foreach ( $files as $file ) {
                    
                    if ( preg_match( '/^([\d]+)\-(.*)\.js$/', $file, $matches ) ) {
                        
                        self::$_files[] = [
                            'index'    => $matches[1],
                            'name'     => $file,
                            'contents' => file_get_contents( __DIR__ . '/JS/' . $file )
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