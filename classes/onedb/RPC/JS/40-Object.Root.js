// This class implements the Root Object for each onedb database.
function OneDB_Object_Root( server ) {
    
    // class name
    this.__class = 'OneDB_Object_Root';
    
    // constructor
    this.init = function() {
        
        Object.defineProperty( this, "_server", {
            "get": function() {
                return server;
            }
        } );
        
        Object.defineProperty( this, "id", {
            "get": function() {
                return null;
            }
        } );
        
        Object.defineProperty( this, "name", {
            "get": function() {
                return '/';
            }
        } );
        
        Object.defineProperty( this, "type", {
            "get": function() {
                return 'Root';
            }
        } );
        
        Object.defineProperty( this, "parent", {
            "get": function() {
                return null;
            }
        } );
        
        Object.defineProperty( this, "created", {
            "get": function() {
                return 0;
            }
        });
        
        Object.defineProperty( this, "modified", {
            "get": function() {
                return 0;
            }
        });
        
        Object.defineProperty( this, "owner", {
            "get": function() {
                return 'system';
            }
        });
        
        Object.defineProperty( this, "modifier", {
            "get": function() {
                return 'system';
            }
        });
        
        Object.defineProperty( this, "description", {
            "get": function() {
                return 'This is the uppermost node in the database tree';
            }
        });
        
        Object.defineProperty( this, 'icon', {
            "get": function() {
                return null;
            }
        });
        
        Object.defineProperty( this, "keywords", {
            'get': function() {
                return [];
            }
        });
        
        Object.defineProperty( this, "tags", {
            "get": function() {
                return [];
            }
        });
        
        Object.defineProperty( this, "online", {
            "get": function() {
                return true;
            }
        });
        
        Object.defineProperty( this, "url", {
            "get": function() {
                return '/';
            }
        });
        
        this.addServerMethod( 'find', [
            {
                "name": "query",
                "type": "window.Object",
                "default": {}
            },
            {
                "name": "limit",
                "type": "nullable integer",
                "default": null
            },
            {
                "name": "orderBy",
                "type": "nullable window.Object",
                "default": {}
            }
        ] );
        
    }
    
    // initialize the class
    this.__create();
    
    return this;
    
}

// This class inherits the OneDB_Class
OneDB_Object_Root.prototype = new OneDB_Class();

// Each onedb client has a single Root. So we're singletoning them
OneDB_Object_Root.prototype.__singletons = {};

// Demuxer method.
OneDB_Object_Root.prototype.__demux = function( muxedData ) {

    return  this.__singletons[ muxedData ]
        ?   this.__singletons[ muxedData ]
        : ( this.__singletons[ muxedData ] = new OneDB_Object_Root( OneDB_Client.prototype.__demux( muxedData ) ) );

}

// Muxer method
OneDB_Object_Root.prototype.__mux = function() {
    return this._server.__mux();
}

