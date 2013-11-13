function OneDB_Type_Document( ) {

    //console.log( "new document: ", arguments );
    
    this.__class = 'OneDB_Type_Document';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
    
}

OneDB_Type_Document.prototype = new OneDB_Type();

//Export the type to make it's properties on the instance.
OneDB_Types.OneDB_Type_Document = {

    "properties": [
        {
            "name"    : "document",
            "type"    : "string",
            "readOnly": false,
            "default" : ""
        },
        {
            "name": "title",
            "type": "string",
            "readOnly": true,
            "default" : ""
        },
        {
            "name"    : "textContent",
            "type"    : "string",
            "readOnly": true,
            "default" : ""
        },
        {
            "name"    : "isDocumentTemplate",
            "type"    : "boolean",
            "readOnly": true,
            "default" : false
        },
        {
            "name": "dom",
            "type": "Node",
            "readOnly": true,
            "get": function() {
                var node = document.createElement( 'div' );
                node.innerHTML = this.document;
                return node;
            }
        }
    ],

    "methods": [
    ]

};
