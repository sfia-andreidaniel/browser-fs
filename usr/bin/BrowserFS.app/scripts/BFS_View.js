/* BrowserFS View panel */
function BFS_View( app ) {
    
    var panel = $('div', 'BFS_View' ),
        body  = panel.appendChild( $('div', 'body.medium' ) );
    
    // link to app icons manager
    panel.icons = app.interface.iconsManager;
    
    var sizes = {
        "small": {
            "iconWidth": 16,
            "iconHeight": 16,
            "itemWidth": 200,
            "itemHeight": 18
        },
        "medium": {
            "iconWidth": 64,
            "iconHeight": 64,
            "itemWidth": 100,
            "itemHeight": 110
        },
        "large": {
            "iconWidth": 128,
            "iconHeight": 128,
            "itemWidth": 140,
            "itemHeight": 180
        }
    },

    size = 'medium',
    
    iconWidth = sizes[size].iconWidth,
    iconHeight= sizes[size].iconHeight,
    itemWidth = sizes[size].itemWidth,
    itemHeight= sizes[size].itemHeight,
    
    sortBy = 'type',
    
    items = [];
    
    Object.defineProperty( panel, "iconWidth", {
        "get": function() {
            return iconWidth;
        }
    } );
    
    Object.defineProperty( panel, "iconHeight", {
        "get": function() {
            return iconHeight;
        }
    } );
    
    Object.defineProperty( panel, "itemWidth", {
        "get": function() {
            return itemWidth;
        }
    } );
    
    Object.defineProperty( panel, "itemHeight", {
        "get": function() {
            return itemHeight;
        }
    } );
    
    Object.defineProperty( panel, "size", {
        
        "get": function() {
            return size;
        },
        "set": function( sizeStr ) {
            
            if ( [ 'small', 'medium', 'large' ].indexOf( sizeStr ) == -1 )
                throw "Bad size: Allowed: 'small', 'medium', 'large'";
            
            panel.removeClass( 'small' ).removeClass( 'medium' ).removeClass( 'large' ).addClass( size = sizeStr );
            
            iconWidth  = sizes[ size ].iconWidth;
            iconHeight = sizes[ size ].iconHeight;
            itemWidth  = sizes[ size ].itemWidth;
            itemHeight = sizes[ size ].itemHeight;
            
            panel._paint_();
        }
        
    } );
    
    var iconsArranger = Cowboy.debounce( 20, false, function() {
        
        var cx = 0,
            cy = 0,
            winWidth = body.offsetWidth,
            winHeight = body.offsetHeight;
        
        for ( var i=0, len = items.length; i<len; i++ ) {
            items[i].style.left = cx + "px";
            items[i].style.top  = cy + "px";
            cx += ( itemWidth + 10 );
            if ( cx > winWidth ) {
                cx = 0;
                cy += ( itemHeight + 10 );
            }
        }
        
    } );
    
    var typeCompare = function( item1, item2 ) {
        var type1 = item1.inode.type.split( '.' )[0].toLowerCase(),
            type2 = item2.inode.type.split( '.' )[0].toLowerCase();
        
        return type1 == 'category' &&
               type2 == 'category'
            ? 0
            : ( type1 == 'category'
                ? -1
                : 1
            );
    }
    
    var nameCompare = function( inode1, inode2 ) {
        
        var result;
        
        if ( result = typeCompare( inode1, inode2 ) == 0 ) {
            return inode1.inode.name.toLowerCase().strcmp( inode2.inode.name.toLowerCase() );
        } else
            return result;
        
    };
    
    var itemsSorter = Cowboy.debounce( 20, false, function() {
        
        switch ( sortBy ) {
            case 'type':
                items.sort( typeCompare );
                break;
            case 'name':
                items.sort( nameCompare );
                break;
            default:
                items.sort( nameCompare );
                break;
        }
        
        iconsArranger();
    } );
    
    panel.addItem = function( obj ) {
        items.push( body.appendChild( new BFS_View_Icon( panel, obj ) ) );
        itemsSorter();
    };
    
    panel.clear = function() {

        // Clears the panel
        while ( items.length )
            body.removeChild( items.shift() );

    };
    
    panel.setItems = function( itemsList ) {
        
        // Set items of the panel
        
    };
    
    panel._paint_ = function() {
    
        for ( var i=0, len = items.length; i<len; i++ )
            items[i]._paint_();
        
        iconsArranger();
    }
    
    panel.size = 'large';
    
    return panel;
    
}