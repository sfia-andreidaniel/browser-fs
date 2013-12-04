function BFS_Startup( callback ) {
    
    var tasker = new Async();
    
    tasker.sync( function() {
        
        console.log( "BFS: loading rpc ..." );
        
        window.BFS_RPC = 'browser-fs/onedb-rpc.php';
        
        /* Check if the BrowserFS library is loaded. */
        
        if ( document.getElementById( 'browserfs-rpc' ) === null ) {
            
            ( function( task ) {
                
                var script = document.createElement( 'script' );
                
                script.id   = 'browserfs-rpc';
                script.src  = 'browser-fs/rpc.js.php';
                script.type = 'text/javascript';
                
                document.getElementsByTagName( 'head' )[0].appendChild( script );
                
                script.addEventListener( 'load', function() {
                    
                    // the rpc has been loaded
                    
                    task.on( 'success' );
                    
                    console.log( 'loaded' );
                    
                }, false );
                
            })( this );
            
        } else this.on( 'success' );
        
    } );
    
    tasker.sync( function() {
        
        if ( typeof window.OneDB === 'undefined' )
            this.on( 'error', "BrowserFS seems to be injected in dom, but it is not loaded!" );
        else
            this.on( "success" );
        
    } );
    
    tasker.run(
        function() {
            callback( false );
        },
        function( reason ) {
            callback( reason );
        }
    );
    
}