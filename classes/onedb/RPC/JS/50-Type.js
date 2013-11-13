function OneDB_Type( ) {
    
    this.__class = 'OneDB_Type';
    
    this.init = function( rootObject, properties ) {
    
        //console.log( "new OneDB Type (" + this.__class + "): ", rootObject, properties );
    
        Object.defineProperty( this, "_root", {
            "get": function() {
                return rootObject;
            }
        } );
    
        // Expose properties to this object based on configuration
    
        if ( OneDB_Types[ this.__class ] ) {
    
            // Implement properties ...
    
            if ( OneDB_Types[ this.__class].properties ) {
    
                ( function( me ) {
    
                    for ( var i=0, len = OneDB_Types[ me.__class ].properties.length; i<len; i++ ) {
    
                        ( function( propName, config ) {
    
                            //console.log( "Defining property " + propName + "..." );
    
                            var local = config.get 
                                ? undefined
                                : ( typeof[ properties[ propName ] ] == 'undefined'
                                    ? ( config.default || null )
                                    : properties[ propName ]
                                );
    
                            Object.defineProperty( me, propName, {
    
                                "get": ( function() {
                                    return config.get 
                                        ? function() { return config.get.apply( me ); }
                                        : function() { return local; }
                                    ;
                                } )(),
                                "set": ( function() {
    
                                    if ( config.get && !config.readOnly )
                                        throw "Cannot implement a setter if the ( object is NOT readOnly ) + ( object has a native getter )!";
    
                                    return config.readOnly
                                        ? function( value ) {
                                            throw "Property " + propName + " is readOnly!";
                                        }
                                        : function( value ) {
                                            local = value;
                                            me._root.__change( 'data.' + propName, local );
                                        };
                                } )()
    
                            } );
    
                            // We want to resynchronize the property after the .save() method
                            // of the this._root is called
                            me.bind('property-resync', function( data ) {
                                if ( data.name == propName ) {
                                    //console.log( "Updating data." + propName + " on the local object..." );
                                    local = data.value;
                                } else console.log( "proxy.skyp: ", data.name );
                            });
    
                        } )( OneDB_Types[ me.__class ].properties[i].name, OneDB_Types[ me.__class ].properties[i] );
    
                    }
    
                } )( this );
    
            } else {
                console.log( "Warning: The " + this.__class + " does not have a binding in OneDB_Types" );
            }
    
        }
    
    }
    
    return this;
    
}

OneDB_Type.prototype = new OneDB_Class();