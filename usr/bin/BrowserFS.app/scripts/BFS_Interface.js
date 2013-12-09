/* Initialize the browserFS backend interface 
 */
function BFS_Interface( app ) {
    
    var interface = new Thing();
    
    // application menu
    BFS_Menu( app );
    // application toolbar
    BFS_Toolbar( app );
    
    // application activity panel
    interface.panel    = app.insert( new BFS_Panel( app ) );
    // application view ( icons, folders )
    interface.view     = app.insert( new BFS_View( app ) );
    // application location address bar
    interface.location = app.insert( new BFS_AddressBar( app ) );
    // initialize the icon manager
    interface.iconsManager = new BFS_IconManager();
    
    Object.defineProperty( app, "interface", {
        "get": function() {
            return interface;
        }
    } );
}