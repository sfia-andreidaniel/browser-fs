function BFS_Common_Commands( app ) {
    
    var closeConfirmed = false;
    
    app.closeCallback = function() {
        
        if ( !closeConfirmed ) {
        
            DialogBox( "Are you sure you want to quit BrowserFS?", {
                
                "buttons": {
                    "Yes": function() {
                        
                        closeConfirmed = true;
                        app.close();
                        
                    },
                    "No": function() {
                        // Do nothing
                    }
                },
                "caption": "Confirm exit",
                "childOf": app
                
            } );
            
            return false;
        
        } else {
            
            return true;
            
            // purge dialog
            setTimeout( function() {
                
                app.purge();
                
            }, 100 );
        }
        
    };
    
    app.handlers.cmd_exit = function() {
        app.close();
    };
    
    app.handlers.cmd_location_up_level = function() {
        app.location += '/../';
    }
    
    Keyboard.bindKeyboardHandler( app, 'alt up', function() {
        app.appHandler( 'cmd_location_up_level' );
    } );
    
}