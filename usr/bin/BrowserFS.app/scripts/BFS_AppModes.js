function BFS_AppModes( app ) {
    
    window[ 'BFS_AppMode_' + app.flags.applicationMode ]( app );
    
    app.createDialog = function( dialogMode, cwd ) {
        
        var dlg = new BFS( {
            
            "applicationMode": dialogMode,
            "connection": app.connection || ( function() {
                throw new Exception( 'OneDB.GeneralException', 'Cannot create a dialog while the main application is not connected' );
            } )(),
            "width": BFS_Globals.appModes[ dialogMode ].width,
            "height": BFS_Globals.appModes[ dialogMode ].height,
            "location": cwd || null

        } );
        
    }
    
}