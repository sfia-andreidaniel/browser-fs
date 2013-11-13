function OneDB_Object( server, properties ) {
    
    var _server    = null,
        _batch     = [];
    
    this.__class = 'OneDB_Object';
    
    this.__change = function( propertyName, propertyValue ) {
        this.on( 'change', { "name": propertyName, "value": propertyValue } );
    };
    
    this.init = function() {
        
        _server = server;
        
        Object.defineProperty( this, "find", {
            "get": function() {
                return ( function( me ) {
                    return me.has_flag( 'container' )
                        ? function() {
                            return OneDB.runEndpointMethod( me, "find", Array.prototype.slice.call( arguments, 0 ) );
                        }
                        : function() {
                            console.warn( "Warning: running .find on a OneDB_Object which is not a container (url = " + me.url + "). Returning a dummy result." );
                            return new OneDB_Iterator( [], me._root );
                        }
                } )( this )
            }
        } );
        
        Object.defineProperty( this, "_server", {
            "get": function() {
                return server;
            },
            "set": function() {
                throw "The '_server' property of a OneDB_Object is read-only";
            }
        });
        
        Object.defineProperty( this, "childNodes", {
            "get": function() {
                return OneDB.getRemoteProperty( this, "childNodes" );
            }
        } );
        
        // In the properties we expect to have a OneDB_Object serialization
        
        for ( var i=0, len = this._nativeProperties.length; i<len; i++ ) {
            
            ( function( property, me ) {
                
                var localProperty = properties[ property ] || ( property == '_flags' ? 0 : null );
                
                Object.defineProperty( me, property, {
                    "get": function() {
                        return localProperty;
                    },
                    "set": ( function() {
                            
                            if ( [ 'id', 'modifier', 'owner', 'created', 'modified', 'url', '_flags' ].indexOf( property ) >= 0 )
                            return function( v ) {
                                throw "The '" + property + "' of a OneDB_Object is read-only!";
                            }; else
                            return function( data ) {
                                localProperty = data;
                                me.__change( property, data );
                            }
                    } )()
                });
                
                me.bind( 'property-resync', function( data ) {
                    if ( data.name == property ) {
                        console.log( 'resync prop: ' + property + ' on root object with: ', data.value );
                        localProperty = data.value;
                    }
                } );
                
            })( this._nativeProperties[i], this );
            
        }
        
        // Setup the type getter setter
        var myType = null,
            lastType = null;
            
        Object.defineProperty( this, "type", {
            
            "get": function() {
                
                if ( myType )
                    return myType.__class.replace( /^OneDB_Type_/, '' );
                else
                    return null;
            
            },
            "set": function( typeName ) {
                
                lastType = ( typeName || '' ).replace( /[\._]+/g, '_' );
                
                if ( !window[ 'OneDB_Type_' + lastType ] )
                    throw "Failed to set object type: The class OneDB_Type_" + lastType + " is not implemented!";
                
                myType = new window[ "OneDB_Type_" + typeName ]( this, properties );
                
                this.__change( 'type', typeName.replace( /[_]+/g, '.' ) );
            }
                
        } );
        
        this.bind( 'property-resync', function( data ) {
            
            if ( data.name == 'type' && data.value != lastType ) {
                this.type = data.value;
            }
            
        } );
        
        Object.defineProperty( this, "data", {
            
            "get": function() {
                return myType || {};
            }
            
        } );
        
        if ( properties.type )
            this.type = properties.type;
        
        this.bind( 'change', function( data ) {
            
            /* Test if there is another previous set property in the batch */
            
            for ( var i=0, len = _batch.length; i<len; i++ ) {
                
                if ( _batch[i].name == data.name ) {
                    _batch.splice( i, 1 );
                    break;
                }
                
            }
            
            _batch.push( data );
            
            console.log( "saved batch is: ", _batch );
            
        } );
        
        Object.defineProperty( this, "changed", {
            "get": function() {
                return _batch.length;
            }
        });
        
        
        this.addServerMethod( '__commit', [
            { "name": "batch",
              "type": "array"
            }
        ] );
        
    }
    
    // resynchronize object with the information sent by the server
    // after the .save() is issued.
    this.__resync = function( obj ) {
        
        console.log( 'resynchronizing object...' );
        
        obj = obj || {};
        
        for ( var i=0, len = this._nativeProperties.length; i<len; i++ ) {
            
            if ( typeof obj[ this._nativeProperties[ i ] ] != 'undefined' ) {
                this.on( 'property-resync', { "name": this._nativeProperties[i], "value": obj[ this._nativeProperties[i] ] } );
            } else {
                this.on( 'property-resync', { "name": this._nativeProperties[i], "value": null } );
            }
            
        }
        
        if ( this.type )
            for ( var k in obj ) {
                
                if ( obj.hasOwnProperty( k ) && obj.propertyIsEnumerable( k ) && this._nativeProperties.indexOf( k ) == -1 )
                    this.data.on( 'property-resync', { "name": k, "value": obj[ k ] } );
            }
        
        // flush batch
        _batch = [];
    }
    
    // Saves the object on the server if has local modifications
    // on the client side.
    this.save = function() {
        
        switch ( true ) {
            
            case !this.changed:
                return;
                break;
            
            case this.has_flag( 'readonly' ):
                throw "The object cannot be saved because it is read-only!";
                break;
            
            case this.has_flag( 'live' ):
                throw "Live objects cannot be saved!";
                break;
            
            case this.has_flag( 'unstable' ):
                throw "The object cannot be saved because it has been retrieved from server in an unstable state!";
                break;
        
        }
        
        //resynchronize object with the value returned by
        //the __commit method on the server side.
        this.__resync( this.__commit( _batch ) );

    }
    
    this.__create();
    
    return this;
    
}

/* The OneDB_Object is extending a OneDB_Class
 */
OneDB_Object.prototype = new OneDB_Class();

/* Possible object internal flags */
OneDB_Object.prototype._flags_list = {
    "NOFLAG": 0,
    "READONLY": 2,
    "CONTAINER": 4,
    "UNLINKED": 8,
    "ROOT": 16,
    "UNSTABLE": 32,
    "LIVE": 64
};

/* A list of properties that are defined automatically
   to the object on creation.

   These are called native properties, because no matter
   which data type the object implements, it will have in
   it's root these properties.
*/

OneDB_Object.prototype._nativeProperties = [ 
    'id',
    'parent',
    /* 'type', */
    'name',
    'created',
    'modified',
    'owner',
    'modifier',
    'description',
    'icon',
    'keywords',
    'tags',
    'online',
    'url',
    '_flags'
];

/* Test flags ... */
OneDB_Object.prototype.has_flag = function( what ) {
    if ( typeof what == 'string' ) {
        
        return ( ( this._flags_list[ what.toUpperCase() ] || 0 ) & this._flags )
            ? true
            : false;
        
    } else return false;
}

// Object muxer
OneDB_Object.prototype.__mux   = function() {
    return OneDB.mux( [ this.id ? this.id.__mux() : null, this._server.__mux() ] );
};

// Object demuxer
// @param data => muxed [ OneDB_Client client, Object properties ]
OneDB_Object.prototype.__demux = function( data ) {
    
    data = OneDB.demux( data );

    return new OneDB_Object( data[0], data[1] );
};