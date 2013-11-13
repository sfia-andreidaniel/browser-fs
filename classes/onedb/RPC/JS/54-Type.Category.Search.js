function OneDB_Type_Category_Search( ) {
    
    this.__class = 'OneDB_Type_Category_Search';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
};

// The OneDB_Type_Category_Search is inheriting the class OneDB_Type
OneDB_Type_Category_Search.prototype = new OneDB_Type();

OneDB_Types.OneDB_Type_Category_Search = {

    "properties": [
        {
            "name"    : "query",
            "type"    : "window.Object",
            "readOnly": false,
            "default" : {}
        }
    ],

    "methods": [

    ]

};