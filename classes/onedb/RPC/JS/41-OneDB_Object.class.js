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

