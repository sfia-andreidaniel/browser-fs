function BFS_cmd_edit_cut( app ) {
    
    app.handlers.cmd_edit_cut = function() {
        
        BFS_Clipboard.cut( app.interface.selection );
        
        console.log( app.interface.selection.length + " items were cutted!" );
        
    };

}