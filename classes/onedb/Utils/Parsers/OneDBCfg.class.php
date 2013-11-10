<?php
    
    class Utils_Parsers_OneDBCfg extends Object {
        
        protected static $inis     = [];
        protected static $instance = NULL;
        
        protected        $_file    = NULL;
        
        // class constructor. keeps cache in static for each
        // realpath(iniFile)
        public function init( $iniFile ) {
            
            if ( self::$instance === NULL )
                self::$instance = $this;
            
            $this->_file = realpath( $iniFile );
            
            if ( $this->_file === FALSE )
                throw Object( 'Exception.IO', "File " . $iniFile . " does not exists!" );
            
            if ( !isset( self::$inis[ $this->_file ] ) ) {
            
                $buffer = @file_get_contents( $iniFile );
                
                if ( $buffer === FALSE )
                    throw Object( 'Exception.IO', "Failed to open ini file: " . $iniFile );
                
                $lines = explode( "\n", $buffer );
                
                $out = [];
                
                $section = [ 'NULL' => TRUE ];
                
                foreach ( $lines as $line ) {
                    
                    switch ( TRUE ) {
                        
                        case preg_match( '/^([\s]+)?\#/', $line ):
                            break;
                        
                        case ( preg_match( '/^([\s]+)?\[([a-z\d\-_]+)\]([\s]+)?$/i', $line, $matches ) ) ? TRUE : FALSE:
                            
                            // flush previous section
                            if ( count( $section ) && !isset( $section['NULL'] ) ) {
                                
                                if ( !isset( $section['name'] ) )
                                    throw Object( "Exception.OneDB", "The section does not contain a 'name' entry!" );
                                
                                if ( !isset( $out[ $section['type'] ] ) )
                                    $out[ $section['type'] ] = [];
                                
                                $out[ $section['type'] ][ $section['name'] ] = $section;
                            }
                            
                            // initialize a new section
                            $section = [
                                'type' => $matches[ 2 ]
                            ];
                            break;
                        
                        case ( preg_match( '/^([\s]+)?([a-z\d_\-\.]+)([\s]+)?\=([\s]+)?(.*)$/i', $line, $matches ) ) ? TRUE : FALSE:
                            
                            if ( isset( $section['NULL'] ) )
                                throw Object( "Exception.OneDB", "Found property outside section!" );
                            
                            $propertyName = $matches[ 2 ];
                            $propertyValue = trim( $matches[ 5 ] );
                            
                            $section[ $propertyName ] = $propertyValue;
                            
                            break;
                        
                        default:
                            // ignore line
                            break;
                        
                    }
                    
                }
                
                // flush previous section
                if ( count( $section ) && !isset( $section['NULL'] ) ) {
                                
                    if ( !isset( $section['name'] ) )
                        throw Object( "Exception.OneDB", "The section does not contain a 'name' entry!" );
                                
                    if ( !isset( $out[ $section['type'] ] ) )
                        $out[ $section['type'] ] = [];
                                
                    $out[ $section['type'] ][ $section['name'] ] = $section;
                }

                self::$inis[ $this->_file ] = $out;
                
                if ( !isset( self::$inis[ $this->_file ][ 'connection' ] ) )
                    self::$inis[ $this->_file ][ 'connection' ] = [];
                
                if ( !isset( self::$inis[ $this->_file ][ 'website' ] ) )
                    self::$inis[ $this->_file ][ 'website' ] = [];
            }
            
        }
        
        public function __get( $propertyName ) {
            
            if ( !isset( self::$inis[ $this->_file ][ 'website' ][ $propertyName ] ) )
                throw Object( "Exception.OneDB", "website $propertyName does not exist" );
            
            return Object( "Utils.Parsers.OneDBCfg.Site", 
                self::$inis[ $this->_file ][ 'website' ][ $propertyName ], 
                self::$inis[ $this->_file ][ 'connection' ]
            );
            
        }
        
        static public function create() {
            return self::$instance;
        }
    }
    
?>