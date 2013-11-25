/* Unix inspired file mode mask tools
 */

( function() {
    
    // Defines a constant into an object
    function _const ( obj, name, value ) {
        Object.defineProperty( obj, name, {
            "get": function() {
                return value;
            }
        });
    }
    
    var Umask = function() {
        
        _const( this, 'AC_NOBODY',    512 );
        _const( this, 'AC_READONLY',  256 );
        _const( this, 'AC_REGULAR',   128 );
        
        _const( this, 'MASK_VERBOSE',   1 );
        _const( this, 'MASK_OCTAL',     2 );
        
        _const( this, 'ST',           512 ); // 1000000000 // sticky bit
        _const( this, 'UR',           256 ); // 0100000000 // user can read
        _const( this, 'UW',           128 ); // 0010000000 // user can write
        _const( this, 'UX',            64 ); // 0001000000 // user can execute
        _const( this, 'GR',            32 ); // 0000100000 // group can read
        _const( this, 'GW',            16 ); // 0000010000 // group can write
        _const( this, 'GX',             8 ); // 0000001000 // group can execute
        _const( this, 'AR',             4 ); // 0000000100 // anyone can read
        _const( this, 'AW',             2 ); // 0000000010 // anyone can write
        _const( this, 'AX',             1 ); // 0000000001 // anyone can execute
        _const( this, 'NUL',            0 ); // 0000000000 // no flag
        
        _const( this, 'MAX_UMASK',   1023 ); // 1111111111 // max possible umask
        
        _const( this, 'M777', this.UR + this.UW + this.UX +
                              this.GR + this.GW + this.GX +
                              this.AR + this.AW + this.AX
        );
        
        // converts a file mode ( mask ) to a human representation
        // string
        
        // @param umask  <integer>
        // @param mode   <integer> enum ( Umask::MASK_VERBOSE, Umask::MASK_OCTAL )
        // @param _throw <boolean> throw exception instead of return null
        this.mode_to_str = function( umask, mode, _throw ) {
            
            _throw = typeof _throw == 'undefined' ? true : !!_throw;
            mode   = Strict.is_int( mode ) ? mode : ( typeof mode == 'undefined' && arguments.length == 1 ? 1 : -1 );
            
            if ( !Strict.is_int( umask ) || umask < 0 || umask > this.MAX_UMASK ) {
                if ( _throw ) throw Exception('Exception.FS', 'umask must be int gte ' + this.MAX_UMASK, 0, null, __FILE__, __LINE__  );
                else return false;
            }
            
            if ( mode != this.MASK_VERBOSE && mode != this.MASK_OCTAL ) {
                if ( _throw ) throw Exception('Exception.FS', 'mode must be MASK_OCTAL | MASK_VERBOSE', 0, null, __FILE__, __LINE__  );
                else return false;
            }
            
            switch ( mode ) {

                case this.MASK_VERBOSE:
                    
                    var out = ['-','-','-','-','-','-','-','-','-','-'];

                    if ( umask & this.UR ) out[0] = 'r';
                    if ( umask & this.UW ) out[1] = 'w';
                    if ( umask & this.UX ) out[2] = 'x';
                    if ( umask & this.GR ) out[3] = 'r';
                    if ( umask & this.GW ) out[4] = 'w';
                    if ( umask & this.GX ) out[5] = 'x';
                    if ( umask & this.AR ) out[6] = 'r';
                    if ( umask & this.AW ) out[7] = 'w';
                    if ( umask & this.AX ) out[8] = 'x';
                    if ( umask & this.ST ) out[9] = 't';

                    return out.join('');

                    break;

                case this.MASK_OCTAL:

                    var out = umask.toString(8);
                    
                    while ( out.length < 3 )
                        out = '0' + out;
                    
                    return out;

                    break;
            }
        }; // end of this.mode_to_str
        
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
                
                case /^[r|\-][w|\-][x|\-][r|\-][w|\-][x|\-][r|\-][w|\-][x|\-]([t|\-])?/.test( str ):
                    if ( str[0] == 'r' ) out ^= this.UR;
                    if ( str[1] == 'w' ) out ^= this.UW;
                    if ( str[2] == 'x' ) out ^= this.UX;
            
                    if ( str[3] == 'r' ) out ^= this.GR;
                    if ( str[4] == 'w' ) out ^= this.GW;
                    if ( str[5] == 'x' ) out ^= this.GX;
            
                    if ( str[6] == 'r' ) out ^= this.AR;
                    if ( str[7] == 'w' ) out ^= this.AW;
                    if ( str[8] == 'x' ) out ^= this.AX;
            
                    if ( str.length == 10 && str[9] == 't' ) out ^= this.ST;
                    
                    return out;
                    
                    break;
                
                case ( matches = /^([0-1])?([0-7])([0-7])([0-7])$/.exec( str ) ) ? true : false:
                    
                    return parseInt( str, 8 );
                    
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
                
                case Strict.is_string( flag ):
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
                                if ( _throw ) throw Exception( 'Exception.FS', 'failed to decode octal or verbose string flag', 0, null, __FILE__, __LINE__ );
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
                                            if ( _throw ) throw Exception( 'Exception.FS', 'failed to decode octal or verbose string flag', 0, null, __FILE__, __LINE__ );
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
                                if ( _throw ) throw Exception( 'Exception.FS', 'invalid string flag representation', 0, null, __FILE__, __LINE__  );
                                else return false;
                                break;
                        }
                        
                    }
                    
                    return 1;
                    
                    break;
                
            }
        }
        
    } // end of function Umask
    
    var u = new Umask();
    
    Object.defineProperty( window, "Umask", {
        "get": function() { return u; }
    });
    
} )();
