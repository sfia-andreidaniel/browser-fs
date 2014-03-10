function BFS_cmd_edit_copy( app ) {
    
    app.handlers.cmd_edit_copy = function() {
        
        BFS_Clipboard.copy( app.interface.selection );
        
        console.log( app.interface.selection.length + " items were copied!" );
        
    };

}