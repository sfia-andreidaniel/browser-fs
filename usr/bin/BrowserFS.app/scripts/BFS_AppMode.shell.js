function BFS_AppMode_shell( app ) {
    
    app.addCustomEventListener( 'location-changed', function( path ) {
        
        app.caption = path + ' - BrowserFS';
        
        app.interface.view.focus();
        
        app.interface.view.iconSize = 'medium';

        BFS_cmd_file_delete( app );
        BFS_cmd_edit_cut( app );
        BFS_cmd_edit_copy( app );
        BFS_cmd_edit_paste( app );

    } );
    
}