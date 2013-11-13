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


// @param data = [ [ muxed OneDB_Object[], muxed OneDB_Object[], ... ], muxed OneDB_Client ]
OneDB_Iterator.prototype.__demux = function( data ) {
    //console.log( data );
    
    for ( var i=0, len = data[0].length; i<len; i++ )
        data[0][i] = OneDB.demux( data[0][i] );
    
    return new OneDB_Iterator( data[0], OneDB.demux( data[1] ) );
}