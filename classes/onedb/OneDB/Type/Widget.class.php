<?php

    require_once __DIR__ . '/../Type.class.php';

    // require onedb eval exception
    Object( "Exception.OneDB.Eval" );
    
    // a magic return wrapper for widget php eval'd code
    function _return( $value ) {
        throw Object( "Exception.OneDB.Eval", $value );
    }

    class OneDB_Type_Widget extends OneDB_Type {
        
        static protected $_isContainer = FALSE;
        static protected $_isReadOnly  = FALSE;
        
        protected $_php    = '';
        protected $_html   = '';
        protected $_engine = 'html';
        
        public function exportOwnProperties( array &$properties ) {
            
            $properties[ 'php' ]    = $this->_php;
            $properties[ 'html' ]   = $this->_html;
            $properties[ 'engine' ] = $this->_engine;

        }
        
        public function importOwnProperties( array $properties ) {
            
            $this->_php = isset( $properties['php'] )
                ? $properties['php']
                : '';
            
            $this->_html = isset( $properties['html'] )
                ? $properties['html']
                : '';
                
            $this->_engine = isset( $properties['engine'] )
                ? $properties['engine']
                : 'html';
        }
        
        private function evalXTemplateCode( $php, $html, $ENV = [] ) {

            foreach (array_keys( $ENV ) as $__local ) {
                if (!isset( ${"$__local"} ))
                    ${"$__local"} = $ENV["$__local"];
                else throw Object('Exception.OneDB', "Dupplicate argument name: $__local");
            }
            
            Object( 'Template.XStack' );
            
            pushXTemplate( $tpl = new _XTemplate( '<!-- BEGIN: main -->' . $html . '<!-- END: main -->' ) );
            
            ob_start();
            
            $requireFilename = $this->_root->id . '-' . (
                $this->_root->modified === NULL 
                    ? ( $this->_root->created === NULL 
                        ? '0' 
                        : $this->_root->created 
                    ) 
                    : $this->_root->modified
                ) . '.php';
        
            try {

                if (file_exists( "/dev/shm/$requireFilename" )) {

                    require "/dev/shm/$requireFilename";

                } else {

                    // Scan for old versions, do gc
                    $filesList = scandir("/dev/shm");

                    foreach ($filesList as $file) {
                        if ( preg_match( '/^' . $this->_root->id . '-([\d]+)?\.php$/', $file ) ) {

                            @unlink( "/dev/shm/$file" );

                        }
                    }
                    
                    file_put_contents( "/dev/shm/$requireFilename", $php );
                    
                    require "/dev/shm/$requireFilename";
                
                }
                
                $__eval__ = NULL;

            } catch ( Exception_OneDB_Eval $ret) {
                $__eval__ = $ret->value;
            }
            
            parse('');
            out('');
            
            $__evalResult__ = ob_get_clean();
            
            popXTemplate();

            return !empty( $__evalResult__ ) ? $__evalResult__ : "$__eval__";
        
        }

        private function evalPhpCode( $php, $ENV = [] ) {
            
            foreach (array_keys( $ENV ) as $__local ) {
                if (!isset( ${"$__local"} ))
                    ${"$__local"} = $ENV[$__local];
                else throw Object('Exception.OneDB', "Dupplicate argument name: $__local");
            }
            
            // start output buffering...
            ob_start();
            
            $requireFilename = $this->_root->id . '-' . (
                $this->_root->modified === NULL 
                    ? ( $this->_root->created === NULL 
                        ? '0' 
                        : $this->_root->created 
                    ) 
                    : $this->_root->modified
                ) . '.php';
            
            try {
            
                if ( file_exists( '/dev/shm/' . $requireFilename ) ) {
                    require '/dev/shm/' . $requireFilename;
                } else {
                    
                    // We're trying to do a cleanup first.
                    
                    $filesList = scandir("/dev/shm");
                    
                    foreach ($filesList as $file) {
                        if ( preg_match( '/^' . $this->_root->id . '-([\d]+)?\.php$/', $file ) ) {
                            @unlink( "/dev/shm/$file" );
                        }
                    }

                    file_put_contents( "/dev/shm/$requireFilename", $php );
                    require "/dev/shm/$requireFilename";

                }
                
                $__eval__ = NULL;
            } catch ( Exception_OneDB_Eval $ret ) {
                $__eval__ = $ret->value;
            }
            
            $__evalResult__ = ob_get_clean();
            
            return !empty( $__evalResult__ ) ? $__evalResult__ : "$__eval__";
        }

        
        
        public function run( $ENV = [] ) {
            
            if ( !$this->_root->id )
                throw Object('Exception.OneDB', "Cannot run a widget in an unsaved state!" );
            
            try {
            
                switch ( $this->_engine ) {
                    
                    case 'php':
                    
                        return $this->evalPHPCode( $this->_php, $ENV );
                    
                        break;

                    case 'html':
                    
                        return $this->_html;
                    
                        break;

                    case 'xtemplate':
                        
                        return $this->evalXTemplateCode( $this->_php, $this->_html, $ENV );
                    
                        break;
                        
                    default:
                        return $this->_html;
                        break;
                }
            
            } catch ( Exception $e ) {
                
                return "\n<exception>\n" .
                         "  Error compiling php code in widget '" . $this->_root->url . "'\n" .
                         "  ERROR : " . $e->getMessage() . "\n" .
                         "  LINE  : " . $e->getLine() . "\n" .
                         "  FILE  : " . $e->getFile() . "\n" .
                         "</exception>\n";
            
            }
        }
        
        public function __mux() {
            return [
                "php" => $this->_php,
                "html" => $this->_html,
                "engine" => $this->_engine
            ];
        }
    }
    
    OneDB_Type_Widget::prototype()->defineProperty( 'php', [
        "get" => function() {
            return $this->_php;
        },
        "set" => function( $code ) {
            
            if ( !is_string( $code ) )
                throw Object( 'Exception.OneDB', "The property 'php' of a widget needs to be a string value!" );
            
            // compile code
            if ( strlen( $code ) ) {
            
                $compiler = Object( 'Utils.Compiler.PHP' );
            
                $result = $compiler->compile( $code );
                
            } else $result = TRUE;
            
            // set property only if php compilation went good
            if ( $result === TRUE ) {
            
                $this->_php = $code;
                $this->_root->_change( 'php', $this->_php );
            
            } else {
                
                throw Object( 'Exception.OneDB', "Failed to compile php code: " . $result );
            
            }
        }
    ] );
    
    OneDB_Type_Widget::prototype()->defineProperty( 'html', [
        "get" => function() {
            return $this->_html;
        },
        "set" => function( $markup ) {

            if ( !is_string( $markup ) )
                throw Object( "Exception.OneDB", "The property 'html' of a widget needs to be a string value!" );

            $this->_html = $markup;

            $this->_root->_change( 'html', $this->_html );
        }
    ] );
    
    OneDB_Type_Widget::prototype()->defineProperty( 'engine', [
        
        "get" => function() {
            return $this->_engine;
        },
        
        "set" => function( $engine ) {
        
            if ( !is_string( $engine ) )
                throw Object( 'Exception.OneDB', "The engine property of a widget should be string!" );
        
            if ( !in_array( $engine, [ 'php', 'html', 'xtemplate' ] ) )
                throw Object( 'Exception.OneDB', "The engine property of a widget can be: php, html, or xtemplate" );
        
            $this->_engine = $engine;
        
            $this->_root->_change( 'engine', $this->_engine );
        
        }
        
    ] );
    
?>