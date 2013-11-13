function OneDB_Type_Category_WebService( ) {
    
    this.__class = 'OneDB_Type_Category_WebService';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
};

OneDB_Type_Category_WebService.prototype = new OneDB_Type();

// Export the type...
OneDB_Types.OneDB_Type_Category_WebService = {
    
    "properties": [
        {
            "name"    : "webserviceMaxObjects",
            "type"    : "integer",
            "readOnly": false,
            "default" : -1
        },
        {
            "name"    : "webserviceUrl",
            "type"    : "string",
            "readOnly": false,
            "default" : ''
        },
        {
            "name"    : "webserviceTtl",
            "type"    : "integer",
            "readOnly": false,
            "default" : 0
        },
        {
            "name"    : "webserviceConf",
            "type"    : "window.Object",
            "readOnly": false,
            "default" : {}
        },
        {
            "name"    : "webserviceUsername",
            "type"    : "string",
            "readOnly": false,
            "default" : ""
        },
        {
            "name"    : "webservicePassword",
            "type"    : "string",
            "readOnly": false,
            "default" : ""
        },
        {
            "name"    : "webserviceLastHit",
            "type"    : "integer",
            "readOnly": true,
            "default" : -1
        },
        {
            "name"    : "webserviceObjectPath",
            "type"    : "string",
            "readOnly": false,
            "default" : ''
        },
        {
            "name"    : "webserviceTimeout",
            "type"    : "integer",
            "readOnly": false,
            "default" : 10
        }
    ],
    
    "methods": [
        
    ]
    
};