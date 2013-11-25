// This class implements the Root Object for each onedb database.
function OneDB_Object_Root( server ) {
    
    // class name
    this.__class = 'OneDB_Object_Root';
    
    // constructor
    this.init = function() {
        
        Object.defineProperty( this, "_server", {
            "get": function() {
                return server;
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', 'the "_server" property of a OneDB_Object_Root is read-only' );
            }
        } );
        
        Object.defineProperty( this, "id", {
            "get": function() {
                return null;
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'the "id" property of a OneDB_Object_Root is read-only' );
            }
        } );
        
        Object.defineProperty( this, "name", {
            "get": function() {
                return '/';
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'the "name" property of a OneDB_Object_Root is read-only' );
            }
        } );
        
        Object.defineProperty( this, "type", {
            "get": function() {
                return 'Root';
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', 'the "type" property of a OneDB_Object_Root is read-only' );
            }
        } );
        
        Object.defineProperty( this, 'uid', {
            "get": function() {
                return 1; // hardcoded user id of the root account
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'the "uid" property of a OneDB_Object_Root is read-only' );
            }
        } );
        
        Object.defineProperty( this, 'gid', {
            "get": function() {
                return 3; // hradcoded group id of the root account
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', 'the "gid" property of a OneDB_Object_Root is read-only' );
            }
        } );
        
        Object.defineProperty( this, 'muid', {
            "get": function() {
                return 1; // hardcoded user id of the root account
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', 'the "muid" property of a OneDB_Object_Root is read-only' );
            }
        } );
        
        Object.defineProperty( this, "parent", {
            "get": function() {
                return null;
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', 'the "parent" property of a OneDB_Object_Root is read-only' );
            }
        } );
        
        Object.defineProperty( this, "ctime", {
            "get": function() {
                return 0;
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', 'the "ctime" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, "mtime", {
            "get": function() {
                return 0;
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', 'the "mtime" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, 'owner', {
            "get": function() {
                return server.sys.user( 'root' );
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'The "owner" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, 'group', {
            "get": function() {
                return server.sys.group( 'root' );
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'the "group" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, 'mode', {
            "get": function() {
                return 484; // rwxr--r--
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'the "mode" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, "description", {
            "get": function() {
                return 'This is the uppermost node in the database tree';
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'the "description" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, 'icon', {
            "get": function() {
                return null;
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', 'the "icon" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, "keywords", {
            'get': function() {
                return [];
            },
            'set': function(val) {
                throw Exception( 'Exception.IO', 'the "keywords" property of a OneDB_Object_Root is read-only');
            }
        });
        
        Object.defineProperty( this, "tags", {
            "get": function() {
                return [];
            },
            "set": function( val ) {
                throw Exception('Exception.IO', 'the "tags" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, "online", {
            "get": function() {
                return true;
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'the "online" property of a OneDB_Object_Root is read-only' );
            }
        });
        
        Object.defineProperty( this, "url", {
            "get": function() {
                return '/';
            },
            "set": function( val ) {
                throw Exception( 'Exception.IO', 'the "url" property of a OneDB_Object_Root is read-only!' );
            }
        });
        
        Object.defineProperty( this, "_flags", {
            "get": function() {
                return 22; // CONTAINER ^ ROOT ^ READONLY
            },
            "set": function(val) {
                throw Exception( 'Exception.IO', "The '_flags' property of a OneDB_Object_Root is read-only!" );
            }
        } );
        
        this.addServerMethod( 'find', [
            {
                "name": "query",
                "type": "window.Object"
            },
            {
                "name": "limit",
                "type": "nullable integer",
                "default": null
            },
            {
                "name": "orderBy",
                "type": "nullable window.Object",
                "default": null
            }
        ] );
        
        this.addServerMethod( 'create', [
            {
                "name": "objectType",
                "type": "string"
            },
            {
                "name": "objectName",
                "type": "nullable string",
                "default": null
            },
            {
                "name": "flags",
                "type": "integer",
                "default": 0
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

/* Test flags ... */
OneDB_Object_Root.prototype.has_flag = function( what ) {
    if ( typeof what == 'string' ) {

        return ( ( OneDB_Object.prototype._flags_list[ what.toUpperCase() ] || 0 ) & this._flags )
            ? true
            : false;

    } else return false;
}


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

// @returns all the direct child nodes of the OneDB_Object_Root
Object.defineProperty( OneDB_Object_Root.prototype, "childNodes", {
    "get": function() {
        return OneDB.getRemoteProperty( this, "childNodes" );
    }
});

// this method is implemented only for compatibility between OneDB_Object_Root
// and OneDB_Object, but it throws exception when called
OneDB_Object_Root.prototype.save = function() {
    throw Exception( 'Exception.IO', "The root object cannot be saved!" );
};

// this method is implemented only for compatibility between OneDB_Object_Root
// and the OneDB_Object, but it throws exception when called
OneDB_Object_Root.prototype.chmod = function( mask, recursive ) {
    throw Exception( 'Exception.IO', "The root object cannot be chmoded!" );
}

// this method is implemented only for compatibility between OneDB_Object_Root
// and the OneDB_Object, but it throws exception when called.
OneDB_Object_Root.prototype.chown = function( userAndGroup, recursive ) {
    throw Exception( 'Exception.IO', "The root object cannot be chowned. It always belong to the 'root' account" );
}

// "moves" a childNode and makes the parent of the child the
// current object.
// @param childNode: type <OneDB_Object>.
OneDB_Object_Root.prototype.appendChild = function( childNode ) {
    if ( !childNode || !( childNode instanceof OneDB_Object ) )
        throw Exception('Exception.IO', 'The "appendChild" method first argument should be an instance of type OneDB_Object');
    
    return OneDB.runEndpointMethod( this, 'appendChild', [ childNode ] );
}