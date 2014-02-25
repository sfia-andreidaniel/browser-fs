/* BrowserFS left panel */
function BFS_Panel( app ) {
    
    var panel   = $('div', 'BFS_Panel' ),
        body    = panel.appendChild( $('div', 'body' ) ),
        visible = true;
    
    /* Creates a panel in the panel area */
    panel.createPanel = function( panelTitle ) {
        
        var holder    = body.appendChild( $('div', 'Panel' ) ),
            titlebar  = holder.appendChild( $('div', 'title' ) ),
            caption   = titlebar.appendChild( $('div', 'label' ) ),
            panelBody = holder.appendChild( $('div', 'body' ) ),
            expanded  = true,
            btnExpand = titlebar.appendChild( $('div', 'toggle' ) ).chain( function() {
                this.onclick = function() { holder.expanded = !holder.expanded; };
            });
        
        caption.appendChild( $text( panelTitle || 'Panel' ) );
        
        holder.insert = function( DOMNode ) {
            return panelBody.appendChild( DOMNode );
        };
        
        Object.defineProperty( holder, "body", {
            "get": function() {
                return panelBody;
            }
        } );
        
        Object.defineProperty( holder, "expanded", {
            "get": function() {
                return expanded;
            },
            "set": function( bool ) {
                expanded = !!bool;
                holder[ expanded ? 'removeClass' : 'addClass' ]( 'collapsed' );
            }
        } );
        
        caption.setAttr( 'dragable', '1' );
        
        return holder;
    };
    
    setTimeout( function() {
        MakeSortable( body );
    
        BFS_Panel_Details( app, panel );
        BFS_Panel_Places( app, panel );
        
    }, 100 );
    
    Object.defineProperty( panel, "visible", {
        "get": function() {
            return visible;
        },
        "set": function( boolVal ) {
            visible = !!boolVal;
            app[ visible ? "removeClass": "addClass" ]( "panel-off" );
            app.onCustomEvent( 'resizeRun' );
        }
    } );
    
    return panel;
    
}