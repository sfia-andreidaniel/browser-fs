<?php

    class Utils_Parsers_Exception extends Object {
        
        static public $_trimPath = NULL;
        
        public function explainException( $exception, $maxMessageLength = 48 ) {
            
            $stack = [];
            
            $e = $exception;
            
            $pad = '';
            
            while ( $e ) {
                
                $stack[] = $pad
                    . get_class( $e )
                    . ': '
                    . substr( $e->getMessage(), 0, $maxMessageLength )
                    . ' [ '
                    . ( $e->getCode() != 0 
                        ? ( 'code '
                            . $e->getCode()
                            . ', '
                        )
                        : ''
                    )
                    . 'line #'
                    . $e->getLine()
                    . ' in "'
                    . str_replace( self::$_trimPath, '', $e->getFile() )
                    . '" ]';
                
                $pad .= '  ';
                
                $e = $e->getPrevious();
                
            }
            
            return implode( "\n", $stack );
        }
        
    }
    
    Utils_Parsers_Exception::$_trimPath = realpath( __DIR__ . '/../../../../' );

?>