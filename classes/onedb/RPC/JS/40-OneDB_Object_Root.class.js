function OneDB_Object_Root( server ) {
    
    this.__class = 'OneDB_Object_Root';
    
    this.init = function() {
        
        Object.defineProperty( this, "_server", {
            "get": function() {
                return server;
            }
        } );
        
    }
    
    this.__create();
    
    return this;
    
}

OneDB_Object_Root.prototype = new OneDB_Class();
OneDB_Object_Root.prototype.__singletons = {};
OneDB_Object_Root.prototype.__demux = function( muxedData ) {
    
    if ( this.__singletons[ muxedData ] )
        return this.__singletons[ muxedData ];
    
    else
        return this.__singletons[ muxedData ] = new OneDB_Object_Root(
            OneDB_Client.prototype.__demux( muxedData )
        );

}