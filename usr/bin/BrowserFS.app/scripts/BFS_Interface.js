/* Initialize the browserFS backend interface 
 */
function BFS_Interface( app ) {
    
    var interface = new Thing();
    
    Object.defineProperty( app, "interface", {
        "get": function() {
            return interface;
        }
    } );

    
    interface.bind( 'resources-loaded', function() {
        
        // application menu
        BFS_Menu( app );
        // application toolbar
        BFS_Toolbar( app );
        
    } );

    // initialize the icon manager
    interface.iconsManager = new BFS_IconManager( app );
    
    // initialize the selection manager
    interface.selection = new BFS_Selection( app );

    // application launcher
    interface.launcher = new BFS_Launcher( app );
    
    // application activity panel
    interface.panel    = app.insert( new BFS_Panel( app ) );

    // application view ( icons, folders )
    interface.view     = app.insert( new BFS_View( app ) );

    // application location address bar
    interface.location = app.insert( new BFS_AddressBar( app ) );
    
    // application search bar
    interface.search = app.insert( new BFS_SearchBar( app ) );
    
    Object.defineProperty( app, "location", {
        
        "get": function() {
            return interface.location.href;
        },
        "set": function( value ) {
            interface.location.href = value;
        }
        
    } );
    
}