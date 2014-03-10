function BFS_cmd_file_delete( app ) {
    
    app.handlers.cmd_file_delete = function() {
        
        if ( !app.interface.selection.length )
            return;
        
        DialogBox( "Are you sure you want to delete " + app.interface.selection.length + " item(s)?", {
            
            "modal": true,
            "childOf": app,
            "buttons": {
                "Yes": function() {
                    
                    try {
                    
                        for ( var i=0, sel = app.interface.selection, len = sel.length; i<len; i++ ) {
                            
                            sel.item(i).inode.delete();
                            
                        }
                    
                        app.appHandler( 'cmd_refresh' );
                    
                    } catch ( error ) {
                        
                        setTimeout( function() {
                            
                            DialogBox( "Error deleting: " + error, {
                                
                                "type": "error",
                                "childOf": app,
                                "buttons": {
                                    "Ok": function() {
                                        
                                        app.appHandler( 'cmd_refresh' );
                                        
                                    }
                                }
                                
                            } );
                            
                        }, 1 );
                        
                    }
                    
                },
                "No": function() {
                    
                }
            },
            "type": "warning"
            
        } );
        
    }

}