/* Tips:

    // __mux() and __demux() idea camed from the muxers / demuxers - see wikipedia article
    // about these terms.

    .__mux()
    
    // Converts the instance to an object that can be instantiated on the server side in it's
    // native implementation
    
    .__demux()
    
    // instantiates an local object based on a server serialized representation
    
*/

( function() {

    function OneDB_RPC() {
    
        this.is_primitive_type = function( value ) {
            var t = typeof value;
            return value === null || t == 'number' ||
                   t == 'boolean' || t == 'string';
        };
        
        this.is_composed_type = function( value ) {
            var t = typeof value;
            
            return ( ( t == 'object' && value.constructor && String( value.constructor ).match( /Object\(\)/ ) ) ||
                   ( value instanceof window.Array ) ) ? true : false;
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
        
        this.post = function( url, data, callback ) {
            
            callback = callback || null;
            
            var isSync = !!!callback,
                HTTP   = new window.XMLHttpRequest();
            
            if ( callback )
                HTTP.onreadystatechange = function() {
                    if ( HTTP.readyState == 4 )
                            callback( HTTP.status != 200 ? HTTP.status : false, HTTP.status == 200 ? HTTP.responseText : '' );
                };
            
            data = data || {};
            
            // form the query
            var query = [];
            
            for ( var k in data )
                if ( data.propertyIsEnumerable( k ) && data.hasOwnProperty( k ) )
                    query.push( encodeURIComponent( k ) + '=' + encodeURIComponent( String( data[k] ) ) );
            
            query = query.length 
                ? query.join( '&' )
                : '';
            
            HTTP.open( 'POST', url, !isSync );
            HTTP.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            HTTP.send( query );

            if ( !isSync ) {
                return true;
            } else {
                return HTTP.status == 200 ? HTTP.responseText : null;
            }
        };
        
        // Creates a POST ajax request to URL with query contained in data object,
        // parse result and calls the callback if the callback is specified, or
        // returns the JSON parsed value of the response on success or NULL on error.

        this.jpost = function( url, data, callback ) {
            
            if ( callback ) {
                
                this.post( url, data, function ( err, response ) {
                    
                    if ( err )
                        callback( err, null );
                    else {
                        // decode data
                        
                        try {
                        
                            response = JSON.parse( response );
                        
                            callback( false, response );
                        
                        } catch ( e ) {
                            
                            callback( "The server returned a non JSON value", null );
                            
                        }
                    }
                    
                } );
                
            } else {
                
                var result = this.post( url, data );
                
                if ( result === null )
                    return null;
                
                else {
                    
                    try {
                        
                        result = JSON.parse( result );
                        
                        return result;
                    
                    } catch ( e ) {
                        return null;
                    }
                    
                }
                
            }
            
        }
        
        this.mux = function( mixed ) {
            
            //console.log( "muxing: ", mixed );
            
            var iName;
            
            switch ( true ) {
                
                case this.is_primitive_type( mixed ):
                    
                    //console.log( "primitive_type: ", mixed );
                    
                    return mixed;
                    break;
                
                case this.is_composed_type( mixed ):

                    //console.log( "mixed_type: ", mixed );

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
            
//            console.log( "Demuxer: ", mixed );
            
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
                        
//                        console.log( "decoding "+  type + ", from: ", v );
                        
                        switch ( true ) {
                            
                            case type == 'window.Array':
                                // good, we're demuxing every element of the array
                                // v, and return the data.
                                
//                                console.log( "array branch", this );
                                
                                if ( !( v instanceof window.Array ) )
                                    throw "Expected value in 'v' is not an array!";
                                
                                var out = [];
                                
                                for ( var i=0, len = v.length; i<len; i++ ) {
                                    //console.log( "Demuxing: #", i, this.demux( v[i] ) );
                                    out.push( this.demux( v[i] ) );
                                }
                                
                                return out;
                                
                                break;
                            
                            case type == 'window.Object':
                                // good, we're demuxing every key of the object in
                                // v, and return the data.
                                
                                if ( ( v instanceof Array ) || !this.is_composed_type( v ) )
                                    throw "Expected value in 'v' is not a native JS object!";
                                
                                var out = {};
                                
                                for ( var k in v )
                                    if ( v.propertyIsEnumerable( k ) && v.hasOwnProperty( k ) )
                                        out[ k ] = this.demux( v[k] );
                                
                                return out;
                                
                                break;
                            default:
                                // we're trying to instantiate a class instance.
                                
                                if ( !window[ type ] )
                                    throw "A class called '" + type + "' is not found in the global scope!";
                                
                                if ( !window[ type ].prototype.__demux || typeof window[ type ].prototype.__demux != 'function' )
                                    throw "The class called '" + type + "' does not implement a '__demux' " + 
                                           "method or the '__demux' property of the class is not a function";
                                
                                return window[ type ].prototype.__demux( v );
                                
                                break;
                        }

                        break;

                    default:
                        //console.log( mixed );
                        throw "Undemuxable content: !";
                        break;
                }
                
            } catch ( error ) {
                throw error;
            }
        }

        // converts a JavaScript object, primitive or OneDB* class into a JSON representation,
        // in order to send it to server
        //NOTE: The "decode" decodes FROM the server, and the "encode" encodes FOR the server.
        this.encode = function( mixed ) {
            try {
                return JSON.stringify( this.mux( mixed ) );
            } catch ( error ) {
                throw "Failed to mux data: " + error;
            }
        }
        
        // converts a PHP-muxed value from server into a JavaScript structure.
        // note that the "decode" method does not work with "encode"d values,
        // and vice versa.
        //NOTE: The "decode" decodes FROM the server, and the "encode" encodes FOR the server.
        this.decode = function( str ) {
            try {
                return this.demux( JSON.parse( str ) );
            } catch ( error ) {
                throw "Failed to demux data: " + error;
            }
        }
        
        // Runs a remote method on the server side called @methodName, using
        // @methodArgs[] as arguments, and returns the result back here!
        this.runEndpointMethod = function( instance, methodName, methodArgs ) {
            
            var className = this.get_class_name( instance );
            
            if ( !className )
                throw "Failed to run the method '" + methodName + "': The object in not a OneDB instance!";
            
            var data = {
                "do"      : "run-method",
                "on"      : className,
                "method"  : methodName,
                "instance": this.encode( instance ),
                "args"    : this.encode( methodArgs )
            };
            
            var result = this.jpost( window.OneDBRpc, data );
            
            if ( result === null )
                throw "The server returned a non-decodable response. This might be due to errors on the php / webserver, or a bug";
            
            if ( result.ok ) {
                
                // decode result on the client side.
                
                return this.demux( result.result );
                
            } else {
                
                throw result.reason || 'unkonwn problem occured on server side';
                
            }
            
            //console.log( "RPC Send: ", data, result );
        }
        
        /* Fetches a property ( usually defined by a getter ) of a class instance
           from server-side and returns it
         */
        this.getRemoteProperty = function( instance, propertyName ) {
            
            var className = this.get_class_name( instance );
            
            if ( !className )
                throw "Failed to get property '" + propertyName + "': The object in not a OneDB instance!";
            
            var data = {
                "do"       : "get-property",
                "on"       : className,
                "instance" : this.encode( instance ),
                "property" : propertyName
            };
            
            result = this.jpost( window.OneDBRpc, data );
            
            if ( result === null )
                throw "The server returned a non-decodable response. This might be due to errors on the php / webserver, or a bug";
            
            if ( result.ok ) {
                
                return this.demux( result.result );
            
            } else {
                
                throw result.reason || 'unknown problem occured on server side';
            
            }
        };
    
    };

    var odb = new OneDB_RPC();

    Object.defineProperty( window, "OneDB", {
        "get": function() { return odb; }
    });

})();
