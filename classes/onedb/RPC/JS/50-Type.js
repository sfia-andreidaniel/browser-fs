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
    
                            if ( config.on != 'server' ) {
    
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
                                    } //else console.log( "proxy.skyp: ", data.name );
                                });
                            
                            } else {
                                
                                // we're defining a "on-server" method.
                                
                                Object.defineProperty( me, propName, {
                                    
                                    "get": function() {
                                        
                                        switch ( true ) {
                                            
                                            // If the method has the FLAG field, we must do an automatically save to the object
                                            case !!( config.flags && ( config.flags & OneDB_Object.prototype._flags_list.FLUSH ) ):
                                                me._root.save();
                                                break;
                                            
                                        }
                                        
                                        return OneDB.getRemoteProperty( me._root, 'data.' + config.name );
                                        
                                    },
                                    
                                    "set": ( function() {
                                        
                                        if ( config.readOnly )
                                            return function(v) {
                                                throw "The property '" + config.name + "' of a " + me.__class + " is read-only!";
                                            };

                                        else

                                            return function(v) {
                                                
                                                me._root.__change( 'data.' + config.name, v );
                                                
                                                switch ( true ) {
                                            
                                                    // If the method has the FLAG field, we must do an automatically save to the object
                                                    case !!( config.flags && ( config.flags & OneDB_Object.prototype._flags_list.FLUSH ) ):
                                                        me._root.save();
                                                        break;
                                                    
                                                }
                                                
                                                //OneDB.setRemoteProperty( me._root, 'data.' + config.name, v );
                                                
                                            };
                                        
                                    } )()
                                    
                                } );
                                
                            }
                            
                        } )( OneDB_Types[ me.__class ].properties[i].name, OneDB_Types[ me.__class ].properties[i] );
    
                    }
    
                } )( this );
    
            }
            
            if ( OneDB_Types[ this.__class ].methods ) {
                
                for ( var i=0, len = OneDB_Types[ this.__class ].methods.length; i<len; i++ ) {
                    
                    ( function( me, method ) {
                        
                        if ( !method.implementation )
                            throw "No implementation defined on the method " + method.name + " for class " + me.__class;
                        
                        if ( typeof method.implementation != 'function' ) {
                            
                            // Add server implemented method.
                            if ( method.implementation != 'server' )
                                throw "Method implementations can be either 'server' either a function (class: " + me.__class + ")!";
                            
                            me[ method.name ] = function() {
                                // !!!TODO: strict type checking method arguments
                                return OneDB.runEndpointMethod( me._root, "data." + method.name, Array.prototype.slice.call( arguments, 0 ) );
                            };
                            
                        } else {
                            
                            // Add local implemented method
                            me[ method.name ] = function() {
                                return method.implementation.apply( me, Array.prototype.slice.call( arguments, 0 ) );
                            };

                        }
                        
                        //console.log( "Adding Type method: " + method.name );
                    
                    })( this, OneDB_Types[ this.__class ].methods[i] );
                    
                }
                
            }
    
        } else {
            console.log( "Warning: The " + this.__class + " does not have a binding in OneDB_Types" );
        }
    
    }
    
    return this;
    
}

OneDB_Type.prototype = new OneDB_Class();