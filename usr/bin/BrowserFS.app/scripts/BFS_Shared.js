function BFS_Shared( app ) {
    
    var shared = {};
    
    Object.defineProperty( app, "shared", {
        
        "get": function() {
            return shared;
        }
        
    } );
    
}