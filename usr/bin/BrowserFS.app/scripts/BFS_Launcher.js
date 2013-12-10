/* The launcher is responsible for opening items
   on enter and double click events
 */
function BFS_Launcher( app ) {
    
    ( function( launcher ) {
        app.interface.bind( 'open', function() {
            launcher.open( app.interface.selection );
        } );
    })( this );
    
    this.open = function( selection ) {

        // opening multiple items at this time is not supported
        if ( selection.length != 1 )
            return;
        
        if ( selection.item(0).inode.has_flag( 'container' ) ) {
            
            app.location = selection.item( 0 ).inode.url;
            return;
            
        }

    };
    
    return this;
}