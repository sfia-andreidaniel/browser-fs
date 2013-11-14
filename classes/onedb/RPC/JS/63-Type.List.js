function OneDB_Type_List( client, data ) {

    this.__class = 'OneDB_Type_List';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
    
}

OneDB_Type_List.prototype = new OneDB_Type();

//Export the type to make it's properties on the instance.
OneDB_Types.OneDB_Type_List = {
    "properties": [
        {
            "name"    : "accept",
            "type"    : "string",
            "readOnly": false,
            "default" : ""
        },
        {
            "name"    : "maxItems",
            "type"    : "integer",
            "readOnly": false,
            "default" : 1
        },
        {
            "name"    : "items",
            "type"    : "any",
            "readOnly": false,
            "on"      : "server",
            "flags"   : OneDB_Object.prototype._flags_list.FLUSH
        }
    ]
};
