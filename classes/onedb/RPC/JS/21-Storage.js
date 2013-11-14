function OneDB_Storage( client, storageTypeName ) {
    
    this.__class = "OneDB_Storage";
    
    this.init = function() {
        
        var localClient = client,
            storageName = storageTypeName;
        
        Object.defineProperty( this, "server", {
            "get": function() {
                return localClient;
            }
        });
        
        Object.defineProperty( this, "name", {
            "get": function() {
                return storageName;
            }
        } );

    }
    
    this.unlinkFile = function( fileId ) {
        return OneDB.runEndpointMethod( this.server, "storage.unlinkFile", [ fileId ] );
    };
    
    this.__create();
    
    return this;
}

OneDB_Storage.prototype = new OneDB_Class();

OneDB_Storage.prototype.__demux = function( muxedData ) {
    throw "OneDB_Storage.__demux() not implemented";
}

OneDB_Storage.prototype.__mux = function() {
    throw "OneDB_Storage.__mux() not implemented";
}

