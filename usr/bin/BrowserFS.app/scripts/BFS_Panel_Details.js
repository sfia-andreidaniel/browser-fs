/* BrowserFS left panel */
function BFS_Panel_Details( app, appPanel ) {
    
    var panel = appPanel.createPanel( 'Details' ).addClass( 'details' );
    
    panel.insert( $text( 'No details at this point' ) );
    
    var updater = Cowboy.debounce( 100, false, function() {
        
        var explain = app.interface.selection.explain(),
            more;
        
        panel.body.innerHTML = '';
        
        switch ( explain.selectionType ) {
            
            case 'empty':
                more = app.interface.view.length;
                
                panel.body.innerHTML = more == 0 
                    ? "Location is empty"
                    : (
                        more == 1
                            ? "1 item in this location"
                            : more + " items in this location"
                    );
                break;
            
            case 'single':
                panel.insert( $('b') ).appendChild( $text( explain.description ) );
                panel.insert( $('br') );
                panel.insert( $text( app.interface.selection.item(0).inode.type ) );
                
                more = app.interface.selection.item(0).inode.data._explain_();
                
                if ( more.length ) {
                    
                    panel.insert( $('hr') );
                    
                    for ( var i=0, len = more.length; i<len; i++ ) {
                        
                        panel.insert( $text( more[i] ) );
                        panel.insert( $('br') );
                        
                    }
                    
                }
                
                break;
            
            case 'multiple':
                panel.insert( $('b') ).appendChild( $text( explain.description ) );
                panel.insert( $('br') );
                panel.insert( $text( explain.types.join( ', ' ) ) );
                break;
            
        }
        
    } );
    
    app.interface.bind( 'selection-changed', function() {
        
        updater();
        
    } );
    
}