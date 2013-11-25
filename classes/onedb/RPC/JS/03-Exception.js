/* Exception types
 */

( function() {
    
    var ETypes = {};
    
    function RegisterException( type ) {
        
        var f = function( message, code, previous, file, line ) {
            
            message = message || 'unknown';
            code = code || 0;
            previous = previous || null;
            file = file || 'unknown';
            line = line || 0;
            
            this.getPrevious = function() {
                return previous;
            }
            
            this.getCode = function() {
                return code;
            }
            
            this.getMessage = function() {
                return message;
            }
            
            this.getFile = function() {
                return file;
            }
            
            this.getLine = function() {
                return line;
            }
            
            this.toString = function() {
                return type + ': ' + this.getMessage() + ' [ ' + ( code ? 'code ' + this.getCode() + ',' : '' ) + ' in file: "' + this.getFile() + '" at line ' + this.getLine() + ' ]';
            }
            
            return this;
        }
        
        ETypes[ type ] = f;
    }
    
    function __exception__ ( type, message, code, previous, file, line ) {
        
        if ( !( ETypes[ type ] ) )
            RegisterException( type );
        
        return new ETypes[ type ]( message, code, previous, file, line );
        
    }
    
    Object.defineProperty( window, "Exception", {
        "get": function() {
            return __exception__;
        }
    } );
    
} )();