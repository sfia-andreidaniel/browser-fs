<?php

    class OneDB_EvalException extends Exception {
        public $value = null;
        public function __construct( $value ) {
            $this->value = $value;
            parent::__construct();
        }
    }
    
    function _return( $value ) {
        throw new OneDB_EvalException( $value );
    }

    class OneDB_Article_plugin_Widget {

        protected $_ = NULL;
        protected $_ENV = array();

        public function __construct( &$that ) {
            $this->_ = $that;
        }
        
        public function developerModeRun( $ENV = array(), $compile = TRUE ) {
        
            $time = microtime();
            $time = explode(' ', $time);
            $time = $time[1] + $time[0];
            $start = $time;

        
        
        
            $buffer = "<!-- DOCTYPE HTML -->\n<html>\n<head>\n<title>Developer exec - Widget " . 
                      htmlentities( $this->_->name, ENT_COMPAT, 'utf-8' ) . 
                      "</title>\n";
            
            $buffer .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
            
            $base = $this->_->baseURL;
            
            if (!empty( $base ))
                $buffer .= "<base href=\"$base\" />\n";
            
            $dependencies = $this->dependencies();
            
            foreach ($dependencies['javascript']['files'] as $dependency) {
                if ($dependency['location'] == 'head') {
                    $buffer .= "<script type=\"text/javascript\" src=\"$dependency[path]\"></script>\n";
                }
            }
            
            foreach ($dependencies['css']['files'] as $dependency) {
                if ($dependency['location'] == 'head') {
                    $buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$dependency[path]\" />\n";
                }
            }
            
            $buffer .= "<style>exception { white-space: pre; color: red; font-family: courier; font-size: 10px;}</style>\n";
            
            $css = $this->_->css;
            if (!empty( $css ))
                $buffer .= "<style>" . $this->_->css . "</style>\n";
                
            $buffer .= "</head>\n<body>\n<!-- WIDGET-BOUNDARY -->";
            
            $buffer .= $this->enableDevMode( $this->run( $ENV, $compile, FALSE ) ) . "<!-- WIDGET-BOUNDARY -->\n";
            
            $javascript = $this->_->javascript;
            if (!empty( $javascript ))
                $buffer .= "<script type=\"text/javascript\">" . $this->_->javascript . "</script>\n";
            
            
            $time = microtime();
            $time = explode(' ', $time);
            $time = $time[1] + $time[0];
            $finish = $time;
            $total_time = round(($finish - $start), 4);


            foreach ($dependencies['javascript']['files'] as $dependency) {
                if ($dependency['location'] == 'body') {
                    $buffer .= "<script type=\"text/javascript\" src=\"$dependency[path]\"></script>\n";
                }
            }
            
            foreach ($dependencies['css']['files'] as $dependency) {
                if ($dependency['location'] == 'body') {
                    $buffer .= "<link rel=\"stylesheet\" type=\"text/css\" href=\"$dependency[path]\" />\n";
                }
            }

            $buffer .= "<div style='display: block; padding: 2px; position: absolute; top: 0px; left: 0px; right: 0px; font-size: 10px; font-family: courier; background-color: black; color: white; font-weight: bold; opacity: 0.2;' onmouseover=\"this.style.opacity = 1;\" onmouseout=\"this.style.opacity = 0.2;\">$total_time seconds</div>\n";
            
            $buffer .= "</body>\n</html>";
            
            return $buffer;
        }

        private function stripPHPBeginEndTags( $code ) {
            $code = preg_replace('/^([\s]+)?<\?php/', '', $code );
            $code = preg_replace('/\?>([\s]+)?$/', '', $code );
            return $code;
        }

        private function evalPHPCode( $php, $ENV = array() ) {
        
            // eval( OneDB_ImportArgumentsToLocalScope( $ENV, 'ENV' ) );
            
            foreach (array_keys( $ENV ) as $__local ) {
                if (!isset( ${"$__local"} ))
                    ${"$__local"} = $ENV[$__local];
                else throw new Exception("Dupplicate argument name: $__local");
            }
        
            ob_start();
            
            // This should was replaced
            // $__eval__ = eval( $this->stripPHPBeginEndTags( $php ) );
            
            // With This...
            
            $requireFilename = $this->_->_id . '-' . ( $this->_->modified === NULL ? $this->_->date : $this->_->modified ) . '.php';
            
            try {
                
                if (file_exists( "/dev/shm/$requireFilename" )) {
                    require "/dev/shm/$requireFilename";
                } else {
                    // Scan for old versions, do gc
                    $filesList = scandir("/dev/shm");
                    foreach ($filesList as $file) {
                        if ( preg_match( '/^' . $this->_->_id . '-([\d]+)?\.php$/', $file ) ) {
                            @unlink( "/dev/shm/$file" );
                        }
                    }
                    file_put_contents( "/dev/shm/$requireFilename", $php );
                    require "/dev/shm/$requireFilename";
                }
                $__eval__ = NULL;
            } catch ( OneDB_EvalException $ret) {
                $__eval__ = $ret->value;
            }
            

            $__evalResult__ = ob_get_clean();

            return !empty( $__evalResult__ ) ? $__evalResult__ : "$__eval__";
        }
        
        private function evalXTemplateCode( $php, $html, $ENV = array() ) {
            
            // This was replaced
            // eval( OneDB_ImportArgumentsToLocalScope( $ENV, 'ENV' ) );

            // ... with this
            foreach (array_keys( $ENV ) as $__local ) {
                if (!isset( ${"$__local"} ))
                    ${"$__local"} = $ENV["$__local"];
                else throw new Exception("Dupplicate argument name: $__local");
            }
        
            require_once ( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .
                           ".." . DIRECTORY_SEPARATOR . "xtemplate" . DIRECTORY_SEPARATOR . "_XTemplate.class.php"
                         );
            
            require_once ( dirname( __FILE__ ) . DIRECTORY_SEPARATOR .
                           ".." . DIRECTORY_SEPARATOR . "xtemplate" . DIRECTORY_SEPARATOR . "_XTemplateStack.inc.php"
                         );
            
            pushXTemplate( $tpl = new _XTemplate( '<!-- BEGIN: main -->' . $html . '<!-- END: main -->' ) );
            
            ob_start();


            //$__eval__ = eval( $this->stripPHPBeginEndTags( $php ) );


            $requireFilename = $this->_->_id . '-' . ( $this->_->modified === NULL ? $this->_->date : $this->_->modified ) . '.php';
            
            try {
                if (file_exists( "/dev/shm/$requireFilename" )) {
                    require "/dev/shm/$requireFilename";
                } else {
                    // Scan for old versions, do gc
                    $filesList = scandir("/dev/shm");
                    foreach ($filesList as $file) {
                        if ( preg_match( '/^' . $this->_->_id . '-([\d]+)?\.php$/', $file ) ) {
                            @unlink( "/dev/shm/$file" );
                        }
                    }
                    file_put_contents( "/dev/shm/$requireFilename", $php );
                    require "/dev/shm/$requireFilename";
                }
                $__eval__ = NULL;
            } catch ( OneDB_EvalException $ret) {
                $__eval__ = $ret->value;
            }

            parse('');
            out('');
            $__evalResult__ = ob_get_clean();
            
            
            popXTemplate();

            return !empty( $__evalResult__ ) ? $__evalResult__ : "$__eval__";
        }
        
        public function setEnv( $env ) {
            $this->_ENV = $env;
            return $this;
        }
        
        private function disableDevMode( $str, $strip ) {
            return preg_replace('/(\n|^)([\S \t]+)<\!\-\- \!dev \-\->([\s]+)?(\n|$)/', $strip ? "$1$4" : "$1<!-- !dev-escaped -- $2 -->$4", $str );
        }
        
        private function enableDevMode( $str ) {
            return preg_replace('/(\n|^)<\!\-\- \!dev\-escaped \-\- ([\S \t]+) \-\->(\n|$)/', "$1$2<!-- !dev -->$3", $str );
        }
        
        public function run( $ENV = array(), $compile = FALSE, $strip = TRUE ) {
            
            $ENV = array_merge( is_array( $ENV ) ? $ENV : array(), $this->_ENV );
            
            try {
                
                $php = $this->_->php;
                $html= $this->_->html;
                
                if ($compile && !empty( $php ) ) {
                    $result = OneDB_PHPCompiler( $php );
                    if ($result !== TRUE)
                        throw new Exception( $ENV, $result );
                }
                
                $templateEngine = $this->_->templateEngine;
                
                switch ("$templateEngine") {
                
                    case "":
                        return $this->disableDevMode( "{$this->_->html}", $strip );
                        break;
                        
                    case "php":
                    case "javascript":
                        try {
                            
                            return $this->disableDevMode( $this->evalPHPCode( $php, $ENV ), $strip );
                            
                        } catch (Exception $e) {
                            return "\n<exception>\n" .
                                     "  Exception encountered while evaluating php code: \n".
                                     "  WIDGET : " . $this->_->getPath() . DIRECTORY_SEPARATOR . $this->_->name . "\n" .
                                     "  ERROR  : " . $e->getMessage() . "\n".
                                     "  LINE   : " . $e->getLine() . "\n" .
                                     "  FILE   : " . $e->getFile() . "\n" .
                                     "</exception>\n";
                        }
                        
                        break;
                    
                    case "xtemplate":
                        
                        try {
                            return $this->disableDevMode( $this->evalXTemplateCode( $php, $html, $ENV ), $strip );
                        } catch (Exception $e) {
                            return "\n<exception>\n" .
                                     "  Exception encountered while evaluating XTemplate code: \n".
                                     "  WIDGET : " . $this->_->getPath() . DIRECTORY_SEPARATOR . $this->_->name . "\n" .
                                     "  ERROR  : " . $e->getMessage() . "\n".
                                     "  LINE   : " . $e->getLine() . "\n" .
                                     "  FILE   : " . $e->getFile() . "\n" .
                                     "</exception>\n";
                        }
                        
                        break;
                    
                    default:
                        return "\n<exception>\n" .
                                 "  widget type $templateEngine not implemented\n" .
                                 "</exception>\n";
                }
                
            } catch (Exception $e) {
                return "\n<exception>\n" .
                         "  Error compiling php code in widget '" . $this->_->getPath() . DIRECTORY_SEPARATOR . $this->_->name . "\n" .
                         "  ERROR : " . $e->getMessage() . "\n" .
                         "  LINE  : " . $e->getLine() . "\n" .
                         "  FILE  : " . $e->getFile() . "\n" .
                         "</exception>\n";
            }
            
        }

        private function dependencyLocation( $str ) {
            if (preg_match('/^@(head|body): ([^*]+)$/', $str, $matches)) {
                return array(
                    "location" => $matches[1],
                    "path"     => $matches[2]
                );
            } else {
                return array(
                    "location" => "head",
                    "path"     => $str
                );
            }
        }

        public function dependencies() {
            $out = array(
                'css' => array(
                    'inline' => '',
                    'files'  => array()
                ),
                'javascript' => array(
                    'inline' => '',
                    'files'  => array()
                )
            );

            $css = $this->_->css;
            $out['css']['inline'] = empty( $css ) ? "" : trim($css);

            $js = $this->_->javascript;
            $out['javascript']['inline'] = empty( $js ) ? "" : trim( $js );
            
            $_developer = $this->_->developer;

            if (is_array( $_developer )) {
                if (isset( $_developer["css_inc"] ) && is_array( $_developer["css_inc"] ) ) {
                    foreach ($_developer["css_inc"] as $cssInclude) {
                        if (substr( $cssInclude, 0, 1 ) != '#')
                            $out['css']['files'][] = $this->dependencyLocation( $cssInclude );
                    }
                }
                if (isset( $_developer["js_inc"] ) && is_array( $_developer["js_inc"] ) ) {
                    foreach ($_developer["js_inc"] as $jsInclude) {
                        if (substr( $jsInclude, 0, 1 ) != '#')
                            $out['javascript']['files'][] = $this->dependencyLocation( $jsInclude );
                    }
                }
            }
            
            return $out;
        }

    }

?>