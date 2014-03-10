function BFS_Actions( app ) {
    
    var flags = app.interface.filesAssoc.flags;
    
    // register the "Open" action on folders.
    app.interface.filesAssoc.registerAction(
        "Open",
        true,
        "",
        function( selection ) {
            app.location = selection.item( 0 ).inode.url;
        },
        flags.SINGLE + flags.ALL_DIRS,
        function( inode ) {
            return true;
        },
        1
    );
    
}