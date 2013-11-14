function OneDB_Type_File( client, data ) {

    this.__class = 'OneDB_Type_File';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
    
}

OneDB_Type_File.prototype = new OneDB_Type();

//Export the type to make it's properties on the instance.
OneDB_Types.OneDB_Type_File = {
    "properties": [
        {
            "name"    : "fileId",
            "type"    : "integer",
            "readOnly": true,
            "default" : ""
        },
        {
            "name"    : "fileVersions",
            "type"    : "object",
            "readOnly": true,
            "default" : {}
        },
        {
            "name"    : "fileSize",
            "type"    : "integer",
            "readOnly": true,
            "default" : 0
        },
        {
            "name"    : "fileType",
            "type"    : "string",
            "readOnly": true,
            "default" : ''
        },
        {
            "name"    : "storageResponseData",
            "type"    : "object",
            "readOnly": false,
            "on"      : "server",
            "flags"   : OneDB_Object.prototype._flags_list.FLUSH
        }
    ],
    
    "methods": [
        {
            "name": "getFileFormat",
            "implementation": "server",
            "args": [
                {
                    "name": "format",
                    "type": "string"
                }
            ]
        }
    ]
};
