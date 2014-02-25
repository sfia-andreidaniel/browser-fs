function BFS_AppMode_open( app ) {
    
    app.menuVisible = false;

    app.interface.panel.visible = false;

    app.interface.search.visible = false;
    app.interface.search.enabled = false;
    
    app.addCustomEventListener( 'location-changed', function( cwd ) {
        app.caption = cwd + ' - Open files - BrowserFS';
    });
    
}