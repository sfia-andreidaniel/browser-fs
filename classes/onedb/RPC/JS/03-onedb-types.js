var OneDB_Types = {
    
    "OneDB_Type_Category": {
        
        "properties": [
            
        ],
        
        "methods": [
            
        ]
        
    },
    
    "OneDB_Type_Document": {
        
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
        
    }
    
};
