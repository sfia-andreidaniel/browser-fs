function OneDB_Type_Category_Aggregator( ) {
    
    this.__class = 'OneDB_Type_Category_Aggregator';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
};

// The OneDB_Type_Category_Aggregator is inheriting the class OneDB_Type.
OneDB_Type_Category_Aggregator.prototype = new OneDB_Type();

OneDB_Types.OneDB_Type_Category_Aggregator = {

    "properties": [
        {
            "name"    : "paths",
            "type"    : "window.Array",
            "readOnly": false,
            "default" : []
        },
        {
            "name"    : "maxItems",
            "type"    : "integer",
            "readOnly": false,
            "default" : -1
        }
    ],

    "methods": [

    ]

}