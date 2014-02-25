function BFS_SearchBar( app ) {
    
    var holder = $('div', 'BFS_SearchBar' ),
        body   = holder.appendChild( $('div', 'body') ),
        
        input = body.appendChild( new TextBox('') ).setAttr( 'placeholder', 'Search' ),
        
        visible = true,
        enabled = true;
    
    Object.defineProperty( holder, "visible", {
        
        "get": function() {
            
            return visible;
            
        },
        "set": function( bool ) {
            
            visible = !!bool;
            
            app[ visible ? 'removeClass' : 'addClass' ]( 'no-search-bar' );
            
        }
        
    } );
    
    Object.defineProperty( holder, 'enabled', {
        
        "get": function() {
            return enabled;
        },
        "set": function( bool ) {
            enabled = !!bool;
        }
        
    } );
    
    holder.visible = true;
    
    Keyboard.bindKeyboardHandler( app, 'ctrl f', function() {
        
        if ( !enabled )
            return;
        
        if ( holder.visible )
            input.focus();
        else {
            holder.visible = true;
            input.focus();
        }
        
    } );
    
    return holder;
    
}