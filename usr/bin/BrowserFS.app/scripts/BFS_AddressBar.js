function BFS_AddressBar( app ) {
    
    var holder     = $('div', 'BFS_AddressBar' ),
        body       = holder.appendChild( $('div', 'body' ) ),
        inputMode  = 0, // will be boolean
        href       = '/',
        cwd        = null,
        pathParser = new OneDB_Path(),
        visible    = true;
    
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

            editable = bool;
            
            holder.render();
        }
        
    } );
    
    Object.defineProperty( holder, "visible", {
        
        "get": function() {
            return visible;
        },
        "set": function( bool ) {
            
            visible = !!bool;
            
            app[ visible ? 'removeClass' : 'addClass' ]( 'no-address-bar' );
            
        }
        
    } );
    
    /* Creates an editable location interface */
    var createEditable = function() {
        
        var input = ( new TextBox('') ).setAnchors({
            "width": function( w,h ) {
                return w - 70 + "px";
            }
        }).setAttr("style", "position: absolute; left: 60px" );
        
        input.value = href || '/';
        
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
        
        input.addCustomEventListener( 'update', function( url ) {
            
            input.value = url;
            input.select();
            
        } );
        
        return input;
    }
    
    /* Creates a navigable location interface */
    var createNavigable = function() {
        
        var navigable = $('div', 'navigable' );
        
        setTimeout( function() {
            app.focus();
        }, 1 );
        
        navigable.addCustomEventListener( 'update', function( url ) {
            
            //navigable.innerHTML = '';
            
            while( navigable.firstChild )
                navigable.removeChild( navigable.firstChild ).purge();
            
            var locHref = href,
                parts   = [];
            
            while ( locHref ) {
                
                parts.push( {
                    "label": pathParser.basename( locHref ) || '/',
                    "url"  : locHref || '/'
                } );
                
                locHref = pathParser.substract( locHref, 1 );
                
            }
            
            parts = parts.reverse();
            
            for ( var i=0, len = parts.length; i<len; i++ ) {
                
                ( function( segment ) {
                    
                    navigable.appendChild( new Button( segment.label, function() {
                        
                        holder.href = segment.url;
                        
                    } ) );
                    
                } )( parts[i] );
                
            }
            
        } );
        
        //console.log( 'created a navigable' );
        
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
            inputMode = body.appendChild( new createEditable() );
        else
            inputMode = body.appendChild( new createNavigable() );
        
        inputMode.onCustomEvent( 'update', href );
        
        app.paint();
        
    }
    
    holder.editable = false;
    holder.visible  = true;
    
    Keyboard.bindKeyboardHandler( app, "ctrl e", function(){
        if ( holder.visible )
            holder.editable = !holder.editable;
        else {
            holder.visible = true;
        }
    } );
    
    Object.defineProperty( holder, "inode", {
        
        "get": function() {
            return cwd || null;
        }
        
    } );
    
    Object.defineProperty( holder, "href", {
        
        "get": function() {
            return href;
        },
        "set": function( str ) {
        
            var testStr = pathParser.resolve( str );
            
            if ( testStr === false )
                throw Exception('Exception.IO', 'invalid path "' + str + '"' );

            holder.editable = false;
            
            var newWorkingDirectory = false;
            
            try {
            
                newWorkingDirectory = app.connection.getElementByPath( testStr );
                
                if ( newWorkingDirectory === null )
                    throw Exception( 'Exception.IO', 'Invalid location' );
                
                cwd = newWorkingDirectory;
                
                app.appHandler( 'cmd_refresh' );
                
                href = cwd.url;
                
                inputMode.onCustomEvent( 'update', href );
                
                app.onCustomEvent( 'location-changed', href );
                
            } catch ( Error ) {
                
                DialogBox( Error + '', {
                    "caption": "Error opening location",
                    "childOf": app,
                    "modal": true
                } );
                
                return;
            }

        }
        
    } );
    
    app.handlers.cmd_open_address = function() {
        holder.editable = true;
    }
    
    app.handlers.cmd_refresh = function() {
        
        app.interface.view.clear();
        
        if ( !app.connection || !cwd )
            return;
        
        cwd.childNodes.each( function() {
            app.interface.view.addItem( this );
        } );
        
    }
    
    app.interface.bind( 'connected', function() {
        cwd = app.connection.rootNode;
        holder.href = '/';
    } );
    
    return holder;
    
}