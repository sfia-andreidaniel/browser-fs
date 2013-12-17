function BFS_Creator( app ) {
    
    app.handlers.cmd_create = function( inodeType ) {
    
        var inode, icon;
    
        try {
        
            if ( typeof OneDB_Types[ 'OneDB_Type_' + inodeType.replace( /\./, '_' ) ] == 'undefined' )
                throw Exception( 'Exception.FS', 'Undefined node type: ' + inodeType );
            
            if ( app.interface.location.inode === null )
                throw Exception( 'Exception.Connection', "Not connected!" );
            
            inode = app.interface.location.inode.create( inodeType, 'New ' + inodeType.replace( /[\._]+/g, ' ' ), 2 ); // 2 - F_CREATE_NOCONFLICT
            
            icon = app.interface.view.addItem( inode );
            
            icon.scrollIntoViewIfNeeded();
            
            app.interface.selection.set( icon );
            app.interface.view.activeItem = icon;
            
            icon.renameMode = true;
        
        } catch ( error ) {
            
            DialogBox( error + '', {
                
                "title": "Failed to create a " + inodeType + '',
                "childOf": app,
                "modal": true
                
            } );
            
        }
        
    }
    
}