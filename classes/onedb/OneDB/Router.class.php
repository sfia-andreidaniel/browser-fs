<?php
    
    require_once __DIR__ . '/Client.class.php';
    
    class OneDB_Router extends Object implements IDemuxable {
        
        protected $_url    = NULL;
        protected $_native = NULL;
        protected $_server = NULL;
        
        public static $__muxType = 'OneDB_Router';
        
        public function init( $url, OneDB_Client $_server ) {
            
            $this->_url    = $url;
            $this->_server = $_server;
            
            if ( get_class( $this ) == 'OneDB_Router' ) {
            
                $this->_native = self::parse( $url );
                
                if ( $this->_native !== NULL ) {
                    
                    // Determine if a class OneDB_Router_... exists, and return a instance of that class
                    
                    // Try to load the appropriated class
                    
                    try {
                        
                        $instance = Object( 'OneDB.Router.' . ucwords( $this->_native[ 'method' ] ), $url, $_server );
                        
                        $instance->setNativeArguments( $this->_native );
                        
                        return $instance;
                        
                    } catch ( Exception $e ) {
                        
                        throw Object( 'Exception.Router', 'Failed to route "' . $url . '": component "' . ucwords( $this->_native['method'] ) . '" not found!', 0, $e );
                        
                    }
                    
                } else {
                    
                    // The URL is a OneDB path?
                    // TODO
                    
                    throw Object( 'Exception.Router', 'Unroutable path!' );
                    
                }
            }
        }
        
        /* The Router parses the argument on constructor, and pass them
           to the Router Class in a parsed way
         */
        protected function setNativeArguments( $args ) {
            $this->_native = $args;
        }
        
        /* Executes the router native handler
         */
        public function run( $additionalRuntimeArguments = NULL ) {
            throw Object( 'Exception.Router', 'The run() method should be implemented on children classes of OneDB.Router class' );
        }
        
        /* Parses a router request, and returns NULL if invalid request,
         * or [ 'method': <string>, 'id': <string>, 'extension' => <nullable string>, 'fragment' => <nullable string> ]
         */
        static public function parse( $url ) {
        
            if ( !preg_match( '/^(\/)?bfs\/([a-z\_]([a-z\_\d]+)?)\/([a-f\d]{24})(\.[a-z\d]+(\.[a-z\d]+)?)?(\#(.*))?$/i', $url, $matches ) )
                return NULL;
            else {
                return [
                    'method'    => $matches[2],
                    'id'        => $matches[4],
                    'extension' => isset( $matches[5] ) && is_string( $matches[5] ) ? trim( $matches[5], '.' ) : NULL,
                    'fragment'  => isset( $matches[8] ) && strlen( $matches[8] ) ? $matches[8] : NULL
                ];
            }
        }
        
        public function __mux() {
            return [ $this->_url, $this->_server->__mux() ];
        }
        
        public static function __demux( $data ) {
            //var_dump( $data );
            list( $server, $url ) = explode("\t", $data );
            return Object('OneDB.Router', $url, OneDB_Client::__demux( $server ) );
        }
        
    }
    
?>