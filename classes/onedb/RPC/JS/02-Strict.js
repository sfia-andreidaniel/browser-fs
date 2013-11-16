( function() {
    
    function Strict() {
        
        this.is_int = function( val ) {
            return typeof val == 'number' && !isNaN( val ) && parseInt( val ) == val;
        }
        
        this.is_boolean = function( val ) {
            return typeof val == 'boolean';
        }
        
        this.is_bool = this.is_boolean;
        
        this.is_array = function( val ) {
            return val instanceof window.Array && typeof val.prototype == 'undefined' && typeof val.length != 'undefined' && typeof val.slice == 'function';
        }
        
        this.is_callable = function( val ) {
            return typeof val == 'function';
        }
        
        this.is_instance = function( val ) {
            return typeof val == 'object' && val.constructor && /^function /.test( String( val.constructor ) );
        }
        
        this.is_string = function( val ) {
            return typeof val == 'string';
        }
        
        this.is_float = function( val ) {
            return typeof val == 'number' && !isNaN( val ) && parseFloat( val ) == val;
        }
        
        this.isset = function( val ) {
            return typeof val != 'undefined';
        }
    }
    
    var s = new Strict();
    
    Object.defineProperty( window, "Strict", {
        "get": function() {
            return s;
        }
    });
    
} )();