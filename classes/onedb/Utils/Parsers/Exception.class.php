<?php

    class Utils_Parsers_Exception extends Object {
        
        static public $_trimPath = NULL;
        
        public function explainException( $exception, $maxMessageLength = 48 ) {
            
            $stack = [];
            
            $e = $exception;
            
            $pad = '';
            
            while ( $e ) {
                
                $stack[] = $pad
                    . preg_replace( '/^Exception_/', '', get_class( $e ) )
                    . ': '
                    . substr( $e->getMessage(), 0, $maxMessageLength )
                    . ( $e->getCode() != 0 
                        ? ( ' ( error code: '
                            . $e->getCode()
                            . ' )'
                        )
                        : ''
                    );
                
                $e = $e->getPrevious();
                
            }
            
            return implode( "\n", $stack );
        }
        
    }
    
    Utils_Parsers_Exception::$_trimPath = realpath( __DIR__ . '/../../../../' );

?>