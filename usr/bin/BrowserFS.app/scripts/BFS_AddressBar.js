function BFS_AddressBar( app ) {
    
    var holder = $('div', 'BFS_AddressBar' ),
        body   = holder.appendChild( $('div', 'body' ) ),
        inputMode = 0, // will be boolean
        href   = '/';
    
    body.appendChild( new DOMLabel( 'Address: ', {
        "x": 0,
        "y": 6
    } ) );
    
    var editable = false;
    
    Object.defineProperty( holder, 'editable', {
        
        "get": function() {
            return editable;
        },
        "set": function( bool ) {
            bool = !!bool;

            if ( bool === editable )
                return;
            
            editable = bool;
            
            holder.render();
        }
        
    } );
    
    /* Creates an editable location interface */
    var createEditable = function() {
        
        var input = ( new TextBox('') ).setAnchors({
            "width": function( w,h ) {
                return w - 70 + "px";
            }
        }).setAttr("style", "position: absolute; left: 60px" );
        
        input.value = '/';
        
        setTimeout( function() {
            
            input.focus();
            input.select();
            
        }, 1 );
        
        Keyboard.bindKeyboardHandler( input, "esc", function() {
            holder.editable = false;
        } );
        
        Keyboard.bindKeyboardHandler( input, "enter", function() {
            holder.href = input.value;
        } );
        
        input.addEventListener( 'blur', function() {
            setTimeout( function() {
                holder.editable = false;
            }, 100 );
        }, false );
        
        return input;
    }
    
    /* Creates a navigable location interface */
    var createNavigable = function() {
        
        var navigable = $('div', 'navigable' );
        
        setTimeout( function() {
            app.focus();
        }, 1 );
        
        return navigable;
        
    }
    
    // renders the address bar viewing mode
    holder.render = function() {
        
        if ( body.firstChild.nextSibling )
            body.removeChild( body.firstChild.nextSibling );
        
        if ( inputMode )
            inputMode.purge();
        
        inputMode = null;
        
        if ( editable )
            inputMode = body.appendChild( createEditable() );
        else
            inputMode = body.appendChild( createNavigable() );
        
        app.paint();
        
    }
    
    holder.editable = false;
    
    Keyboard.bindKeyboardHandler( app, "ctrl e", function(){
        holder.editable = !holder.editable;
    } );
    
    Object.defineProperty( holder, "href", {
        
        "get": function() {
            return href;
        },
        "set": function( str ) {

            holder.editable = false;
            
            

        }
        
    } );
    
    app.handlers.cmd_open_address = function() {
        holder.editable = true;
    }
    
    return holder;
    
}