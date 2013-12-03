function OneDB_Router( url, client ) {
    
    this.__class = "OneDB_Router";
    
    this.init = function() {
        
        var localClient = client,
            localUrl    = url;
        
        Object.defineProperty( this, "url", {
            "get": function() {
                return localUrl;
            }
        });
        
        Object.defineProperty( this, "client", {
            "get": function() {
                return localClient;
            }
        } );

    }
    
    this.addServerMethod( "run", [
        {
            "name": "additionalParams",
            "type": "nullable mixed",
            "default": null
        }
    ] );
    
    /* this.unlinkFile = function( fileId ) {
        return OneDB.runEndpointMethod( this.server, "storage.unlinkFile", [ fileId ] );
    }; */
    
    this.__create();
    
    return this;
}

OneDB_Router.prototype = new OneDB_Class();

OneDB_Router.prototype.__demux = function( muxedData ) {
    return new OneDB_Router( muxedData[0], OneDB_Client.prototype.__demux( muxedData[1] ) );
}

OneDB_Router.prototype.__mux = function() {
    return this.client.__mux() + "\t" + this.url;
};