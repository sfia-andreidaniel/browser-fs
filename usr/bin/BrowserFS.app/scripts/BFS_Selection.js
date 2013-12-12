function BFS_Selection( app ) {
    
    var items = [];
    
    this.clear = function() {

        while ( items.length )
            items.shift().selected = false;

        app.interface.on( 'selection-changed' );
    };
    
    this.add = function( singleItem ) {
    
        for ( var i=0, len = items.length; i<len; i++ )
            if ( items[i] == singleItem )
                return;

        items.push( singleItem );

        singleItem.selected = true;

        app.interface.on( 'selection-changed' );
    };
    
    this.set = function( singleItem ) {
        this.clear();
        
        for ( var i=0, len = items.length; i<len; i++ ) {
            if ( items[i] == singleItem )
                return;
        }
        
        items.push( singleItem );
        singleItem.selected = true;
        
        app.interface.on( 'selection-changed' );
    };
    
    this.remove = function( ) {
        var index;
        for ( var i=0, len = arguments.length; i<len; i++ ) {
            if ( ( index = items.indexOf( arguments[i] ) ) >= 0 ) {
                items.splice( index, 1 )[0].selected = false;
                app.interface.on( 'selection-changed' );
            }
        }
    };
    
    this.xor = function( item ) {
        var index;
        if ( ( index = items.indexOf( item ) ) == -1 ) {
            item.selected = true;
            items.push( item );
        } else {
            items.splice( index, 1 )[0].selected = false;
        }
        app.interface.on( 'selection-changed' );
    };
    
    Object.defineProperty( this, "length", {
        "get": function() {
            return items.length;
        }
    } );
    
    this.item = function( index ) {
        return items[ ~~index ];
    };
    
    var pluralize = function( str ) {
        return str.replace( /y$/, 'i' ) + 'es';
    }
    
    this.explain = function() {
        
        return {
            "length": items.length,
            "description": items.length == 0
                ? ""
                : ( items.length == 1 ? items[0].inode.name : items.length + " items" ),
            "types": ( function() {
                var out = {},
                    itemType,
                    out2 = [];

                for ( var i=0, len = items.length; i<len; i++ ) {
                    
                    itemType = items[i].inode.type.split( '.' )[0];
                    
                    out[ itemType ] = out[ itemType ] || 0;
                    
                    out[ itemType ]++;
                    
                }
                
                for ( var k in out ) {
                    
                    if ( out.propertyIsEnumerable( k ) && out.hasOwnProperty( k ) ) {
                        out2.push( { "type": k, "length": out[ k ] }.chain( function() {
                            
                            this.toString = function() {
                                return this.length == 1 ? "1 " + this.type : this.length + " " + pluralize( this.type );
                            };
                            
                        } ) );
                    }
                    
                }
                
                return out2;
                
            } )(),
            "selectionType": items.length == 0
                ? 'empty'
                : ( items.length == 1
                    ? 'single'
                    : 'multiple'
                )
        };
        
    };
    
    return this;
}