/* Initialize the browserFS backend interface 
 */
function BFS_Interface( app ) {
    
    var interface = new Thing();
    
    Object.defineProperty( app, "interface", {
        "get": function() {
            return interface;
        }
    } );

    // application menu
    BFS_Menu( app );
    // application toolbar
    BFS_Toolbar( app );
    
    // initialize the icon manager
    interface.iconsManager = new BFS_IconManager();
    
    // initialize the selection manager
    interface.selection = new BFS_Selection( app );

    // application activity panel
    interface.panel    = app.insert( new BFS_Panel( app ) );

    // application view ( icons, folders )
    interface.view     = app.insert( new BFS_View( app ) );

    // application location address bar
    interface.location = app.insert( new BFS_AddressBar( app ) );
    
    Object.defineProperty( app, "location", {
        
        "get": function() {
            return interface.location.href;
        },
        "set": function( value ) {
            interface.location.href = value;
        }
        
    } );
    
}