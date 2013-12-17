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
        
        var actions = app.interface.filesAssoc.getActions( selection ),
            action  = null;
        
        for ( var i=0, len = actions.length; i<len; i++ ) {
            
            if ( actions[i]['default'] ) {
                action = actions[i];
                break;
            }
            
        }
        
        if ( action )
            action.handler( selection );
    };
    
    return this;
}