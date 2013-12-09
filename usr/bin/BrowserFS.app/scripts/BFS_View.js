/* BrowserFS View panel */
function BFS_View( app ) {
    
    var panel = $('div', 'BFS_View' ),
        body  = panel.appendChild( $('div', 'body.medium' ) );
    
    var sizes = {
        "small": {
            "width": 200,
            "height": 20
        },
        "medium": {
            "width": 64,
            "height": 96
        },
        "large": {
            "width": 128,
            "height": 128
        }
    },

    size = 'medium',
    itemWidth = sizes[size].width,
    itemHeight= sizes[size].height,

    items = [];
    
    Object.defineProperty( panel, "size", {
        
        "get": function() {
            return size;
        },
        "set": function( sizeStr ) {
            
            if ( [ 'small', 'medium', 'large' ].indexOf( sizeStr ) == -1 )
                throw "Bad size: Allowed: 'small', 'medium', 'large'";
            
            panel.removeClass( 'small' ).removeClass( 'medium' ).removeClass( 'large' ).addClass( size = sizeStr );
            
            app.interface.on( 'repaint' );
        }
        
    } );
    
    panel.addItem = function( obj ) {
        
        // Adds an object to the panel
        
        
        items.push( body.appendChild( new BFS_View_Icon( panel, obj ) ) );
        
    };
    
    panel.clear = function() {

        // Clears the panel

    };
    
    panel.setItems = function( itemsList ) {
        
        // Set items of the panel
        
    };
    
    return panel;
    
}