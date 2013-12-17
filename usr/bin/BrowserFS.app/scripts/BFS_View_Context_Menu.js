function BFS_View_Context_Menu( body, app ) {
    
    function CTX_Menu() {
        
        var on     = 'body',
            cursor = null;
        
        this.setup = function( event ) {
            
            if ( event.shiftKey )
                return false;
            
            if ( ( cursor = event.toElement ) == body ) {

                app.interface.selection.clear();

                on = 'body';

            } else {
            
                // loop the cursor until the body or an icon is found
            
                while ( cursor != body && !cursor.hasClass( 'icon' ) ) {
                    cursor = cursor.parentNode;
                }
            
                if ( cursor == body ) {
                
                    on = 'body';
                    
                } else {
                    
                    // The user clicked on a selected item.
                    // We're generating a context menu for the selection
                    if ( cursor.selected ) {
                        
                        on = 'selection';
                        
                    } else {
                        
                        // we're automatically setting the selection to the icon
                        // we right-clicked
                        app.interface.selection.set( cursor );
                        app.interface.view.activeItem = cursor;
                        
                        on = 'selection';
                        
                    }
                    
                }
                
            }
            
            return true;
        };
        
        this.generate = function() {
            
            var out = [],
                actions,
                selection;;
            
            switch ( on ) {
                
                case 'body':
                    
                    out.push( app.shared.menu.create );
                    
                    out.push( null );
                    
                    out.push( {
                        "caption": "Arrange Icons",
                        "icon": "",
                        "items": [
                            {
                                "caption": "By Name",
                                "icon": "",
                                "id": "",
                                "input": "radio"
                            },
                            {
                                "caption": "By Type",
                                "icon": "",
                                "id": "",
                                "input": "radio"
                            },
                            null,
                            {
                                "caption": "Reverse Order",
                                "icon": "",
                                "id": "",
                                "input": "checkbox"
                            }
                        ]
                    } );
                    
                    // push the "view icons size menu"
                    out.push( app.shared.menu.view );
                    
                    out.push( null );
                    
                    out.push( app.shared.menu.pasteMenu );
                    
                    out.push( null );
                    
                    out.push( app.shared.menu.properties );
                    
                    break;
                
                case 'selection':
                    
                    // get the available actions for the selection
                    actions = app.interface.filesAssoc.getActions( app.interface.selection );
                    
                    if ( actions.length ) {
                        
                        for ( var i=0, len = actions.length; i<len; i++ ) {
                            
                            ( function( action ) {
                            
                                out.push({
                                    "caption": action.name,
                                    "handler": function( ) {
                                        return action.handler( app.interface.selection );
                                    }
                                });
                            
                            })( actions[i] );
                            
                        }
                        
                        out.push( null );
                        
                    }
                    
                    out.push( app.shared.menu.menu_cut );
                    out.push( app.shared.menu.menu_copy );
                    
                    out.push( null );
                    
                    out.push( app.shared.menu.menu_rename );
                    out.push( app.shared.menu.menu_copy_to );
                    out.push( app.shared.menu.menu_move_to );
                    
                    out.push( null );
                    
                    out.push( app.shared.menu.menu_delete );
                    
                    break;
                
            }
            
            return out;
            
        };
        
        return this;
    };
    
    var context = new CTX_Menu();
    
    body.addContextMenu( context.generate, context.setup );
    
}