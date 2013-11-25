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
        
        this.str_to_mode = function( str, _throw ) {
            
            _throw = typeof _throw == 'undefined' ? true : !!_throw;
            
            var out = this.NUL,
                matches;
            
            switch ( true ) {
                case !Strict.is_string( str ):
                    
                    if ( _throw ) throw Exception( 'Exception.FS', 'input mode must be string!', 0, null, __FILE__, __LINE__  );
                    else return false;
                    
                    break;
                
                case !/^[r|\-][w|\-][x|\-][r|\-][w|\-][x|\-][r|\-][w|\-][x|\-]([r|\-])?/.test( str ):
                    if ( str[0] == 'r' ) out ^= this.UR;
                    if ( str[1] == 'w' ) out ^= this.UW;
                    if ( str[2] == 'x' ) out ^= this.UX;
            
                    if ( str[3] == 'r' ) out ^= this.GR;
                    if ( str[4] == 'w' ) out ^= this.GW;
                    if ( str[5] == 'x' ) out ^= this.GX;
            
                    if ( str[6] == 'r' ) out ^= this.AR;
                    if ( str[7] == 'w' ) out ^= this.AW;
                    if ( str[8] == 'x' ) out ^= this.AX;
            
                    if ( str.length == 10 && out[9] == 't' ) out ^= this.ST;
                    
                    return out;
                    
                    break;
                
                case ( matches = /^([0-1])?([0-7])([0-7])([0-7])$/.exec( str ) ) ? true : false:
                    
                    if ( matches[1] == '1' ) out |= this.ST;
                    
                    switch ( ~~matches[2] ) {
                        case 1: out ^= this.UX;
                            break;
                        case 2: out ^= this.UW;
                            break;
                        case 3: out ^= this.UW; out ^= this.UX;
                            break;
                        case 4: out ^= this.UR;
                            break;
                        case 5: out ^= this.UR; out ^= this.UX;
                            break;
                        case 6: out ^= this.UR; out ^= this.UW;
                            break;
                        case 7: out ^= this.UR; out ^= this.UW; out ^= this.UX;
                            break;
                    }

                    switch ( ~~matches[3] ) {
                        case 1: out ^= this.GX;
                            break;
                        case 2: out ^= this.GW;
                            break;
                        case 3: out ^= this.GW; out ^= this.GX;
                            break;
                        case 4: out ^= this.GR;
                            break;
                        case 5: out ^= this.GR; out ^= this.GX;
                            break;
                        case 6: out ^= this.GR; out ^= this.GW;
                            break;
                        case 7: out ^= this.GR; out ^= this.GW; out ^= this.GX;
                            break;
                    }

                    switch ( ~~matches[4] ) {
                        case 1: out ^= this.AX;
                            break;
                        case 2: out ^= this.AW;
                            break;
                        case 3: out ^= this.AW; out ^= this.AX;
                            break;
                        case 4: out ^= this.AR;
                            break;
                        case 5: out ^= this.AR; out ^= this.AX;
                            break;
                        case 6: out ^= this.AR; out ^= this.AW;
                            break;
                        case 7: out ^= this.AR; out ^= this.AW; out ^= this.AX;
                            break;
                    }
                    
                    return out;
                    
                    break;
                
                default:
                    if ( _throw ) throw Exception( 'Exception.FS', 'invalid mode format!', 0, null, __FILE__, __LINE__  );
                    else return false;
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
        
        this.test = function( bitmask, flag, _throw ) {
            
            _throw = typeof _throw == 'undefined' ? true : !!_throw;
            
            if ( !Strict.is_int( bitmask ) || bitmask < 0 ) {
                if ( _throw ) throw Exception( 'Exception.FS', "invalid bitmask. expected int gte 0", 0, null, __FILE__, __LINE__  );
                else return false;
            }
            
            var flags = {
                'ur' : this.UR,
                'uw' : this.UW,
                'ux' : this.UX,
                'gr' : this.GR,
                'gw' : this.GW,
                'gx' : this.GX,
                'ar' : this.AR,
                'aw' : this.AW,
                'ax' : this.AX,
                'st' : this.ST,
                'nul': this.NUL
            };
            
            switch ( true ) {
                
                case String.is_string( flag ):
                    // test single flag of type string
                    
                    switch ( true ) {
                        
                        // ur, uw, ux, etc:
                        case Strict.isset( flags[ flag ] ):
                            return ( ( bitmask & flags[flag] ) === flags[ flag ] ) ? 1 : 0;
                            break;
                        
                        // string flag:
                        case /^([0-1])?[0-7][0-7][0-7]$/.test( flag ):
                        case /^[r|\-][w|\-][x|\-][r|\-][w|\-][x|\-][r|\-][w|\-][x|\-](t|\-)?$/.test( flag ):
                        
                            var bitmask2 = this.str_to_mode( flag, false );
                            
                            //echo "bm2: $bitmask2\n";

                            if ( bitmask2 === false ) {
                                if ( _throw ) throw Exception( 'Exception.FS', 'failed to decode octal or verbose string flag', 0, null, __FILE__, __LINE__  );
                                else return false;
                            }
                            
                            //echo "$bitmask & $bitmask2 = ", ( $bitmask & $bitmask2 ), "\n";
                            
                            return ( ( bitmask & bitmask2 ) === bitmask2 ) ? 1 : 0;
                            
                            break;
                        
                        default:
                            if ( _throw ) throw Exception( 'Exception.FS', 'invalid string flag representation', 0, null, __FILE__, __LINE__  );
                            else return false;
                            break;
                        
                    }
                    break;
                
                // test integer flag
                case Strict.is_int( flag ):
                    
                    return ( ( bitmask & flag ) === flag ) ? 1 : 0;
                    
                    break;
                
                case Strict.is_array( flag ):
                    // test multiple flags
                    
                    var i;
                    
                    for ( var jj = 0, len = flag.length; jj<len; jj++ ) {
                        
                        i = flag[jj];
                    
                        switch ( true ) {
                            
                            // test integer flag:
                            case Strict.is_int( i ):
                                
                                if ( ( ( bitmask & i ) !== i ) ) return 0;

                                break;
                            
                            // test string flag:
                            case Strict.is_string( i ):
                                


                                switch ( true ) {
                                    // ur, uw, ux, etc:
                                    case Strict.isset( flags[ i ] ):
                                        if ( ( ( bitmask & flags[i] ) !== flags[i] ) ) return 0;
                                        break;
                        
                                    // string flag:
                                    case /^([0-1])?[0-7][0-7][0-7]$/.test( flag ):
                                    case /^[r|\-][w|\-][x|\-][r|\-][w|\-][x|\-][r|\-][w|\-][x|\-](t|\-)?$/.test( flag ):
                        
                                        var bitmask2 = this.str_to_mode( i, false );

                                        if ( bitmask2 === false ) {
                                            if ( _throw ) throw Exception( 'Exception.FS', 'failed to decode octal or verbose string flag', , 0, null, __FILE__, __LINE__ );
                                            else return false;
                                        }
                            
                                        if ( ( ( bitmask & bitmask2 ) !== bitmask2 ) ) return 0;
                            
                                        break;
                        
                                    default:
                                        if ( _throw ) throw Exception( 'Exception.FS', 'invalid string flag representation', 0, null, __FILE__, __LINE__ );
                                        else return false;
                                        break;
                                }
                                
                                break;
                            
                            default:
                                if ( _throw ) throw Exception( 'Exception.FS', 'invalid string flag representation', 0, null, __FILE__, __LINE__ );
                                else return false;
                                break;
                        }
                        
                    }
                    
                    return 1;
                    
                    break;
                
            }
        }