/* General methods:

    // __mux() and __demux() idea camed from the muxers / demuxers - see wikipedia article
    // about these terms.

    .__mux()
    
    // Converts the instance to an object that can be instantiated on the server side in it's
    // native implementation
    
    .__demux()
    
    // instantiates an local object based on a server serialized representation
    
*/

window.OneDB = ( function() {

    function OneDB_RPC() {
    
        this.is_primitive_type = function( value ) {
            var t = typeof value;
            return t == 'number' || t == null ||
                   t == 'boolean' || t == 'string';
        };
        
        this.is_composed_type = function( value ) {
            var t = typeof value;
            
            return ( ( t == 'object' && value.constructor && String( value.constructor ).match( /Object\(\)/ ) ) ||
                   ( t instanceof window.Array ) ) ? true : false;
        };
        
        this.is_instantiated_type = function( value ) {
            return typeof value == 'object' && value.constructor && /^function /.test( String( value.constructor ) );
        };
        
        this.get_class_name = function( value ) {
            
            if ( !this.is_instantiated_type( value ) )
                return null;
            
            if ( !value.__class )
                return null;
            
            return value.__class;
        };
        
        this.mux = function( mixed ) {
            
            // console.log( "muxing: ", mixed );
            
            var iName;
            
            switch ( true ) {
                
                case this.is_primitive_type( mixed ):
                    
                    console.log( "primitive_type: ", mixed );
                    
                    return mixed;
                    break;
                
                case this.is_composed_type( mixed ):

                    console.log( "mixed_type: ", mixed );

                    if ( mixed instanceof Array ) {
                        var out = [];

                        // is array
                        for ( var i=0, len = mixed.length; i<len; i++ ) {
                            out.push( this.mux( mixed[i] ) );
                        }
                        return {
                            "type": "window.Array",
                            "v": out
                        };
                    } else {
                        // is object
                        var out = {};
                        for ( var k in mixed )
                            if ( mixed.propertyIsEnumerable( k ) && mixed.hasOwnProperty( k ) )
                                out[k] = this.mux( mixed[k] );
                        return {
                            "type": "window.Object",
                            "v": out
                        };
                    }
                    
                    break;
                
                case !!( ( iName = this.get_class_name( mixed ) ) && iName.length ):
                    
                    // console.log( "instantiated_type: ", mixed );
                    
                    if ( !window[ iName ] )
                        throw "Attempted to mux an instance of " + iName + " but it's class was not found in the global scope!";
                    
                    if ( typeof mixed['__mux'] != 'function' )
                        throw "Attempted to mux an instance of " + iName + " but it don't implement a __mux() method!";
                    
                    return {
                        "type": iName,
                        "v": mixed.__mux()
                    };
                    
                    break;
                
                default:
                    
                    return null;
                    
                    break;
            }
            
        };
        
        this.demux = function( mixed ) {
            
            var type, v;
            
            try {
                
                switch ( true ) {
                    
                    case this.is_primitive_type( mixed ):
                        return mixed;
                    
                    case this.is_composed_type( mixed ):
                        if ( mixed instanceof Array )
                            throw "Unexpected native Array!";

                        // Only objects are allowed
                        type = mixed.type || '';

                        if ( !type )
                            throw "Expected data 'type' key in object!";
                        
                        v = mixed.v;
                        
                        switch ( true ) {
                            
                            case type == 'window.Array':
                                // good, we're demuxing every element of the array
                                // v, and return the data.
                                break;
                            
                            case type == 'window.Object':
                                // good, we're demuxing every key of the object in
                                // v, and return the data.
                                break;
                            default:
                                // we're trying to instantiate a class instance.
                                break;
                        }

                        break;

                    default:
                        throw "Undemuxable content!";
                        break;
                }
                
            } catch ( error ) {
                throw "Failed to demux data: " + error;
            }
        }

        // converts a JavaScript object, primitive or OneDB* class into a JSON representation,
        // in order to send it to server
        //NOTE: The "decode" decodes FROM the server, and the "encode" encodes FOR the server.
        this.encode = function( mixed ) {
            return JSON.stringify( this.mux( mixed ) );
        }
        
        // converts a PHP-muxed value from server into a JavaScript structure.
        // note that the "decode" method does not work with "encode"d values,
        // and vice versa.
        //NOTE: The "decode" decodes FROM the server, and the "encode" encodes FOR the server.
        this.decode = function( str ) {
            
        }
        
    };

    return new OneDB_RPC();

})();

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

function OneDB_Base() {
    
    this.__class = "OneDB_Base";
    
    this.bind = function( eventName, callback ) {
        this.__events = this.__events || {};
        if ( callback ) {
            this.__events[ eventName ] = this.__events[eventName] || [];
            this.__events[ eventName ].push( callback );
        }
    }
    
    this.on = function( eventName, eventData ) {
        this.__events = this.__events || {};

        if ( this.__events[ eventName ] )
            for ( var i=0, len=this.__events[eventName].length; i<len; i++ )
                if ( this.__events[eventName][i].call( this, eventData ) === false )
                    return false;

        return true;
    }

    this.__mux = function() {
        return {
            "type": this.__class
        };
    };
    
    this.__create = function() {
        if ( typeof this.init == 'function' ) {
            
            this.init.call( this );
            
        }
    }
    
    return this;
}

function OneDB_Class( ) {
    
    this.__class = "OneDB_Class";
    
    this.addServerMethod = function( methodName, methodArgs ) {
        
        this.__methods = this.__methods || {};
        
        this.__methods[ methodName ] = methodArgs;
        
        ( function( me ) {
            me[ methodName ] = function() {
                
                var args = Array.prototype.slice.call( arguments, 0 ),
                    numArgs = 0;
                
                // at this point we check only if the number of the arguments 
                // of invocation is equal with the number of arguments from the
                // method definition.
                
                if ( args.length != ( numArgs = me.__methods[ methodName ].length ) )
                    throw "This method requires " + numArgs + " arguments: " + JSON.stringify( me.__methods[ methodName ] );
                
                // create rpc call
                
                var rpcParams = {
                    "type"  : me.__class,
                    "__construct"  : me.__mux(),
                    "method": methodName,
                    "args"  : args
                };
                
                console.log( "RPC-ing: ", rpcParams );
                
            };
        })(this);
        
    }
    
}

OneDB_Class.prototype = new OneDB_Base();

function OneDB_Client( websiteName, runAs ) {
    
    this.__class = "OneDB_Client";
    
    this.init = function() {
        
        this._initArgs = [
            websiteName,
            runAs
        ];

    }
    
    this.__mux = function() {
        return this._initArgs[0] + ':' + ( this._initArgs[1] || '' );
    };
    
    this.addServerMethod( "getElementByPath", [
        {
            "name": "elementPath",
            "type": "string"
        }
    ]);
    
    this.addServerMethod( "getElementById", [
        {
            "name": "elementId",
            "type": "nullable string"
        }
    ]);
    
    this.__create();
    
    return this;
}

OneDB_Client.prototype = new OneDB_Class();

function OneDB_Iterator( data, server ) {
    
    this.__class = 'OneDB_Iterator';
    
    var _items = [];
    var _server= null;
    
    this.__mux = function() {
        var out = [];
        
        for ( var i=0, len = _items.length; i<len; i++ ) {
            out.push( _items[i].id );
        }
        
        return [ out, OneDB.mux( _server ) ];
    }
    
    this.init = function() {

        // setup the _items
        _items = data instanceof Array
            ? data
            : ( data 
                ? [ data ]
                : []
            );
        
        _server = server;
        
        Object.defineProperty( this, "length", {
            
            "get": function() {
                return _items.length;
            }
            
        } );
        
        Object.defineProperty( this, "items", {
            
            "get": function() {
                // ensure we're not returning the &_items object.
                return _items.slice( 0 );
            }
            
        } );
        
        this.addServerMethod( 'find', [
            {
                "name": "query",
                "type": "object"
            }
        ] );
    };
    
    this.each = function( callback ) {
        
        if ( callback instanceof Function ) {
            
            for ( var i=0, len = _items.length; i<len; i++ ) {
                
                if ( callback( _items[i], i, this ) === false )
                    break;
                
            }
            
        }
        
        return this;
    };
    
    this.here = function( callback ) {
        
        if ( callback instanceof Function ) {
            
            callback( this );
            
        }
        
        return this;
        
    };
    
    this.filter = function( callback ) {
        
        if ( callback instanceof Function ) {
            
            var out = [];
            
            for ( var i=0, len = _items.length; i<len; i++ ) {
                
                if ( callback( _items[i] ) )
                    out.push( _items[i] );
                
                return new OneDB_Iterator( out, _server );
                
            }
            
        } else return this;
        
    };
    
    this.sort = function( callback ) {
        
        if ( callback instanceof Function && _items.length ) {
            
            return new OneDB_Iterator( _items.slice(0).sort( callback ), _server );
            
        } else return this;
        
    };
    
    this.reverse = function() {
        if ( !_items.length )
            return this;
        else
            return new OneDB_Iterator( _items.slice(0).reverse(), _server );
    };
    
    this.skip = function( howMany ) {
        
        howMany = ~~howMany;
        
        if ( howMany > 0)
            
            return new OneDB_Client( _items.slice( howMany ), _server );
            
        else return new OneDB_Iterator([], _server );
        
    };
    
    this.limit = function( howMany ) {
        
        howMany = ~~howMany;
        
        if ( howMany > 0 )
            
            return new OneDB_Iterator( _items.slice( 0, howMany ), _server );
        
        else return new OneDB_Iterator([], _server );
    
    }
    
    this.get = function( index ) {
        
        index = ~~index;
        
        if ( index >= 0 && index < _items.length )
            return _items[index];
        else
            throw "OneDB_Iterator: Exception: Index " + index + " out of bounds [ 0.." + ( _items.length - 1 ) + "]";
        
    };
    
    this.join = function( otherIterator ) {
        
        var myLen = _items.length,
            hisLen= otherIterator.length;
        
        switch ( true ) {
            
            case myLen > 0 && hisLen > 0:
                
                var out = _items.slice(0);
                
                for ( var i=0, it = otherIterator.items, len = it.length; i<len; i++ )
                    out.push( it[i] );
                
                return new OneDB_Iterator( out, _server );
                
                break;
            
            case myLen == 0:
                return otherIterator;
                break;
            
            default:
                return this;
                break;
        
        }
        
    };
    
    this.add = function( item ) {
        _items.push( item );
        return this;
    };
    
    this.continueIf = function( boolOrCallback ) {
        
        if ( boolOrCallback instanceof Function ) {
            
            if ( boolOrCallback() )
                return this;
            else
                return new OneDB_Iterator( [], _server );
            
        } else {
            
            if ( !!boolOrCallback )
                return this;
            else
                return new OneDB_Iterator( [], _server );
            
        }
        
    };
    
    this.__create();
    
    return this;
    
};

OneDB_Iterator.prototype = new OneDB_Class();

function OneDB_Object( server, properties ) {
    
    var _server    = null,
        _batch     = [];
    
    this.__class = 'OneDB_Object';
    
    this.__change = function( propertyName, propertyValue ) {
        this.on( 'change', { "name": propertyName, "value": propertyValue } );
    };
    
    this.init = function() {
        
        _server = server;
        
        // In the properties we expect to have a OneDB_Object serialization
        
        for ( var i=0, len = this._nativeProperties.length; i<len; i++ ) {
            
            ( function( property, me ) {
                
                var localProperty = properties[ property ] || null;
                
                Object.defineProperty( me, property, {
                    "get": function() {
                        return localProperty;
                    },
                    "set": function( data ) {
                        localProperty = data;
                        me.__change( property, data );
                    }
                });
                
                me.bind( 'property-resync', function( data ) {
                    if ( data.name == property ) {
                        console.log( 'resync prop: ' + property + ' on root object!' );
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
    
    this.__mux = function() {
        return [ this.id ];
    };
    
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

        if ( !this.changed )
            return;

        //resynchronize object with the value returned by
        //the __commit method on the server side.
        this.__resync( this.__commit( _batch ) );

    }
    
    this.__create();
    
    return this;
    
}



OneDB_Object.prototype = new OneDB_Class();

OneDB_Object.prototype._nativeProperties = [ 'id', 'parent', /* 'type', */ 'name', 'created', 'modified', 'owner', 'modifier', 'description', 'icon', 'keywords', 'tags', 'online' ];

function OneDB_Type( ) {
    
    this.__class = 'OneDB_Type';
    
    this.init = function( rootObject, properties ) {
    
        console.log( "new OneDB Type (" + this.__class + "): ", rootObject, properties );
    
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
    
                            console.log( "Defining property " + propName + "..." );
    
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
                                    console.log( "Updating data." + propName + " on the local object..." );
                                    local = data.value;
                                } else console.log( "proxy.skyp: ", data.name );
                            });
    
                        } )( OneDB_Types[ me.__class ].properties[i].name, OneDB_Types[ me.__class ].properties[i] );
    
                    }
    
                } )( this );
    
            }
    
        }
    
    }
    
    return this;
    
}

OneDB_Type.prototype = new OneDB_Class();

function OneDB_Type_Category( ) {
    
    console.log( "new category: ", arguments );
    
    this.__class = 'OneDB_Type_Category';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
};

OneDB_Type_Category.prototype = new OneDB_Type();


function OneDB_Type_Document( ) {

    console.log( "new document: ", arguments );
    
    this.__class = 'OneDB_Type_Document';
    
    this.init.apply( this, Array.prototype.slice.call( arguments, 0 ) );
    
    return this;
    
}

OneDB_Type_Document.prototype = new OneDB_Type();

var client = new OneDB_Client( "loopback", "andrei" );
var iterator = new OneDB_Iterator( [ { "id": "a1" }, {"id": "a2"}, {"id":"a3"} ], client );