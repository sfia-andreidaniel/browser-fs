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
        public function substract( $path, $segments = 0 ) {
            
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
        
        // returns the last segment of the path
        // path will become shorter with that segment
        // examples:
        // pop "foo/ba" => "foo/"
        // pop "foo/bar" => "foo/"
        // pop "foo/bar/char" => "foo/bar/"
        // pop "foo/" => "foo/"
        // NOTE THE BEHAVIOUR, the resulting path will always end in a trailing slash
        // @return string or FALSE when $path is not valid
        public function pop( &$path ) {
            
            if ( !is_string( $path ) )
                return FALSE;
            
            if ( preg_match( '/\/([^\/]+)?$/', $path, $matches ) ) {
                
                $segment = isset( $matches[1] ) ? $matches[1] : '';
                
                $path = ( $len = strlen( $segment ) )
                    ? substr( $path, -strlen( $segment ) )
                    : $path;
                
                return $segment;
                
            } else {
                
                $segment = $path;
                $path = '/';
                
                return $segment;
            }
            
        }
        
        // convers a path with urlencoded segments into a path with urldecoded segments
        // however, the "/" character will always remain encoded as %2F
        public function decode( $str ) {
            
            $str = $this->resolve( $str );
            
            if ( $str === FALSE )
                return FALSE;
            
            $segments = explode( '/', trim( $str, '/' ) );
            
            for ( $i=0, $len = count( $segments ); $i<$len; $i++ ) {
                
                $segments[$i] = str_replace( '+', '%20', $segments[$i] );
                
                $segments[$i] = urldecode( $segments[$i] );
                
                $segments[$i] = str_replace( '"', '%22', str_replace( '/', '%2F', $segments[$i] ) );
            }
            
            return '/' . implode( '/', $segments );
        }
        
        public function isEqual( $path1, $path2 ) {
            
            $a = $this->decode( $path1 );
            $b = $this->decode( $path2 );
            
            return ( $a === FALSE || $b === FALSE )
                ? NULL
                : $a === $b;
            
            return $this->decode( $path1 ) == $this->decode( $path2 );
            
        }
        
        public function isCommonParent( $path1, $path2 ) {
            
            $a = $this->decode( $path1 );
            $b = $this->decode( $path2 );
            
            if ( $a === NULL || $b === NULL )
                return NULL;
            else {
                
                $a = $this->substract( $a, 1 );
                $b = $this->substract( $b, 1 );
                
                if ( $a === FALSE || $b === FALSE )
                    return NULL;
                else
                    return $a === $b;
                
            }
            
            return $this->substract( $this->decode( $path1 ), 1 ) == $this->substract( $this->decode( $path2 ), 1 );
            
        }
        
        // weather a path is a direct parent of another path or not
        // @return NULL, TRUE, or FALSE
        //         NULL is returned when one of the paths is invalid
        public function isParentOf( $father, $child ) {
            
            $child = $this->substract( $child, 1 );
            
            if ( $child === FALSE )
                return FALSE;
            
            $rf = $this->decode( $father );
            $rc = $this->decode( $child );
            
            return ( $rf === NULL || $rc === NULL )
                ? NULL
                : $rf === $rc;
            
        }
    }
    
?>