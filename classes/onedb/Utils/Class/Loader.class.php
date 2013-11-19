<?php
    
    class Utils_Class_Loader extends Object {
        
        public function init( $className ) {
            
            $className = preg_replace( '/[\.\_]+/', '_', $className );
            
            if ( class_exists( $className ) )
                return TRUE;
            
            $classFile = __DIR__ . '/../../' . str_replace( '_', '/', $className ) . '.class.php';
            
            if ( !file_exists( $classFile ) )
                throw Object( 'Exception.Runtime', 'class file /classes/' . str_replace( '_', '/', $className ) . '.class.php was not found' );
            
            require_once( $classFile );
            
            if ( !class_exists( $className ) )
                throw Object( 'Exception.Runtime', 'after requiring the class file, the class ' . $className . ' was not found!' );
            
            return TRUE;
        }
        
    }
    
?>