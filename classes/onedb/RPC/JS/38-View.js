// This class implements the View of a Object.
// This class is instantiated only by a OneDB_Object_Views class, and
// is not intended to be instantiated manually.

// @param object: <OneDB_Object.Widget>

function OneDB_Object_View( object ) {
    
    // class name
    this.__class = 'OneDB_Object_View';
    
    var argument = null;
    
    // @param: arg: <OneDB_Object>
    this._setArgument_ = function( arg ) {
        argument = arg;
        return this;
    };
    
    this.run = function() {
        return this._object_.data.run( { "argument": argument } );
    };
    
    // constructor
    this.init = function() {
        
        Object.defineProperty( this, "_object_", {
            "get": function() {
                return object;
            }
        } );
        
    }
    
    // initialize the class
    this.__create();
    
    return this;
    
}

// This class inherits the OneDB_Class
OneDB_Object_View.prototype = new OneDB_Class();

OneDB_Object_View.prototype.__demux = function ( muxedData ) {
    return new OneDB_Object_View( OneDB_Object.prototype.__demux( muxedData ) );
}