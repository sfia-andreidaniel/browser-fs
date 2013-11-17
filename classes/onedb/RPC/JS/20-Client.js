function OneDB_Client( websiteName, userName, storageEngine, shadowChallenge ) {
    
    this.__class = "OneDB_Client";
    this.__storage = null;
    
    this.init = function() {
        
        this._initArgs = [
            websiteName,
            userName,
            storageEngine,
            shadowChallenge || ''
        ];
        

    }
    
    Object.defineProperty( this, "runAs", {
        "get": function() {
            return this._initArgs[1];
        }
    });
    
    Object.defineProperty( this, "websiteName", {
        "get": function() {
            return this._initArgs[0];
        }
    });
    
    Object.defineProperty( this, "shadowChallenge", {
        "get": function() {
            return this._initArgs[3] || '';
        }
    } );
    
    Object.defineProperty( this, "storage", {
        
        "get": function() {
            return this.__storage || ( this.__storage = new OneDB_Storage( this, this._initArgs[2] ) );
        }
        
    } );
    
    Object.defineProperty( this, "root", {
        
        "get": function() {
            return new OneDB_Object_Root( this );
        }
        
    } );
    
    this.addServerMethod( "getElementByPath", [
        {
            "name": "elementPath",
            "type": "string"
        }
    ]);
    
    this.addServerMethod( "getElementById", [
        {
            "name": "elementId",
            "type": "nullable string"
        }
    ]);
    
    this.addServerMethod( "find", [
        {
            "name"   : "query",
            "type"   : "window.Object",
            "default": {}
        },
        {
            "name"    : "limit",
            "type"    : "nullable integer",
            "default" : null
        },
        {
            "name"    : "orderBy",
            "type"    : "nullable window.Object",
            "default" : null
        }
    ] );
    
    ( function( me ) {
        var lsys = 0,
            _cache= null;
        
        Object.defineProperty( me, "sys", {
            "get": function() {
                var now;
                if ( _cache === null || ( ( ( now = new Date() ).getTime() ) - lsys ) > 60000 ) {
                    lsys   = now;
                    _cache = OneDB.getRemoteProperty( me, "sys" );
                }
                return _cache;
            }
        } );
    } )( this );
    
    Object.defineProperty( this, 'user', {
        "get": function() {
            return this.sys.user( this.runAs );
        }
    });
    
    this.__create();
    
    return this;
}

OneDB_Client.prototype = new OneDB_Class();

OneDB_Client.prototype.__singletons = {};

OneDB_Client.prototype.__demux = function( muxedData ) {
    
    if ( typeof muxedData !== 'string' )
        throw "Failed to demux a OneDB_Client class: the muxed data is not a string";
    
    if ( this.__singletons[ muxedData ] )
        return this.__singletons[ muxedData ];
    
    var args = muxedData.split( ':' );
    
    //args[1] = args.slice( 1 ).join(':');
    
    var siteName        = args[0],
        runAs           = args[1] || '';
        storageName     = args[2] || '',
        shadowChallenge = args[3] || '';
    
    return this.__singletons[ muxedData ]
        = new OneDB_Client( siteName, runAs, storageName, shadowChallenge );
    
}

OneDB_Client.prototype.__mux = function() {
    var mux = this._initArgs[0] + ':' + ( this._initArgs[1] || '' ) + ':' + this._initArgs[3];
    //console.log( "muxing: ", mux );
    return mux;
}

