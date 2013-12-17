function BFS_View_Mouse_Selection( /*<BFS_View_Body>*/ body, app ) {
    
    var sel     = $('div', 'selection' ),
        origin  = { "x": 0, "y": 0, "x1": 0, "y1": 0 },
        visible = false,
        active  = false,
        effect  = 'set',
    
    bodyMouseMove = function( e ) {
        
        if ( active && !visible ) {
            
            visible = true;
            
            body.appendChild( sel );
            
            sel.style.top    = origin.y + "px";
            sel.style.width  = origin.x1 + "px";
            sel.style.height = origin.y1 + "px";
            sel.style.left   = origin.x + "px";
            
        }
        
        origin.x1 = e.target == body ? e.layerX : ( function( targ, x ) {
            while ( targ != body ) {
                x += targ.offsetLeft;
                targ = targ.parentNode;
            }
            return x;
        } )( e.target, e.layerX );

        origin.y1 = e.layerY == body ? e.layerY : ( function( targ, y ) {
            while ( targ != body ) {
                y += targ.offsetTop;
                targ = targ.parentNode;
            }
            return y;
        } )( e.target, e.layerY );
        
        sel.style.left   = Math.min( origin.x, origin.x1 ) + "px";
        sel.style.top    = Math.min( origin.y, origin.y1 ) + "px";
        sel.style.width  = Math.abs( origin.x - origin.x1 ) + "px";
        sel.style.height = Math.abs( origin.y - origin.y1 ) + "px";
        
        cancelEvent( e );
    },
    
    querySelection = function( ) {
        
        if ( origin.x == origin.x1 || origin.y == origin.y1 )
            return [];
        
        var x  = Math.min ( origin.x, origin.x1 ),
            x1 = Math.max ( origin.x, origin.x1 ),
            y  = Math.min ( origin.y, origin.y1 ),
            y1 = Math.max ( origin.y, origin.y1 ),
            result = [],
            overlaps = {
                offsetLeft: x,
                offsetTop : y,
                offsetWidth: x1 - x,
                offsetHeight: y1-y
            };
        
        //console.log( x, x1, y, y1 );
        
        for ( var i=0, items = Array.prototype.slice.call( body.childNodes, 0 ), len = items.length; i<len; i++ ) {
            
            if ( items[i].overlapsWith( overlaps ) )
                result.push( items[i] );
            
        }
        
        //console.log( result );
        
        return result;
    },
    
    bodyMouseUp = function( e ) {
        
        if ( visible ) {
            body.removeChild( sel );
        }
        
        body.removeEventListener( 'mousemove', bodyMouseMove, true );
        body.removeEventListener( 'mouseup',   bodyMouseUp, true );
        
        active = false;
        
        if ( !visible )
            return;
        
        visible = false;
        
        var items = querySelection();
        
        if ( effect == 'set' )
            app.interface.selection.clear();
        
        for ( var i=0, len = items.length; i<len; i++ ) {
            app.interface.selection.add( items[i] );
        }
        
        cancelEvent( e );
    };
    
    body.addEventListener( 'mousedown', function( e ) {
        
        if ( e.which != 1 || e.target != body || active || !!body.querySelector( '.icon.rename' ))
            return;
        
        effect = e.ctrlKey ? 'add' : 'set';
        
        origin.x = origin.x1 = e.layerX;
        origin.y = origin.y1 = e.layerY;
        
        active = true;
        
        body.addEventListener( 'mousemove', bodyMouseMove, true );
        body.addEventListener( 'mouseup', bodyMouseUp, true );
        
        cancelEvent( e );
        
    }, true );
    
    
};