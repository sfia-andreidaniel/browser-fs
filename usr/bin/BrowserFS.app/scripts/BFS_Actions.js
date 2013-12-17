function BFS_Actions( app ) {
    
    var flags = app.interface.filesAssoc.flags;
    
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