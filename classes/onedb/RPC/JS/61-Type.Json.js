function OneDB_Type_Json( client, data ) {

    this.__class = 'OneDB_Type_Json';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    for ( var key in data ) {
        
        if ( data.propertyIsEnumerable( key ) &&
             data.hasOwnProperty( key ) &&
             OneDB_Object.prototype._nativeProperties.indexOf( key ) == -1
        ) this[ key ] = data[key];
        
    }
    
    return this;
    
}

OneDB_Type_Json.prototype = new OneDB_Type();

//Export the type to make it's properties on the instance.
OneDB_Types.OneDB_Type_Json = {
    "properties": []
};
