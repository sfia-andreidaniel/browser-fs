function OneDB_Type_Widget( client, data ) {

    this.__class = 'OneDB_Type_Widget';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
    
}

OneDB_Type_Widget.prototype = new OneDB_Type();

//Export the type to make it's properties on the instance.
OneDB_Types.OneDB_Type_Widget = {
    "properties": [
        {
            "name"    : "php",
            "type"    : "string",
            "readOnly": false,
            "default" : ""
        },
        {
            "name"    : "html",
            "type"    : "string",
            "readOnly": false,
            "default" : ""
        },
        {
            "name"    : "engine",
            "type"    : "string",
            "readOnly": false,
            "default" : "html"
        },
    ],
    "methods": [
        {
            "name"           : "run",
            "implementation" : "server",
            "args"           : [
                {
                    "name"   : "ENV",
                    "type"   : "window.Object",
                    "default": {}
                }
            ]
        }
    ]
};
