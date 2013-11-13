function OneDB_Client( websiteName, runAs ) {
    
    this.__class = "OneDB_Client";
    
    this.init = function() {
        
        this._initArgs = [
            websiteName,
            runAs
        ];

    }
    
    /*
    this.__mux = function() {
        return this._initArgs[0] + ':' + ( this._initArgs[1] || '' );
    };
    */
    
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
    
    args[1] = args.slice( 1 ).join(':');
    
    var siteName = args[0];
    var runAs    = args[1] || '';
    
    return this.__singletons[ muxedData ]
        = new OneDB_Client( siteName, runAs );
    
}

OneDB_Client.prototype.__mux = function() {
    return this._initArgs[0] + ':' + ( this._initArgs[1] || '' );
}

