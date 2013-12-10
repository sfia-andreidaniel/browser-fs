function BFS_Selection( app ) {
    
    var items = [];
    
    this.clear = function() {
        while ( items.length )
            items.shift().selected = false;
        app.interface.on( 'selection-changed' );
    };
    
    this.add = function( ) {
        items = items.unique( items.merge( Array.prototype.slice.call( arguments, 0 ) ) );
        for ( var i=0, len = arguments.length; i<len; i++ )
            arguments[i].selected = true;
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
            items.splice( index )[0].selected = false;
        }
        app.interface.on( 'selection-changed' );
    }
    
    return this;
}