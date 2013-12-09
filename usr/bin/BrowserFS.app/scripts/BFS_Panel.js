/* BrowserFS left panel */
function BFS_Panel( app ) {
    
    var panel = $('div', 'BFS_Panel' ),
        body  = panel.appendChild( $('div', 'body' ) );
    
    /* Creates a panel in the panel area */
    panel.createPanel = function( panelTitle ) {
        
        var holder    = body.appendChild( $('div', 'Panel' ) ),
            titlebar  = holder.appendChild( $('div', 'title' ) ),
            caption   = titlebar.appendChild( $('div', 'label' ) ),
            panelBody = holder.appendChild( $('div', 'body' ) );
        
        caption.appendChild( $text( panelTitle || 'Panel' ) );
        
        holder.insert = function( DOMNode ) {
            return panelBody.appendChild( DOMNode );
        };
        
        caption.setAttr( 'dragable', '1' );
        
        return holder;
    };
    
    setTimeout( function() {
        MakeSortable( body );
    
        BFS_Panel_Details( app, panel );
        BFS_Panel_Places( app, panel );
        
    }, 100 );
    
    return panel;
    
}