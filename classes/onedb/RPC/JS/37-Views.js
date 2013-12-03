// This class implements the Views of a OneDB_Object or OneDB_Object_Root
function OneDB_Object_Views( object, isVirtual ) {
    
    // class name
    this.__class = 'OneDB_Object_Views';
    
    // constructor
    this.init = function() {
        
        Object.defineProperty( this, "_object_", {
            "get": function() {
                return object;
            }
        } );
        
        Object.defineProperty( this, "_virtual_", {
            "get": function() {
                return !!isVirtual;
            }
        } );
    }
    
    // initialize the class
    this.__create();
    
    return this;
    
}

// This class inherits the OneDB_Class
OneDB_Object_Views.prototype = new OneDB_Class();

// Muxer method
OneDB_Object_Views.prototype.__mux = function() {
    return this._object_.__mux();
};

OneDB_Object_Views.prototype.getView = function( viewType, viewName ) {
    if ( this._virtual_ )
        throw Exception('Exception.OneDB', 'Operation not supported!', 0, null, __FILE__, __LINE__ );
    else
        return OneDB.runEndpointMethod( this, 'getView', [ viewType, viewName ] )._setArgument_( this._object_ );
}

OneDB_Object_Views.prototype.setView = function( viewType, viewName, widget, justForType ) {
    if ( this._virtual_ )
        throw Exception('Exception.OneDB', 'Operation not supported!', 0, null, __FILE__, __LINE__  );
    else
        return OneDB.runEndpointMethod( this, 'setView', [ viewType, viewName, widget, justForType || null ] );
}

OneDB_Object_Views.prototype.enumerateViews = function() {
    if ( this._virtual_ )
        throw Exception('Exception.OneDB', 'Operation not supported!', 0, null, __FILE__, __LINE__  );
    else
        return OneDB.runEndpointMethod( this, 'enumerateViews', [] );
}

OneDB_Object_Views.prototype.deleteView = function( viewId ) {
    if ( this._virtual_ )
        throw Exception('Exception.OneDB', 'Operation not supported!', 0, null, __FILE__, __LINE__  );
    else
        return OneDB.runEndpointMethod( this, 'deleteView', [ viewId ] );
};