function BFS_SearchBar( app ) {
    
    var holder = $('div', 'BFS_SearchBar' ),
        body   = holder.appendChild( $('div', 'body') ),
        
        input = body.appendChild( new TextBox('') ).setAttr( 'placeholder', 'Search' ),
        
        visible = true;
    
    Object.defineProperty( holder, "visible", {
        
        "get": function() {
            
            return visible;
            
        },
        "set": function( bool ) {
            
            visible = !!bool;
            
            app[ visible ? 'removeClass' : 'addClass' ]( 'no-search-bar' );
            
        }
        
    } );
    
    holder.visible = app.flags.applicationMode == 'shell';
    
    if ( app.flags.applicationMode == 'shell' )

        Keyboard.bindKeyboardHandler( app, 'ctrl f', function() {
            
            if ( holder.visible )
                input.focus();
            else {
                holder.visible = true;
                input.focus();
            }
            
        } );
    
    return holder;
    
}