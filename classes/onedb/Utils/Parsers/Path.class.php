<?php
    
    /* Path parser.
    
       @author: sfia.andreidaniel@gmail.com
     
     */
    
    class Utils_Parsers_Path extends Object {
        
        static $_singleton = NULL;
        
        public function init() {
            return self::$_singleton != NULL
                ? self::$_singleton
                : self::$_singleton = new self();
        }
        
        public function isAbsolute( $path ) {
            return ( strlen( $path ) == 0 || $path[0] != '/' )
                ? FALSE
                : TRUE;
        }
        
        // return string if param @$str can be resolved
        // as a valid path, or FALSE if the $str parameter is
        // invalid
        
        // possible reasons why should the bath cannot be resolved
        // * the path points outside the root element: example: /foo/../../ -> illegal
        public function resolve( $str ) {
            
            $str = trim( $str, '/' );
            
            // split path in segments
            
            $parts = preg_split( '/[\/]+/', $str );
            
            $len = count( $parts );
            $i   = 0;
            
            while ( $i < $len ) {
                
                if ( $len < 0 )
                    return FALSE;
                
                if ( $i < 0 )
                    $i = 0;
                
                if ( $len == 0 )
                    return '/';
                
                switch ( $parts[$i] ) {
                    // "." segments are eliminated
                    case '.':
                        
                        array_splice( $parts, $i, 1 );
                        
                        $len--;
                        
                        break;
                    
                    // ".." segments are resolving to previous segment
                    case '..':
                    
                        if ( $i == 0 )
                            return FALSE;
                        
                        array_splice( $parts, $i - 1, 2 );
                        
                        $i -=2; //come back to previous segment
                        $len -= 2;
                        
                        break;
                    
                    default:
                        // good path segment
                        $i++;
                        break;
                }
                
            }
            
            return '/' . implode('/', $parts );
            
        }
        
        public function append( $currentPath, $fragment ) {
            
            return $this->isAbsolute( $fragment )
                ? $this->resolve( $fragment )
                : $this->resolve( $currentPath . '/' . $fragment );
            
        }
        
        // substract $segments from a path and return the resulting path
        // substract "/foo/bar", 2 = "/"
        // substract "/foo/bar", 1 = "/foo"
        // return string on success, or FALSE on error
        public function substract( $path, $segments ) {
            
            if ( !is_int( $segments ) || $segments < 0 )
                return FALSE;
            
            for ( $i = 0; $i < $segments; $i++ )
                $path .= '/..';
            
            $result = $this->resolve( $path );
            
            return $result;
        }
        
        // returns the last segment of a path
        // basename "/foo/bar/" = "bar"
        // basename "/foo/bar"  = "bar"
        // basename "/"         = FALSE

        // return FALSE on error or the resulting segment on success.

        // if the resulting segment is url encoded, it decodes the segment with
        // function urldecode.
        public function basename( $path ) {
            
            $path = $this->resolve( $path );
            
            if ( $path == FALSE )
                return FALSE;
            
            if ( preg_match( '/\/([^\/]+)$/', $path, $matches ) ) {
                
                $result = $matches[1];
                
                $result = str_replace( '+', '%20', $result );
                
                return urldecode( $result );
                
            } else
                return FALSE;
        }
    }
    
?>