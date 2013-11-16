<?php

    /* Unix file inspired permissions umask functions.
     */
    
    class Sys_Umask extends Object {
        
        // BITMASK REPRESENTING ACCOUNT TYPES FLAGS
        const AC_NOBODY         = 512; // 1000000000 // regular anonymous account flag
        const AC_SUPERUSER      = 256; // 0100000000 // superuser account flag
        const AC_REGULAR        = 128; // 0010000000 // regular account flag
        
        // UMASK representation modes
        const MASK_VERBOSE      = 1;
        const MASK_OCTAL        = 2;
        
        // BITMASK REPRESENTING MODE FLAGS
        const UR                = 512; // 1000000000 // user can read
        const UW                = 256; // 0100000000 // user can write
        const UX                = 128; // 0010000000 // user can execute
        const GR                =  64; // 0001000000 // group can read
        const GW                =  32; // 0000100000 // group can write
        const GX                =  16; // 0000010000 // group can execute
        const AR                =   8; // 0000001000 // anyone can read
        const AW                =   4; // 0000000100 // anyone can write
        const AX                =   2; // 0000000010 // anyone can execute
        const ST                =   1; // 0000000001 // sticky bit
        const NUL               =   0; // 0000000000 // no flag
        
        // PROVIDES:
        // 
        // static public function mode_to_str ( $umask, $mode = 1; $_throw = TRUE )
        // static public function str_to_mode ( $str, $_throw = TRUE )
        // static public function test        ( $mask, $flag, $_throw = TRUE )

        
        // BEGIN IMPLEMENTATION
        
        
        
        /* Converts a file mode to a human representation
           string
           
           @umask  : <integer>
           @mode   : <integer> enum ( MASK_VERBOSE, MASK_OCTAL )
           @_throw : <boolean> throw exception if wrong input.
           
           RETURN  :
                FALSE    on error
                CHAR[10] on success
           
         */
        
        static public function mode_to_str( $umask, $mode = 1, $_throw = TRUE ) {
            
            if ( !is_int( $umask ) || $umask < 0 ) {
                if ( $_throw ) throw Object('Exception.FS', 'umask must be int gte 0' );
                else return FALSE;
            }
            
            if ( !is_int( $mode ) || !in_array( $mode, [ self::MASK_VERBOSE, self::MASK_OCTAL ] ) ) {
                if ( $_throw ) throw Object('Exception.FS', 'mode must be MASK_OCTAL | MASK_VERBOSE' );
                else return FALSE;
            }
            
            switch ( $mode ) {
                case self::MASK_VERBOSE:
            
                    $out = '----------';
                
                    if ( $umask & self::UR ) $out[0] = 'r';
                    if ( $umask & self::UW ) $out[1] = 'w';
                    if ( $umask & self::UX ) $out[2] = 'x';

                    if ( $umask & self::GR ) $out[3] = 'r';
                    if ( $umask & self::GW ) $out[4] = 'w';
                    if ( $umask & self::GX ) $out[5] = 'x';

                    if ( $umask & self::AR ) $out[6] = 'r';
                    if ( $umask & self::AW ) $out[7] = 'w';
                    if ( $umask & self::AX ) $out[8] = 'x';

                    if ( $umask & self::ST ) $out[9] = 't';
                    
                    return $out;
                    
                    break;
                
                case self::MASK_OCTAL:
                    
                    $out = '';
                    
                    if ( $umask & self::ST ) $out .= '1'; // else $out .= '0';
                    
                    switch ( TRUE ) {
                        
                        case ( ( $umask & self::UR ) && ( $umask & self::UW ) && ( $umask & self::UX ) ) ? TRUE : FALSE:
                            $out .= '7'; break;
                        case ( ( $umask & self::UR ) && ( $umask & self::UW ) ) ? TRUE : FALSE:
                            $out .= '6'; break;
                        case ( ( $umask & self::UR ) && ( $umask & self::UX ) ) ? TRUE : FALSE:
                            $out .= '5'; break;
                        case ( ( $umask & self::UW ) && ( $umask & self::UX ) ) ? TRUE : FALSE:
                            $out .= '3'; break;
                        case ( $umask & self::UR ) ? TRUE : FALSE:
                            $out .= '4'; break;
                        case ( $umask & self::UW ) ? TRUE : FALSE:
                            $out .= '2'; break;
                        case ( $umask & self::UX ) ? TRUE : FALSE:
                            $out .= '1'; break;
                        default:
                            $out .= '0'; break;
                    }

                    switch ( TRUE ) {
                        
                        case ( ( $umask & self::GR ) && ( $umask & self::GW ) && ( $umask & self::GX ) ) ? TRUE : FALSE:
                            $out .= '7'; break;
                        case ( ( $umask & self::GR ) && ( $umask & self::GW ) ) ? TRUE : FALSE:
                            $out .= '6'; break;
                        case ( ( $umask & self::GR ) && ( $umask & self::GX ) ) ? TRUE : FALSE:
                            $out .= '5'; break;
                        case ( ( $umask & self::GW ) && ( $umask & self::GX ) ) ? TRUE : FALSE:
                            $out .= '3'; break;
                        case ( $umask & self::GR ) ? TRUE : FALSE:
                            $out .= '4'; break;
                        case ( $umask & self::GW ) ? TRUE : FALSE:
                            $out .= '2'; break;
                        case ( $umask & self::GX ) ? TRUE : FALSE:
                            $out .= '1'; break;
                        default:
                            $out .= '0'; break;
                    }

                    switch ( TRUE ) {
                        
                        case ( ( $umask & self::AR ) && ( $umask & self::AW ) && ( $umask & self::AX ) ) ? TRUE : FALSE:
                            $out .= '7'; break;
                        case ( ( $umask & self::AR ) && ( $umask & self::AW ) ) ? TRUE : FALSE:
                            $out .= '6'; break;
                        case ( ( $umask & self::AR ) && ( $umask & self::AX ) ) ? TRUE : FALSE:
                            $out .= '5'; break;
                        case ( ( $umask & self::AW ) && ( $umask & self::AX ) ) ? TRUE : FALSE:
                            $out .= '3'; break;
                        case ( $umask & self::AR ) ? TRUE : FALSE:
                            $out .= '4'; break;
                        case ( $umask & self::AW ) ? TRUE : FALSE:
                            $out .= '2'; break;
                        case ( $umask & self::AX ) ? TRUE : FALSE:
                            $out .= '1'; break;
                        default:
                            $out .= '0'; break;
                    }
                    
                    return $out;
                    
                    break;
            }
            
        }
        
        /* Converts a mask from it's string representation to it's integer
           representation.
           
           @param: str <string>, in formats:
                        CHAR[10]   LIKE rwxrwxrwxt
                        CHAR[3..4] LIKE 1755, 755, 0750
           @param: _throw <boolean>
                   if true, exception will be thrown on bad input
           
           RETURN:
                FALSE on error
                INT   on success
         */
        
        static public function str_to_mode( $str, $_throw = TRUE ) {
            
            $out = self::NUL;
            
            switch ( TRUE ) {
                case !is_string( $str ):
                    
                    if ( $_throw ) throw Object( 'Exception.FS', 'input mode must be string!' );
                    else return FALSE;
                    
                    break;
                
                case preg_match('/^[r|\-][w|\-][x|\-][r|\-][w|\-][x|\-][r|\-][w|\-][x|\-]([r|\-])?/', $str ) ? TRUE : FALSE:
                    if ( $str[0] == 'r' ) $out ^= self::UR;
                    if ( $str[1] == 'w' ) $out ^= self::UW;
                    if ( $str[2] == 'x' ) $out ^= self::UX;
            
                    if ( $str[3] == 'r' ) $out ^= self::GR;
                    if ( $str[4] == 'w' ) $out ^= self::GW;
                    if ( $str[5] == 'x' ) $out ^= self::GX;
            
                    if ( $str[6] == 'r' ) $out ^= self::AR;
                    if ( $str[7] == 'w' ) $out ^= self::AW;
                    if ( $str[8] == 'x' ) $out ^= self::AX;
            
                    if ( strlen( $str ) == 10 && $out[9] == 't' ) $out ^= self::ST;
                    
                    return $out;
                    
                    break;
                
                case preg_match('/^([0-1])?([0-7])([0-7])([0-7])$/', $str, $matches ) ? TRUE : FALSE:
                    
                    if ( $matches[1] == '1' ) $out |= self::ST;
                    
                    switch ( ~~$matches[2] ) {
                        case 1: $out ^= self::UX;
                            break;
                        case 2: $out ^= self::UW;
                            break;
                        case 3: $out ^= self::UW; $out ^= self::UX;
                            break;
                        case 4: $out ^= self::UR;
                            break;
                        case 5: $out ^= self::UR; $out ^= self::UX;
                            break;
                        case 6: $out ^= self::UR; $out ^= self::UW;
                            break;
                        case 7: $out ^= self::UR; $out ^= self::UW; $out ^= self::UX;
                            break;
                    }

                    switch ( ~~$matches[3] ) {
                        case 1: $out ^= self::GX;
                            break;
                        case 2: $out ^= self::GW;
                            break;
                        case 3: $out ^= self::GW; $out ^= self::GX;
                            break;
                        case 4: $out ^= self::GR;
                            break;
                        case 5: $out ^= self::GR; $out ^= self::GX;
                            break;
                        case 6: $out ^= self::GR; $out ^= self::GW;
                            break;
                        case 7: $out ^= self::GR; $out ^= self::GW; $out ^= self::GX;
                            break;
                    }

                    switch ( ~~$matches[4] ) {
                        case 1: $out ^= self::AX;
                            break;
                        case 2: $out ^= self::AW;
                            break;
                        case 3: $out ^= self::AW; $out ^= self::AX;
                            break;
                        case 4: $out ^= self::AR;
                            break;
                        case 5: $out ^= self::AR; $out ^= self::AX;
                            break;
                        case 6: $out ^= self::AR; $out ^= self::AW;
                            break;
                        case 7: $out ^= self::AR; $out ^= self::AW; $out ^= self::AX;
                            break;
                    }
                    
                    return $out;
                    
                    break;
                
                default:
                    if ( $_throw ) throw Object( 'Exception.FS', 'invalid mode format!' );
                    else return FALSE;
                    break;
            }
        }
        
        /* Test if a $bitmask satisfies a $flag
         *
         * @bitmask: <int>
         * @flag   : <string> enum( 'ur', 'uw', 'ux', 'gr', 'gw', 'gx', 'ar', 'aw', 'ax', 'st', 'nul' )
         *           OR
         *           <array> [ 'ar', 'uw', 'ux', ... ]
         *           OR
         *           <int>    enum( self::UR, self::UW, self::UX, self::GR, self::GW, self::GX, self::AR, self::AW, self::AX, self::ST, self::NUL )
         *           OR
         *           <string> '0667', '777', etc
         *           OR
         *           <string> 'rwxrwxrwxt'
         *
         * RETURN: 1 OR 0 on success, or FALSE on failure
         * EXAMPLES:
         *    Sys_Umask::test( Sys_Umask::UR ^ Sys_Umask::UW, 'rwx-------' );   // 1
         *    Sys_Umask::test( Sys_Umask::UR ^ Sys_Umask::UW, [ 'ur', 'uw' ] ); // 1
         *    Sys_Umask::test( Sys_Umask::UR ^ Sys_Umask::UW, '600' );          // 1
         *
         *
         *
         *
         */
        
        static public function test( $bitmask, $flag, $_throw = TRUE ) {
            
            if ( !is_int( $bitmask ) || $bitmask < 0 ) {
                if ( $_throw ) throw Object( 'Exception.FS', "invalid bitmask. expected int gte 0" );
                else return FALSE;
            }
            
            $flags = [
                'ur' => self::UR,
                'uw' => self::UW,
                'ux' => self::UX,
                'gr' => self::GR,
                'gw' => self::GW,
                'gx' => self::GX,
                'ar' => self::AR,
                'aw' => self::AW,
                'ax' => self::AX,
                'st' => self::ST,
                'nul'=> self::NUL
            ];
            
            switch ( TRUE ) {
                
                case is_string( $flag ):
                    // test single flag of type string
                    
                    switch ( TRUE ) {
                        
                        // ur, uw, ux, etc:
                        case isset( $flags[ $flag ] ):
                            return ( ( $bitmask & $flags[$flag] ) === $flags[ $flag ] ) ? 1 : 0;
                            break;
                        
                        // string flag:
                        case preg_match( '/^([0-1])?[0-7][0-7][0-7]$/', $flag ):
                        case preg_match( '/^[r|\-][w|\-][x|\-][r|\-][w|\-][x|\-][r|\-][w|\-][x|\-](t|\-)?$/', $flag ):
                        
                            $bitmask2 = self::str_to_mode( $flag, FALSE );
                            
                            //echo "bm2: $bitmask2\n";

                            if ( $bitmask2 === FALSE ) {
                                if ( $_throw ) throw Object( 'Exception.FS', 'failed to decode octal or verbose string flag' );
                                else return FALSE;
                            }
                            
                            //echo "$bitmask & $bitmask2 = ", ( $bitmask & $bitmask2 ), "\n";
                            
                            return ( ( $bitmask & $bitmask2 ) === $bitmask2 ) ? 1 : 0;
                            
                            break;
                        
                        default:
                            if ( $_throw ) throw Object( 'Exception.FS', 'invalid string flag representation' );
                            else return FALSE;
                            break;
                        
                    }
                    break;
                
                // test integer flag
                case is_int( $flag ):
                    
                    return ( ( $bitmask & $flag ) === $flag ) ? 1 : 0;
                    
                    break;
                
                case is_array( $flag ):
                    // test multiple flags
                    
                    foreach ( $flag as $i ) {
                        
                        switch ( TRUE ) {
                            
                            // test integer flag:
                            case is_int( $i ):
                                
                                if ( ( ( $bitmask & $i ) !== $i ) ) return 0;

                                break;
                            
                            // test string flag:
                            case is_string( $i ):
                                


                                switch ( TRUE ) {
                                    // ur, uw, ux, etc:
                                    case isset( $flags[ $i ] ):
                                        if ( ( ( $bitmask & $flags[$i] ) !== $flags[$i] ) ) return 0;
                                        break;
                        
                                    // string flag:
                                    case preg_match( '/^([0-1])?[0-7][0-7][0-7]$/', $flag ):
                                    case preg_match( '/^[r|\-][w|\-][x|\-][r|\-][w|\-][x|\-][r|\-][w|\-][x|\-](t|\-)?$/', $flag ):
                        
                                        $bitmask2 = self::str_to_mode( $i, FALSE );

                                        if ( $bitmask2 === FALSE ) {
                                            if ( $_throw ) throw Object( 'Exception.FS', 'failed to decode octal or verbose string flag' );
                                            else return FALSE;
                                        }
                            
                                        if ( ( ( $bitmask & $bitmask2 ) !== $bitmask2 ) ) return 0;
                            
                                        break;
                        
                                    default:
                                        if ( $_throw ) throw Object( 'Exception.FS', 'invalid string flag representation' );
                                        else return FALSE;
                                        break;
                                }
                                
                                break;
                            
                            default:
                                if ( $_throw ) throw Object( 'Exception.FS', 'invalid string flag representation' );
                                else return FALSE;
                                break;
                        }
                        
                    }
                    
                    return 1;
                    
                    break;
                
            }
        }
    }
    
    // A class alias, cause we're going to use a lot this class
    class Umask extends Sys_Umask {}
    
    /*
    $test = '770';
    $mask = Sys_Umask::UR ^ Sys_Umask::UW ^ Sys_Umask::UX ^ Sys_Umask::GX;
    
    echo Sys_Umask::mode_to_str( $mask ), "\n";
    echo Sys_Umask::mode_to_str( Sys_Umask::str_to_mode( $test ) ), "\n";
    
    echo Sys_Umask::test( $mask, [ 'ux', 'gx' ] ), "\n";          // 1
    */

?>